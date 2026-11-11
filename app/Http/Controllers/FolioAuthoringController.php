<?php

namespace App\Http\Controllers;

use App\Mail\LockPingMail;
use App\Models\Volume;
use App\Models\Folio;
use App\Models\AuthorLedger;
use App\Models\FolioRevision;
use App\Models\Persona;
use App\Models\ContentTier;
use App\Models\Book;
use App\Models\UserAttribute;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Stevebauman\Purify\Facades\Purify;

class FolioAuthoringController extends Controller
{
    public function createChapter()
    {
        $activeBooks = Volume::whereHas('publicationState', function ($q) {
            $q->where('book_url', 'active');
        })->with('shelf')->orderBy('volume_sort_order')->get();

        $allCharacters = Persona::where('persona_status', 'active')->orderBy('persona_name')->get();

        $languageRatings = ContentTier::where('tier_category', 'Language')->orderBy('tier_sort_order')->get();
        $sexRatings = ContentTier::where('tier_category', 'Sex')->orderBy('tier_sort_order')->get();
        $violenceRatings = ContentTier::where('tier_category', 'Violence')->orderBy('tier_sort_order')->get();

        $systemPage = Book::where('book_url', 'chapters/create')
            ->where('is_published', true)
            ->with([
                'grid.gridModules',
                'module' => function ($q) {
                    $q->orderBy('book_modules.module_sort_order');
                }
            ])
            ->first();

        return view('chapter', compact(
            'activeBooks',
            'allCharacters',
            'languageRatings',
            'sexRatings',
            'violenceRatings',
            'systemPage'
        ));
    }

    public function editChapter(Folio $folio)
    {
        if (! $folio->isAccessibleBy(auth()->user())) {
            abort(403, 'Unauthorised access to this draft folio.');
        }

        $folio->load('personae:id,persona_name');

        return response()->json([
            'id'                => $folio->id,
            'volume_id'         => $folio->volume_id,
            'active_persona_id' => $folio->active_persona_id,
            'folio_mode'        => $folio->folio_mode,
            'ic_day'            => $folio->ic_day,
            'ic_time'           => $folio->ic_time,
            'scene_location'    => $folio->scene_location,
            'content_tier_l'    => $folio->content_tier_l,
            'content_tier_s'    => $folio->content_tier_s,
            'content_tier_v'    => $folio->content_tier_v,
            'folio_text'        => $folio->folio_text,
            'folio_status'      => $folio->folio_status,
            'character_ids'     => $folio->personae->pluck('id')->map(fn($id) => (string)$id)->values(),
        ]);
    }

    public function storeChapter(Request $request)
    {
        $validated = $request->validate([
            'folio_id'          => 'nullable|exists:folios,id',
            'volume_id'         => 'required|exists:volumes,id',
            'active_persona_id' => 'required|exists:personae,id',
            'character_ids'     => 'nullable|array',
            'character_ids.*'   => 'string',
            'folio_mode'        => 'required|in:mission,personal_log',
            'ic_day'            => 'required|integer',
            'ic_time'           => 'required|string',
            'scene_location'    => 'required|string',
            'content_tier_l'    => 'required|integer',
            'content_tier_s'    => 'required|integer',
            'content_tier_v'    => 'required|integer',
            'folio_text'        => 'required|string',
            'folio_status'      => 'required|in:draft,pending_approval,published',
        ]);

        $cleanText = Purify::clean($validated['folio_text']);
        $rawCharacterIds = $validated['character_ids'] ?? [];
        $folioId = $validated['folio_id'] ?? null;
        unset($validated['character_ids'], $validated['folio_id']);

        $userId = auth()->id();
        $resolvedCharacterIds = $this->resolveCharacterIds($rawCharacterIds);

        // Consolidate scene-splitting logic (<hr> breaks split into sequential folios)
        $scenes = preg_split('/<hr\s*\/?>/i', $cleanText);
        $scenes = array_values(array_filter(array_map('trim', $scenes), fn($scene) => strip_tags($scene) !== ''));

        if (empty($scenes)) {
            $scenes = [$cleanText];
        }

        $primaryFolio = null;
        $attachedCharacterIds = [];

        DB::transaction(function () use (
            $scenes,
            $validated,
            $folioId,
            $userId,
            $resolvedCharacterIds,
            &$primaryFolio,
            &$attachedCharacterIds
        ) {
            foreach ($scenes as $index => $sceneHtml) {
                $payload = $validated;
                $payload['folio_text'] = $sceneHtml;
                $currentWordCount = str_word_count(strip_tags($sceneHtml));

                if ($index === 0 && $folioId) {
                    $folio = Folio::findOrFail($folioId);

                    $previousWordCount = str_word_count(strip_tags($folio->folio_text));
                    $wordDelta = max(0, $currentWordCount - $previousWordCount);

                    if ($folio->folio_text !== $sceneHtml) {
                        FolioRevision::create([
                            'folio_id'      => $folio->id,
                            'user_id'       => $userId,
                            'revision_text' => $folio->folio_text,
                        ]);
                    }

                    $payload['locked_by_user_id'] = null;
                    $payload['locked_at'] = null;
                    $folio->update($payload);
                } else {
                    $maxSortOrder = Folio::where('volume_id', $payload['volume_id'])->max('folio_sort_order') ?? 0;
                    $payload['user_id'] = $userId;
                    $payload['folio_sort_order'] = $maxSortOrder + 1;
                    $folio = Folio::create($payload);
                    $wordDelta = $currentWordCount;
                }

                if ($userId && $wordDelta > 0) {
                    AuthorLedger::updateOrCreate(
                        ['folio_id' => $folio->id, 'user_id' => $userId],
                        ['contribution_word_count' => DB::raw("contribution_word_count + {$wordDelta}")]
                    );
                }

                $syncResults = $folio->personae()->sync($resolvedCharacterIds);

                if ($index === 0) {
                    $primaryFolio = $folio;
                    $attachedCharacterIds = $syncResults['attached'] ?? [];
                }
            }
        });

        if (! empty($attachedCharacterIds) && $primaryFolio) {
            $taggedCharacters = Persona::whereIn('id', $attachedCharacterIds)->with('user')->get();

            foreach ($taggedCharacters as $char) {
                if ($char->user_id && (int)$char->user_id !== (int) $userId && $char->user) {
                    $discordId = UserAttribute::where('user_id', $char->user_id)
                        ->where('key', 'discord_id')
                        ->value('value');

                    $userMention = ! empty($discordId) ? "<@{$discordId}>" : $char->user->name;
                    $folioUrl = url('/dashboard');
                    $folioLink = "[Folio #{$primaryFolio->id}]({$folioUrl})";

                    NotificationService::sendDiscord(
                        'alerts',
                        "🏷️ **Writer Tagged:** Persona **{$char->persona_name}** ({$userMention}) was added as present in {$folioLink} (*{$primaryFolio->scene_location}*) by " . auth()->user()->name . "."
                    );
                }
            }
        }

        if ($primaryFolio && $primaryFolio->folio_status === 'published') {
            NotificationService::sendDiscord(
                'chapters',
                "Folio #{$primaryFolio->id} ({$primaryFolio->folio_mode}) located at *{$primaryFolio->scene_location}* is now live."
            );
        }

        return response()->json([
            'status'   => 'success',
            'success'  => true,
            'folio_id' => $primaryFolio?->id,
            'message'  => lexicon('msg_WriteChapter_SavedSuccess', 'Folio successfully saved!'),
            'redirect' => '/dashboard',
        ]);
    }

    public function autoSave(Request $request, ?Folio $folio = null)
    {
        $validated = $request->validate([
            'folio_id'          => 'nullable|exists:folios,id',
            'volume_id'         => 'nullable|exists:volumes,id',
            'active_persona_id' => 'nullable|exists:personae,id',
            'character_ids'     => 'nullable|array',
            'character_ids.*'   => 'string',
            'folio_mode'        => 'nullable|in:mission,personal_log',
            'ic_day'            => 'nullable|integer',
            'ic_time'           => 'nullable|string',
            'scene_location'    => 'nullable|string',
            'content_tier_l'    => 'nullable|integer',
            'content_tier_s'    => 'nullable|integer',
            'content_tier_v'    => 'nullable|integer',
            'folio_text'        => 'required|string',
            'folio_status'      => 'nullable|in:draft,pending_approval,published',
        ]);

        if (empty($validated['volume_id'])) {
            return response()->json(['status' => 'waiting_for_volume'], 200);
        }

        $rawCharacterIds = $validated['character_ids'] ?? [];
        $folioId = $folio?->id ?? ($validated['folio_id'] ?? null);
        unset($validated['character_ids'], $validated['folio_id']);

        $validated['folio_text'] = Purify::clean($validated['folio_text']);
        $validated['folio_mode'] = $validated['folio_mode'] ?? 'mission';
        $validated['ic_day'] = $validated['ic_day'] ?? 1;
        $validated['ic_time'] = $validated['ic_time'] ?? '0800';
        $validated['content_tier_l'] = $validated['content_tier_l'] ?? 0;
        $validated['content_tier_s'] = $validated['content_tier_s'] ?? 0;
        $validated['content_tier_v'] = $validated['content_tier_v'] ?? 0;

        if ($folioId) {
            $existingFolio = Folio::find($folioId);
            if ($existingFolio && !$existingFolio->isAccessibleBy(auth()->user())) {
                return response()->json(['status' => 'unauthorised'], 403);
            }
            $folio = $existingFolio;
            $folio->update($validated);
        } else {
            $validated['user_id'] = auth()->id();
            $validated['folio_status'] = 'draft';
            $folio = Folio::create($validated);
        }

        if (! empty($rawCharacterIds)) {
            $resolvedCharacterIds = $this->resolveCharacterIds($rawCharacterIds);
            $folio->personae()->sync($resolvedCharacterIds);
        }

        return response()->json([
            'status'    => 'success',
            'success'   => true,
            'folio_id'  => $folio->id,
            'timestamp' => now()->toTimeString(),
            'message'   => 'Draft auto-saved successfully.'
        ]);
    }

    protected function resolveCharacterIds(array $rawCharacterIds): array
    {
        $resolved = [];
        foreach ($rawCharacterIds as $item) {
            if (is_numeric($item)) {
                $resolved[] = (int)$item;
            } elseif (trim($item) !== '') {
                $npc = Persona::firstOrCreate(
                    ['persona_name' => trim($item)],
                    ['persona_mode' => 'NPC', 'persona_status' => 'active', 'user_id' => null]
                );
                $resolved[] = $npc->id;
            }
        }
        return $resolved;
    }

    public function acquireLock(Folio $folio)
    {
        $userId = auth()->id();
        $coAuthorUserIds = $folio->personae()
            ->whereNotNull('user_id')
            ->pluck('user_id')
            ->map(fn($id) => (int)$id)
            ->toArray();

        $isCoAuthor = in_array((int) $userId, $coAuthorUserIds, true);

        if ($folio->locked_by_user_id && (int)$folio->locked_by_user_id !== (int) $userId && !$isCoAuthor) {
            if ($folio->locked_at && $folio->locked_at->diffInMinutes(now()) < 10) {
                $lockedUser = $folio->lockedBy;
                return response()->json([
                    'status'    => 'locked',
                    'locked_by' => $lockedUser->name ?? 'Another writer',
                    'message'   => 'This folio is currently locked.'
                ], 423);
            }
        }

        $folio->update(['locked_by_user_id' => $userId, 'locked_at' => now()]);
        return response()->json(['status' => 'success']);
    }
}
