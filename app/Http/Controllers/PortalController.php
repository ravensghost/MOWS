<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Bulletin;
use App\Models\Configuration;
use App\Models\Folio;
use App\Models\Persona;
use App\Models\PersonaMetadata;
use App\Services\AiPortraitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Illuminate\View\View as ViewContract;
use Throwable;

class PortalController extends Controller
{
    /**
     * Display a published Book by its URL slug.
     */
    public function showBook(string $bookUrl = '/'): ViewContract
    {
        $normalizedUrl = '/' . ltrim($bookUrl, '/');
        $payload = $this->resolvePublishedBook($normalizedUrl);

        return view('book', $payload);
    }

    /**
     * Display a Persona biography within the 'bio' Book layout.
     */
    public function showBio(Persona $persona): ViewContract
    {
        $payload = $this->resolvePublishedBook('bio', 'public_label_Bio');

        $persona->load([
            'user',
            'lineageCatalogue',
            'insignia' => function ($query) {
                $query->where('is_visible', true)->with([
                    'insigniaCatalogue' => function ($q) {
                        $q->where('is_visible', true);
                    },
                ]);
            },
            'assignment' => function ($query) {
                $query->where('is_visible', true)->with([
                    'division' => function ($q) {
                        $q->where('is_visible', true);
                    },
                ]);
            },
            'candidatePetition.entries.metadata',
            'chronologicalRegisters.citation',
            'chronologicalRegisters.issuer',
            'attributes',
        ]);

        View::share('persona', $persona);
        $payload['persona'] = $persona;

        return view('book', $payload);
    }

    /**
     * Resolve a published Book, filter its modules by user clearance, and build the view payload.
     */
    protected function resolvePublishedBook(string $bookUrl, ?string $fallbackBookKey = null): array
    {
        $book = Book::where('book_url', $bookUrl)
            ->where('is_published', true)
            ->with([
                'grid.gridModules',
                'modules' => function ($query) {
                    $query->wherePivot('is_visible', true)
                        ->orderBy('module_sort_order');
                },
            ])
            ->firstOrFail();

        $user = auth()->user();

        $filteredModules = ($book->modules ?? collect())->filter(function ($module) use ($user) {
            if (! ($module->pivot->is_visible ?? true)) {
                return false;
            }

            $required = $module->pivot->access_clearance;
            if (empty($required)) {
                return true;
            }

            return $user ? $user->can($required) : false;
        })->values();

        $book->setRelation('modules', $filteredModules);

        $lookupUrl = '/' . ltrim($book->book_url, '/');
        $navKey = Cache::remember('nav_key_' . md5($lookupUrl), 3600, function () use ($lookupUrl) {
            return DB::table('table_of_contents')
                ->where('destination_url', $lookupUrl)
                ->value('translation_code_key');
        });

        $defaultKey = $fallbackBookKey ?: ('public_label_' . ($book->book_url === '/' ? 'Landing' : Str::studly(basename($book->book_url))));
        $bookKey = $navKey ?: $defaultKey;

        $moduleTokens = $filteredModules->pluck('module_token')->toArray();

        $bulletins = null;
        if (! empty(array_intersect(['bulletins', 'module_bulletins'], $moduleTokens))) {
            $bulletins = Bulletin::with('author')
                ->orderBy('published_at', 'desc')
                ->take(5)
                ->get();
        }

        $folios = null;
        if (! empty(array_intersect(['folios', 'module_folios', 'folio'], $moduleTokens))) {
            $folios = Folio::with('activePersona')
                ->where('folio_status', 'published')
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();
        }

        return [
            'book'      => $book,
            'navKey'    => $navKey,
            'bookKey'   => $bookKey,
            'bulletins' => $bulletins,
            'folios'    => $folios,
        ];
    }

    /**
     * Authorise portrait modifications for the given Persona.
     */
    protected function authorizePortraitAction(Persona $persona): void
    {
        $user = auth()->user();

        $isAuthorized = $user && (
            (int) $user->id === (int) $persona->user_id ||
            (method_exists($user, 'hasRole') && $user->hasRole('webmaster')) ||
            $user->can('manage_roster_assignments') ||
            $user->can('manage_users')
        );

        abort_unless(
            $isAuthorized,
            403,
            lexicon('msg_PersonaPortrait_Unauthorised', 'Unauthorised to modify persona portraits.')
        );
    }

    /**
     * Build the portrait data payload for JSON responses.
     */
    protected function getPortraitPayload(Persona $persona): array
    {
        $persona->refresh();
        $persona->load('attributes');

        $primaryUrl = $persona->primary_portrait_url
            ? (Str::startsWith($persona->primary_portrait_url, ['http://', 'https://', '/'])
                ? $persona->primary_portrait_url
                : asset('images/personas/' . $persona->primary_portrait_url))
            : null;

        $gallery = $persona->attributes
            ->where('attribute_key', 'gallery_image')
            ->values()
            ->map(function ($attr) {
                $val = $attr->attribute_value;

                return [
                    'id'       => $attr->id,
                    'filename' => $val,
                    'url'      => Str::startsWith($val, ['http://', 'https://', '/'])
                        ? $val
                        : asset('images/personas/' . $val),
                ];
            })
            ->all();

        return [
            'primary_filename' => $persona->primary_portrait_url,
            'primary_url'      => $primaryUrl,
            'gallery'          => $gallery,
        ];
    }

    /**
     * Upload a new primary or gallery portrait for a Persona.
     */
    public function uploadPersonaPortrait(Request $request, Persona $persona): JsonResponse
    {
        $this->authorizePortraitAction($persona);

        $request->validate([
            'file' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120'],
        ]);

        $destinationPath = public_path('images/personas');
        if (! File::exists($destinationPath)) {
            File::makeDirectory($destinationPath, 0755, true);
        }

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension() ?: 'webp');
        $filename = 'persona_' . $persona->id . '_' . time() . '_' . Str::lower(Str::random(5)) . '.' . $extension;

        $file->move($destinationPath, $filename);

        if (empty($persona->primary_portrait_url)) {
            $persona->update(['primary_portrait_url' => $filename]);
        } else {
            PersonaMetadata::create([
                'persona_id'      => $persona->id,
                'attribute_key'   => 'gallery_image',
                'attribute_value' => $filename,
            ]);
        }

        // Sync & verify 3-layer portrait attribution manifest (Phase 7 Section 1.7)
        if (class_exists(AiPortraitService::class)) {
            try {
                app(AiPortraitService::class)->resolveMissingAttribution(
                    'images/personas/' . $filename,
                    $request->only(['base_credit', 'uniform_credit', 'background_credit', 'artist', 'licence_url']),
                    $persona
                );
            } catch (Throwable) {
                // Do not block portrait upload if attribution service encounters an I/O issue
            }
        }

        $portraits = $this->getPortraitPayload($persona);

        return response()->json([
            'status'   => 'success',
            'message'  => lexicon('msg_PersonaPortrait_UploadSuccess', 'Persona portrait uploaded successfully.'),
            'data'     => $portraits,
            'redirect' => null,
        ], 200);
    }

    /**
     * Promote a gallery portrait to the primary portrait slot.
     */
    public function setPrimaryPersonaPortrait(Request $request, Persona $persona): JsonResponse
    {
        $this->authorizePortraitAction($persona);

        $validated = $request->validate([
            'attribute_id' => ['required', 'integer'],
        ]);

        $attribute = PersonaMetadata::where('persona_id', $persona->id)
            ->where('attribute_key', 'gallery_image')
            ->where('id', $validated['attribute_id'])
            ->firstOrFail();

        $oldPrimary = $persona->primary_portrait_url;
        $newPrimary = $attribute->attribute_value;

        $persona->update(['primary_portrait_url' => $newPrimary]);

        if (! empty($oldPrimary)) {
            $attribute->update(['attribute_value' => $oldPrimary]);
        } else {
            $attribute->delete();
        }

        $portraits = $this->getPortraitPayload($persona);

        return response()->json([
            'status'   => 'success',
            'message'  => lexicon('msg_PersonaPortrait_PrimaryUpdated', 'Primary persona portrait updated.'),
            'data'     => $portraits,
            'redirect' => null,
        ], 200);
    }

    /**
     * Delete a primary or gallery portrait from disk and database.
     */
    public function deletePersonaPortrait(Request $request, Persona $persona): JsonResponse
    {
        $this->authorizePortraitAction($persona);

        $validated = $request->validate([
            'type'         => ['required', 'in:primary,gallery'],
            'attribute_id' => ['required_if:type,gallery', 'nullable', 'integer'],
        ]);

        if ($validated['type'] === 'primary') {
            $filename = $persona->primary_portrait_url;

            if ($filename && ! Str::startsWith($filename, ['http://', 'https://'])) {
                $filePath = public_path('images/personas/' . ltrim($filename, '/'));
                if (File::exists($filePath)) {
                    File::delete($filePath);
                }
            }

            $nextGalleryAttr = PersonaMetadata::where('persona_id', $persona->id)
                ->where('attribute_key', 'gallery_image')
                ->orderBy('id', 'asc')
                ->first();

            if ($nextGalleryAttr) {
                $persona->update(['primary_portrait_url' => $nextGalleryAttr->attribute_value]);
                $nextGalleryAttr->delete();
            } else {
                $persona->update(['primary_portrait_url' => null]);
            }
        } else {
            $attribute = PersonaMetadata::where('persona_id', $persona->id)
                ->where('attribute_key', 'gallery_image')
                ->where('id', $validated['attribute_id'])
                ->firstOrFail();

            $filename = $attribute->attribute_value;

            if ($filename && ! Str::startsWith($filename, ['http://', 'https://'])) {
                $filePath = public_path('images/personas/' . ltrim($filename, '/'));
                if (File::exists($filePath)) {
                    File::delete($filePath);
                }
            }

            $attribute->delete();
        }

        $portraits = $this->getPortraitPayload($persona);

        return response()->json([
            'status'   => 'success',
            'message'  => lexicon('msg_PersonaPortrait_Deleted', 'Persona portrait removed.'),
            'data'     => $portraits,
            'redirect' => null,
        ], 200);
    }

    /**
     * Evaluate whether a Folio requires a mature content shield gate.
     */
    protected function evaluateContentShield(Folio $folio): array
    {
        $isMature = (
            $folio->content_tier_l >= 3 ||
            $folio->content_tier_s >= 3 ||
            $folio->content_tier_v >= 3
        );

        $user = auth()->user();

        $bypassMode = Configuration::where('configuration_token', 'content_shield_bypass')
            ->value('configuration_payload') ?? 'locked';

        $userBypassed = false;
        if ($isMature && $user && $bypassMode === 'user_bypass') {
            $userBypassed = (bool) $user->content_shield_bypass;
        }

        return [
            'is_mature'     => $isMature,
            'requires_gate' => $isMature && ! $userBypassed,
        ];
    }
}