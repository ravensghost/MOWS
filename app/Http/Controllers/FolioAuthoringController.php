<?php

namespace App\Http\Controllers;

use App\Mail\LockPingMail;
use App\Models\AuthorLedger;
use App\Models\ContentTier;
use App\Models\Folio;
use App\Models\FolioRevision;
use App\Models\Persona;
use App\Models\User;
use App\Models\UserAttribute;
use App\Models\Volume;
use App\Services\AiTranslationService;
use App\Services\MultiChannelNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

class FolioAuthoringController extends Controller
{
    public function createFolio(Request $request)
    {
        $activeBooks = Volume::whereHas('publicationState', function ($q) {
            $q->where('book_url', 'active');
        })->with('shelf')->orderBy('volume_sort_order')->get();

        $allCharacters = Persona::where('persona_status', 'active')->orderBy('persona_name')->get();
        $languageRatings = ContentTier::where('tier_category', 'Language')->orderBy('tier_sort_order')->get();
        $sexRatings = ContentTier::where('tier_category', 'Sex')->orderBy('tier_sort_order')->get();
        $violenceRatings = ContentTier::where('tier_category', 'Violence')->orderBy('tier_sort_order')->get();

        if ($request->wantsJson()) {
            return response()->json([
                'status'   => 'success',
                'message'  => lexicon('msg_WriteChapter_FormLoaded', 'Folio authoring payload loaded.'),
                'data'     => [
                    'activeBooks'     => $activeBooks,
                    'allCharacters'   => $allCharacters,
                    'languageRatings' => $languageRatings,
                    'sexRatings'      => $sexRatings,
                    'violenceRatings' => $violenceRatings,
                ],
                'redirect' => null,
            ], 200);
        }

        return redirect()->route('dashboard');
    }

    public function editFolio(Folio $folio)
    {
        if (! $folio->isAccessibleBy(auth()->user())) {
            abort(403, lexicon('msg_WriteChapter_Unauthorised', 'Unauthorised access to this draft folio.'));
        }

        $folio->load('personae:id,persona_name');
        $characterIds = $folio->personae->pluck('id')->map(fn ($id) => (string) $id)->values();

        $payload = [
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
            'character_ids'     => $characterIds,
        ];

        return response()->json(array_merge([
            'status'   => 'success',
            'message'  => lexicon('msg_WriteChapter_Loaded', 'Folio loaded.'),
            'data'     => $payload,
            'redirect' => null,
        ], $payload), 200);
    }

    public function storeFolio(Request $request)
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

        $rawHtml = $validated['folio_text'];
        if (str_contains($rawHtml, 'mows-lexicon-tag') && class_exists(AiTranslationService::class)) {
            try {
                $rawHtml = app(AiTranslationService::class)->bakeFolioDialectTags($rawHtml);
            } catch (Throwable) {
                // Keep raw HTML if dialect baking fails
            }
        }

        $cleanText = function_exists('purify_html') ? purify_html($rawHtml) : $rawHtml;

        $rawCharacterIds = $validated['character_ids'] ?? [];
        $folioId = $validated['folio_id'] ?? null;
        unset($validated['character_ids'], $validated['folio_id']);

        $userId = auth()->id();
        $resolvedCharacterIds = $this->resolveCharacterIds($rawCharacterIds);

        // Consolidate scene-splitting logic (<hr> breaks split into sequential folios)
        $scenes = preg_split('/<hr\s*\/?>/i', $cleanText) ?: [];
        $scenes = array_values(array_filter(array_map('trim', $scenes), fn ($scene) => trim(strip_tags($scene)) !== ''));

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
                    $previousWordCount = str_word_count(strip_tags((string) $folio->folio_text));
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
            $authorHandle = (string) (auth()->user()?->name ?? 'Another writer');

            foreach ($taggedCharacters as $char) {
                if ($char->user_id && (int) $char->user_id !== (int) $userId && $char->user) {
                    $discordId = UserAttribute::where('user_id', $char->user_id)
                        ->where('attribute_key', 'discord_id')
                        ->value('attribute_value');

                    $recipientHandle = (string) ($char->user->name ?? 'Writer');
                    $userMention = ! empty($discordId) ? "<@{$discordId}>" : $recipientHandle;
                    $folioUrl = url('/dashboard');
                    $folioLink = "[Folio #{$primaryFolio->id}]({$folioUrl})";

                    MultiChannelNotificationService::sendDiscord(
                        'alerts',
                        "🏷️ **Writer Tagged:** Persona **{$char->persona_name}** ({$userMention}) was added as present in {$folioLink} (*{$primaryFolio->scene_location}*) by {$authorHandle}."
                    );
                }
            }
        }

        if ($primaryFolio && $primaryFolio->folio_status === 'published') {
            MultiChannelNotificationService::sendDiscord(
                'folios',
                "Folio #{$primaryFolio->id} ({$primaryFolio->folio_mode}) located at *{$primaryFolio->scene_location}* is now live."
            );

            MultiChannelNotificationService::sendPbemDispatch(
                "Published Folio #{$primaryFolio->id}: {$primaryFolio->scene_location}",
                (string) $primaryFolio->folio_text,
                [
                    'persona'  => $primaryFolio->activePersona?->persona_name ?? 'Unknown Persona',
                    'location' => $primaryFolio->scene_location,
                    'ic_date'  => "Day {$primaryFolio->ic_day} @ {$primaryFolio->ic_time}",
                ]
            );
        }

        return response()->json([
            'status'   => 'success',
            'message'  => lexicon('msg_WriteChapter_SavedSuccess', 'Folio successfully saved!'),
            'data'     => $primaryFolio,
            'redirect' => '/dashboard',
        ], 200);
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
            return response()->json([
                'status'   => 'waiting_for_volume',
                'message'  => lexicon('msg_WriteChapter_WaitingForVolume', 'Waiting for volume selection.'),
                'data'     => null,
                'redirect' => null,
            ], 200);
        }

        $rawCharacterIds = $validated['character_ids'] ?? [];
        $folioId = $folio?->id ?? ($validated['folio_id'] ?? null);
        unset($validated['character_ids'], $validated['folio_id']);

        $validated['folio_text'] = function_exists('purify_html')
            ? purify_html($validated['folio_text'])
            : $validated['folio_text'];
        $validated['folio_mode'] = $validated['folio_mode'] ?? 'mission';
        $validated['ic_day'] = $validated['ic_day'] ?? 1;
        $validated['ic_time'] = $validated['ic_time'] ?? '0800';
        $validated['content_tier_l'] = $validated['content_tier_l'] ?? 0;
        $validated['content_tier_s'] = $validated['content_tier_s'] ?? 0;
        $validated['content_tier_v'] = $validated['content_tier_v'] ?? 0;

        if ($folioId) {
            $existingFolio = Folio::find($folioId);
            if ($existingFolio && ! $existingFolio->isAccessibleBy(auth()->user())) {
                return response()->json([
                    'status'   => 'unauthorised',
                    'message'  => lexicon('msg_WriteChapter_Unauthorised', 'Unauthorised access to this draft folio.'),
                    'data'     => null,
                    'redirect' => null,
                ], 403);
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
            'status'   => 'success',
            'message'  => lexicon('msg_WriteChapter_AutoSaved', 'Draft auto-saved successfully.'),
            'data'     => $folio,
            'redirect' => null,
        ], 200);
    }

    protected function resolveCharacterIds(array $rawCharacterIds): array
    {
        $resolved = [];
        foreach ($rawCharacterIds as $item) {
            if (is_numeric($item)) {
                $resolved[] = (int) $item;
            } elseif (trim((string) $item) !== '') {
                $npc = Persona::firstOrCreate(
                    ['persona_name' => trim((string) $item)],
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
            ->map(fn ($id) => (int) $id)
            ->toArray();

        $isCoAuthor = in_array((int) $userId, $coAuthorUserIds, true) || (int) $folio->user_id === (int) $userId;

        if ($folio->locked_by_user_id && (int) $folio->locked_by_user_id !== (int) $userId && ! $isCoAuthor) {
            if ($folio->locked_at && $folio->locked_at->diffInMinutes(now()) < 10) {
                $lockedUser = $folio->lockedBy;
                $lockedHandle = (string) ($lockedUser?->name ?? 'Another writer');

                return response()->json([
                    'status'    => 'locked',
                    'message'   => lexicon('msg_WriteChapter_Locked', 'This folio is currently locked.'),
                    'data'      => null,
                    'redirect'  => null,
                    'locked_by' => $lockedHandle,
                ], 423);
            }
        }

        $folio->update(['locked_by_user_id' => $userId, 'locked_at' => now()]);

        return response()->json([
            'status'   => 'success',
            'message'  => lexicon('msg_WriteChapter_LockAcquired', 'Folio lock acquired.'),
            'data'     => $folio,
            'redirect' => null,
        ], 200);
    }

    /**
     * Ping the writer currently holding the co-writing lock on a Folio.
     */
    public function pingLockWriter(Request $request, Folio $folio)
    {
        $requester = auth()->user();
        $lockHolder = $folio->lockedBy ?? ($folio->turnUser ?? $folio->author);

        if (! $lockHolder) {
            return response()->json([
                'status'   => 'error',
                'message'  => lexicon('msg_WriteChapter_NoLockHolder', 'No active lock holder found for this folio.'),
                'data'     => null,
                'redirect' => null,
            ], 404);
        }

        $requesterName = $requester?->name ?? 'A co-writer';
        $location = $folio->scene_location ?: "Folio #{$folio->id}";

        MultiChannelNotificationService::sendPm(
            $requester,
            [$lockHolder],
            "{$requesterName} sent a co-writing lock ping requesting access or a turn update on Folio #{$folio->id} ({$location}).",
            "Co-Writing Lock Ping: Folio #{$folio->id}"
        );

        if (! empty($lockHolder->email) && MultiChannelNotificationService::isChannelEnabled('email')) {
            try {
                Mail::to($lockHolder->email)->send(new LockPingMail($folio, $requester));
            } catch (Throwable) {
                // Fail gracefully if SMTP is unreachable
            }
        }

        $discordId = UserAttribute::where('user_id', $lockHolder->id)
            ->where('attribute_key', 'discord_id')
            ->value('attribute_value');
        $mention = ! empty($discordId) ? "<@{$discordId}>" : $lockHolder->name;

        MultiChannelNotificationService::sendDiscord(
            'alerts',
            "🔔 **Co-Writing Lock Ping:** {$mention}, **{$requesterName}** is ready to write on **Folio #{$folio->id}** (*{$location}*)."
        );

        return response()->json([
            'status'   => 'success',
            'message'  => lexicon('msg_WriteChapter_PingSent', 'Co-writer lock ping dispatched successfully.'),
            'data'     => ['notified_user_id' => $lockHolder->id],
            'redirect' => null,
        ], 200);
    }
}