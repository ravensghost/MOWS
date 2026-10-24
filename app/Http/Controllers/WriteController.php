<?php

namespace App\Http\Controllers;

use App\Mail\LockPingMail;
use App\Models\Book;
use App\Models\Chapter;
use App\Models\ChapterUserContribution;
use App\Models\ChapterVersion;
use App\Models\Character;
use App\Models\ContentRating;
use App\Models\Page;
use App\Models\UserAttribute;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Stevebauman\Purify\Facades\Purify;

class WriteController extends Controller
{
    public function createChapter()
    {
        $activeBooks = Book::whereHas('status', function ($q) {
            $q->where('slug', 'active');
        })->with('bookcase')->orderBy('sort_order')->get();

        $allCharacters = Character::where('status', 'active')->orderBy('name')->get();
        $languageRatings = ContentRating::where('name', 'Language')->orderBy('sort_rating')->get();
        $sexRatings = ContentRating::where('name', 'Sex')->orderBy('sort_rating')->get();
        $violenceRatings = ContentRating::where('name', 'Violence')->orderBy('sort_rating')->get();

        $systemPage = Page::where('slug', 'chapters/create')
            ->where('is_published', true)
            ->with(['layout.blocks', 'widgets' => function ($q) {
                $q->orderBy('page_widget.sort_order');
            }])
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

    /**
     * Fetch chapter data as JSON for the chapter edit modal.
     */
    public function editChapter(Chapter $chapter)
    {
        if (!$chapter->isAccessibleBy(auth()->user())) {
            abort(403, 'Unauthorised access to this draft chapter.');
        }

        $chapter->load('characters:id,name');

        return response()->json([
            'id'                  => $chapter->id,
            'book_id'             => $chapter->book_id,
            'active_character_id' => $chapter->active_character_id,
            'chapter_type'        => $chapter->chapter_type,
            'ic_day'              => $chapter->ic_day,
            'ic_time'             => $chapter->ic_time,
            'location'            => $chapter->location,
            'rating_l'            => $chapter->rating_l,
            'rating_s'            => $chapter->rating_s,
            'rating_v'            => $chapter->rating_v,
            'content'             => $chapter->content,
            'status'              => $chapter->status,
            'character_ids'       => $chapter->characters->pluck('id')->map(fn($id) => (string) $id)->values(),
        ]);
    }

    public function storeChapter(Request $request)
    {
        $validated = $request->validate([
            'chapter_id'          => 'nullable|exists:chapters,id',
            'book_id'             => 'required|exists:books,id',
            'active_character_id' => 'required|exists:characters,id',
            'character_ids'       => 'nullable|array',
            'character_ids.*'     => 'string',
            'chapter_type'        => 'required|in:mission,personal_log',
            'ic_day'              => 'required|string',
            'ic_time'             => 'required|string',
            'location'            => 'required|string',
            'rating_l'            => 'required|integer',
            'rating_s'            => 'required|integer',
            'rating_v'            => 'required|integer',
            'content'             => 'required|string',
            'status'              => 'required|in:draft,published',
        ]);

        $validated['content'] = Purify::clean($validated['content']);
        $rawCharacterIds = $validated['character_ids'] ?? [];
        $chapterId = $validated['chapter_id'] ?? null;
        unset($validated['character_ids'], $validated['chapter_id']);

        $userId = auth()->id();
        $currentWordCount = str_word_count(strip_tags($validated['content']));

        if ($chapterId) {
            $chapter = Chapter::findOrFail($chapterId);

            $previousWordCount = str_word_count(strip_tags($chapter->content));
            $wordDelta = max(0, $currentWordCount - $previousWordCount);

            if ($chapter->content !== $validated['content']) {
                ChapterVersion::create([
                    'chapter_id' => $chapter->id,
                    'user_id'    => $userId,
                    'content'    => $chapter->content,
                ]);
            }

            $validated['locked_by_user_id'] = null;
            $validated['locked_at'] = null;
            $chapter->update($validated);
        } else {
            $validated['author_user_id'] = $userId;
            $chapter = Chapter::create($validated);
            $wordDelta = $currentWordCount;
        }

        if ($userId && $wordDelta > 0) {
            ChapterUserContribution::updateOrCreate(
                ['chapter_id' => $chapter->id, 'user_id' => $userId],
                ['word_count' => DB::raw("word_count + {$wordDelta}")]
            );
        }

        $resolvedCharacterIds = $this->resolveCharacterIds($rawCharacterIds);
        $syncResults = $chapter->characters()->sync($resolvedCharacterIds);
        $attachedCharacterIds = $syncResults['attached'] ?? [];

        // --- MULTI-CHANNEL ALERTS FOR NEWLY TAGGED CHARACTERS PRESENT ---
        if (!empty($attachedCharacterIds)) {
            $taggedCharacters = Character::whereIn('id', $attachedCharacterIds)->with('user')->get();

            foreach ($taggedCharacters as $char) {
                if ($char->user_id && $char->user_id !== auth()->id() && $char->user) {
                    $discordId = UserAttribute::where('user_id', $char->user_id)
                        ->where('key', 'discord_id')
                        ->value('value');

                    $userMention = !empty($discordId) ? "<@{$discordId}>" : $char->user->name;
                    $chapterUrl = url('/dashboard');
                    $chapterLink = "[Chapter #{$chapter->id}]({$chapterUrl})";

                    // 1. Discord Alert
                    NotificationService::sendDiscord(
                        'alerts',
                        "🏷️ **Writer Tagged:** Character **{$char->name}** ({$userMention}) was added as present in {$chapterLink} (*{$chapter->location}*) by " . auth()->user()->name . "."
                    );

                    // 2. On-Site PM Alert
                    NotificationService::sendPm(
                        auth()->user(),
                        [$char->user],
                        "Your character {$char->name} was tagged as present in Chapter #{$chapter->id} ({$chapter->location}).",
                        "Character Tagged: {$char->name}"
                    );

                    // 3. Email Alert
                    if (!empty($char->user->email)) {
                        NotificationService::sendEmail(
                            $char->user->email,
                            "Character Tagged in Chapter #{$chapter->id}",
                            'emails.notification',
                            [
                                'heading'     => 'Character Tagged in Chapter',
                                'messageText' => "Your character {$char->name} was added as present in Chapter #{$chapter->id} ({$chapter->location}) by " . auth()->user()->name . ".",
                                'actionUrl'   => $chapterUrl,
                                'actionLabel' => 'View Chapter on Dashboard',
                            ]
                        );
                    }
                }
            }
        }

        // --- PHASE 5: PUBLISHED CHAPTER NOTIFICATION PIPELINE ---
        if ($chapter->status === 'published') {
            // 1. Discord Webhook
            NotificationService::sendDiscord(
                'chapters',
                "Chapter #{$chapter->id} ({$chapter->chapter_type}) located at *{$chapter->location}* is now live."
            );

            $subscribers = $chapter->book?->subscribers ?? collect();

            // 2. On-Site PM Notification
            if ($subscribers->isNotEmpty()) {
                NotificationService::sendPm(
                    auth()->user(),
                    $subscribers,
                    'A new chapter has been published in ' . ($chapter->book->title ?? 'your book') . ' at ' . $chapter->location . '.',
                    'New Chapter Published: #' . $chapter->id
                );
            }

            // 3. Email Notification
            foreach ($subscribers as $subscriber) {
                if (!empty($subscriber->email)) {
                    NotificationService::sendEmail(
                        $subscriber->email,
                        'New Chapter Published: Chapter #' . $chapter->id,
                        'emails.chapter_published',
                        ['chapter' => $chapter]
                    );
                }
            }
        }

        return response()->json([
            'status'     => 'success',
            'success'    => true,
            'chapter_id' => $chapter->id,
            'message'    => lexicon('msg_WriteChapter_SavedSuccess', 'Chapter successfully saved!'),
            'redirect'   => '/dashboard',
        ]);
    }

    /**
     * Handle silent background draft auto-saves for both new and existing chapters.
     */
    public function autoSave(Request $request, Chapter $chapter = null)
    {
        $validated = $request->validate([
            'chapter_id'          => 'nullable|exists:chapters,id',
            'book_id'             => 'nullable|exists:books,id',
            'active_character_id' => 'nullable|exists:characters,id',
            'character_ids'       => 'nullable|array',
            'character_ids.*'     => 'string',
            'chapter_type'        => 'nullable|in:mission,personal_log',
            'ic_day'              => 'nullable|string',
            'ic_time'             => 'nullable|string',
            'location'            => 'nullable|string',
            'rating_l'            => 'nullable|integer',
            'rating_s'            => 'nullable|integer',
            'rating_v'            => 'nullable|integer',
            'content'             => 'required|string',
            'status'              => 'nullable|in:draft,published',
        ]);

        if (empty($validated['book_id'])) {
            return response()->json(['status' => 'waiting_for_book'], 200);
        }

        $rawCharacterIds = $validated['character_ids'] ?? [];
        $chapterId = $chapter?->id ?? ($validated['chapter_id'] ?? null);
        unset($validated['character_ids'], $validated['chapter_id']);

        $validated['content'] = Purify::clean($validated['content']);
        $validated['ic_day'] = $validated['ic_day'] ?? 1;
        $validated['ic_time'] = $validated['ic_time'] ?? '0800';
        $validated['rating_l'] = $validated['rating_l'] ?? 1;
        $validated['rating_s'] = $validated['rating_s'] ?? 1;
        $validated['rating_v'] = $validated['rating_v'] ?? 1;

        if ($chapterId) {
            $existingChapter = Chapter::find($chapterId);
            if ($existingChapter && !$existingChapter->isAccessibleBy(auth()->user())) {
                return response()->json(['status' => 'unauthorised'], 403);
            }
            $chapter = $existingChapter;
            $chapter->update($validated);
        } else {
            $validated['author_user_id'] = auth()->id();
            $validated['status'] = 'draft';
            $chapter = Chapter::create($validated);
        }

        if (!empty($rawCharacterIds)) {
            $resolvedCharacterIds = $this->resolveCharacterIds($rawCharacterIds);
            $chapter->characters()->sync($resolvedCharacterIds);
        }

        return response()->json([
            'status'     => 'success',
            'success'    => true,
            'chapter_id' => $chapter->id,
            'timestamp'  => now()->toTimeString(),
            'message'    => 'Draft auto-saved successfully.'
        ]);
    }

    /**
     * Resolve numeric character IDs or auto-create lightweight NPC records.
     */
    protected function resolveCharacterIds(array $rawCharacterIds): array
    {
        $resolvedCharacterIds = [];

        foreach ($rawCharacterIds as $item) {
            if (is_numeric($item)) {
                $resolvedCharacterIds[] = (int) $item;
            } elseif (trim($item) !== '') {
                $npc = Character::firstOrCreate(
                    ['name' => trim($item)],
                    [
                        'character_type' => 'NPC',
                        'status'         => 'active',
                        'user_id'        => null,
                    ]
                );
                $resolvedCharacterIds[] = $npc->id;
            }
        }

        return $resolvedCharacterIds;
    }

    /**
     * Acquire or verify exclusive editor write-lock.
     */
    public function acquireLock(Chapter $chapter)
    {
        $userId = auth()->id();

        $coAuthorUserIds = $chapter->characters()->whereNotNull('user_id')->pluck('user_id')->toArray();
        $isCoAuthor = in_array($userId, $coAuthorUserIds);

        if ($chapter->locked_by_user_id && $chapter->locked_by_user_id !== $userId && !$isCoAuthor) {
            if ($chapter->locked_at && $chapter->locked_at->diffInMinutes(now()) < 10) {
                $lockedUser = $chapter->lockedBy;
                return response()->json([
                    'status'    => 'locked',
                    'locked_by' => $lockedUser->name ?? 'Another writer',
                    'user_id'   => $lockedUser->id ?? null,
                    'email'     => $lockedUser->email ?? null,
                    'message'   => 'This chapter is currently locked by another writer.'
                ], 423);
            }
        }

        $chapter->update([
            'locked_by_user_id' => $userId,
            'locked_at'         => now(),
        ]);

        return response()->json(['status' => 'success']);
    }

    /**
     * Send an "Are You Finished?" ping to the user holding the lock.
     */
    public function pingLockWriter(Request $request, Chapter $chapter)
    {
        $validated = $request->validate([
            'channel' => 'required|in:pm,email,discord',
        ]);

        $locker = $chapter->lockedBy;
        if (!$locker) {
            return response()->json(['status' => 'error', 'message' => 'No active locker found.'], 404);
        }

        $sender = auth()->user();
        $channel = $validated['channel'];

        if ($channel === 'pm') {
            NotificationService::sendPm(
                $sender,
                [$locker],
                'Are you finished with Chapter #' . $chapter->id . '?',
                'Editor Lock Ping: Chapter #' . $chapter->id
            );
        } elseif ($channel === 'email' && !empty($locker->email)) {
            if (NotificationService::isChannelEnabled('email')) {
                Mail::to($locker->email)->send(new LockPingMail($chapter, $sender));
            }
        } elseif ($channel === 'discord') {
            $discordId = UserAttribute::where('user_id', $locker->id)
                ->where('key', 'discord_id')
                ->value('value');
            $mention = !empty($discordId) ? "<@{$discordId}>" : $locker->name;

            NotificationService::sendDiscord(
                'alerts',
                "🔔 **Chapter Editor Ping:** {$sender->name} is asking {$mention} if they're finished with Chapter #{$chapter->id} (*{$chapter->location}*)."
            );
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Ping successfully sent to ' . $locker->name . '.'
        ]);
    }
    public function aiChapterGenerate(Request $request, \App\Services\AiContentService $contentService)
    {
        $validated = $request->validate([
            'prompt'   => 'required|string',
            'npc_name' => 'nullable|string',
        ]);

        $content = $contentService->generateNpcDialogue(
            $validated['prompt'],
            $validated['npc_name'] ?? 'Security Chief'
        );

        return response()->json([
            'status'  => 'success',
            'content' => $content,
        ]);
    }
}
