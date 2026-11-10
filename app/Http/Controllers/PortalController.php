<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\View;
use App\Models\Book;
use App\Models\Module;
use App\Models\Grid;
use App\Models\GridModule;
use App\Models\Bulletin;
use App\Models\Folio;
use App\Models\Persona;
use App\Models\PersonaMetadata;
use App\Models\Configuration;

class PortalController extends Controller
{
    public function showBook($slug = '/')
    {
        $slug = $slug === '/' ? '/' : ltrim($slug, '/');

        $systemPage = Book::where('book_url', $slug)
            ->where('is_published', true)
            ->with([
                'grid.gridModules',
                'module' => function ($query) {
                    $query->wherePivot('is_visible', true)
                        ->orderBy('module_sort_order');
                }
            ])
            ->firstOrFail();

        $user = auth()->user();
        $systemPage->setRelation('module', ($systemPage->module ?? collect())->filter(function ($widget) use ($user) {
            if (!($widget->pivot->is_visible ?? true)) {
                return false;
            }
            $required = $widget->pivot->access_clearance;
            if (empty($required)) {
                return true;
            }
            return $user ? $user->can($required) : false;
        })->values());

        $lookupUrl = $systemPage->book_url === '/' ? '/' : '/' . ltrim($systemPage->book_url, '/');

        $navKey = Cache::remember('nav_lexicon_' . md5($lookupUrl), 3600, function () use ($lookupUrl) {
            return DB::table('table_of_contents')
                ->where('destination_url', $lookupUrl)
                ->value('translation_code_key');
        });

        $pageKey = $navKey ?: ('public_label_' . ($systemPage->book_url === '/' ? 'Landing' : Str::studly(basename($systemPage->book_url))));

        $data = [
            'page'       => $systemPage,
            'systemPage' => $systemPage,
            'navKey'     => $navKey,
            'pageKey'    => $pageKey,
            'newsItems'  => null,
            'storyPosts' => null,
        ];

        $widgetKeys = $systemPage->module->pluck('module_token')->toArray();

        if (in_array('news', $widgetKeys) || in_array('widget_news', $widgetKeys)) {
            $data['newsItems'] = Bulletin::with('author')
                ->orderBy('published_at', 'desc')
                ->take(5)
                ->get();
        }

        if (in_array('posts', $widgetKeys) || in_array('widget_posts', $widgetKeys)) {
            $data['storyPosts'] = Folio::with('activePersona')
                ->where('folio_status', 'published')
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();
        }

        return view('page', $data);
    }

    public function showBio(Persona $persona)
    {
        $systemPage = Book::where('book_url', 'bio')
            ->where('is_published', true)
            ->with([
                'Grid.gridModules',
                'gridModules' => function ($query) {
                    $query->wherePivot('is_visible', true)
                        ->orderBy('grid_module_sort_order');
                }
            ])
            ->firstOrFail();

        $user = auth()->user();
        $systemPage->setRelation('GridModule', $systemPage->GridModule->filter(function ($module) use ($user) {
            if (!($module->pivot->is_visible ?? true)) {
                return false;
            }
            $required = $module->pivot->access_clearance;
            if (empty($required)) {
                return true;
            }
            return $user ? $user->can($required) : false;
        })->values());

        $persona->load([
            'user',
            'rank' => function ($query) {
                $query->where('is_visible', true)->with([
                    'rankSet' => function ($q) {
                        $q->where('is_visible', true);
                    }
                ]);
            },
            'position' => function ($query) {
                $query->where('is_visible', true)->with([
                    'section' => function ($q) {
                        $q->where('is_visible', true);
                    }
                ]);
            },
            'application.answers.formField',
            'serviceRecords.award',
            'serviceRecords.issuer',
            'attributes',
        ]);

        View::share('character', $persona);

        $lookupUrl = '/bio';
        $navKey = Cache::remember('nav_lexicon_' . md5($lookupUrl), 3600, function () use ($lookupUrl) {
            return DB::table('table_of_contents')
                ->where('destination_url', $lookupUrl)
                ->value('translation_code_key');
        });

        $pageKey = $navKey ?: 'public_label_Bio';

        $data = [
            'page'       => $systemPage,
            'systemPage' => $systemPage,
            'pageKey'    => $pageKey,
            'character'  => $persona,
            'newsItems'  => null,
            'storyPosts' => null,
        ];

        return view('page', $data);
    }

    protected function authorizeAvatarAction(Persona $persona): void
    {
        $user = auth()->user();

        $isAuthorized = $user && (
            (int) $user->id === (int) $persona->user_id ||
            $user->hasRole('webmaster') ||
            $user->can('manage_roster_assignments') ||
            $user->can('manage_users')
        );

        abort_unless($isAuthorized, 403, lexicon('msg_CharacterAvatar_Unauthorised', 'Unauthorised to modify character portraits.'));
    }

    protected function getPortraitPayload(Persona $persona): array
    {
        $persona->refresh();
        $persona->load('attributes');

        $primaryUrl = $persona->primary_portrait_url
            ? (Str::startsWith($persona->primary_portrait_url, ['http://', 'https://', '/']) ? $persona->primary_portrait_url : asset('images/characters/' . $persona->primary_portrait_url))
            : null;

        $gallery = $persona->attributes
            ->where('attribute_key', 'gallery_image')
            ->values()
            ->map(function ($attr) {
                $val = $attr->attribute_value;
                return [
                    'id'       => $attr->id,
                    'filename' => $val,
                    'url'      => Str::startsWith($val, ['http://', 'https://', '/']) ? $val : asset('images/characters/' . $val),
                ];
            })
            ->all();

        return [
            'primary_filename' => $persona->primary_portrait_url,
            'primary_url'      => $primaryUrl,
            'gallery'          => $gallery,
        ];
    }

    public function uploadCharacterAvatar(Request $request, Persona $persona)
    {
        $this->authorizeAvatarAction($persona);

        $request->validate([
            'file' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120'],
        ]);

        $destinationPath = public_path('images/characters');
        if (!File::exists($destinationPath)) {
            File::makeDirectory($destinationPath, 0755, true);
        }

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension() ?: 'webp');
        $filename = 'char_' . $persona->id . '_' . time() . '_' . Str::lower(Str::random(5)) . '.' . $extension;

        $file->move($destinationPath, $filename);

        if (empty($persona->primary_portrait_url)) {
            $persona->update(['primary_portrait_url' => $filename]);
        } else {
            PersonaMetadata::create([
                'persona_id'    => $persona->id,
                'attribute_key'   => 'gallery_image',
                'attribute_value' => $filename,
            ]);
        }

        return response()->json([
            'message'   => lexicon('msg_CharacterAvatar_UploadSuccess', 'Character portrait uploaded successfully.'),
            'portraits' => $this->getPortraitPayload($persona),
        ]);
    }

    public function setPrimaryCharacterAvatar(Request $request, Persona $persona)
    {
        $this->authorizeAvatarAction($persona);

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

        if (!empty($oldPrimary)) {
            $attribute->update(['attribute_value' => $oldPrimary]);
        } else {
            $attribute->delete();
        }

        return response()->json([
            'message'   => lexicon('msg_CharacterAvatar_PrimaryUpdated', 'Primary character portrait updated.'),
            'portraits' => $this->getPortraitPayload($persona),
        ]);
    }

    public function deleteCharacterAvatar(Request $request, Persona $persona)
    {
        $this->authorizeAvatarAction($persona);

        $validated = $request->validate([
            'type'         => ['required', 'in:primary,gallery'],
            'attribute_id' => ['nullable', 'integer'],
        ]);

        if ($validated['type'] === 'primary') {
            $filename = $persona->primary_portrait_url;

            if ($filename && !Str::startsWith($filename, ['http://', 'https://'])) {
                $filePath = public_path('images/characters/' . ltrim($filename, '/'));
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

            if ($filename && !Str::startsWith($filename, ['http://', 'https://'])) {
                $filePath = public_path('images/characters/' . ltrim($filename, '/'));
                if (File::exists($filePath)) {
                    File::delete($filePath);
                }
            }

            $attribute->delete();
        }

        return response()->json([
            'message'   => lexicon('msg_CharacterAvatar_Deleted', 'Character portrait removed.'),
            'portraits' => $this->getPortraitPayload($persona),
        ]);
    }

    protected function evaluateContentShield(Folio $chapter): array
    {
        $isMature = ($chapter->rating_l >= 3 || $chapter->rating_s >= 3 || $chapter->rating_v >= 3);

        $user = auth()->user();
        $bypassMode = Configuration::where('configuration_token', 'content_shield_bypass')->value('configuration_payload') ?? 'locked';

        $userBypassed = false;
        if ($isMature && $user && $bypassMode === 'user_bypass') {
            $userBypassed = (bool) $user->content_shield_bypass;
        }

        return [
            'is_mature'     => $isMature,
            'requires_gate' => $isMature && !$userBypassed,
        ];
    }
}
