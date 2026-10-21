<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Page;
use App\Models\Chapter;
use App\Models\Character;
use App\Models\ContentRating;
use Illuminate\Http\Request;
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

            // Calculate word count delta based on previous content state
            $previousWordCount = str_word_count(strip_tags($chapter->content));
            $wordDelta = max(0, $currentWordCount - $previousWordCount);

            // Snapshot previous content if it changed during manual save
            if ($chapter->content !== $validated['content']) {
                \App\Models\ChapterVersion::create([
                    'chapter_id' => $chapter->id,
                    'user_id'    => $userId,
                    'content'    => $chapter->content,
                ]);
            }

            // Release any editor lock on save
            $validated['locked_by_user_id'] = null;
            $validated['locked_at'] = null;
            $chapter->update($validated);
        } else {
            $validated['author_user_id'] = $userId;
            $chapter = Chapter::create($validated);
            $wordDelta = $currentWordCount; // Full count for initial creation
        }

        // Record or update the user's running word contribution tally
        if ($userId && $wordDelta > 0) {
            \App\Models\ChapterUserContribution::updateOrCreate(
                ['chapter_id' => $chapter->id, 'user_id' => $userId],
                ['word_count' => \DB::raw("word_count + {$wordDelta}")]
            );
        }

        $resolvedCharacterIds = $this->resolveCharacterIds($rawCharacterIds);
        $chapter->characters()->sync($resolvedCharacterIds);

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

        $chapterId = $chapter?->id ?? $request->input('chapter_id');
        if ($chapterId) {
            $existingChapter = Chapter::find($chapterId);
            if ($existingChapter && !$existingChapter->isAccessibleBy(auth()->user())) {
                return response()->json(['status' => 'unauthorised'], 403);
            }
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

        // Allow co-authors or current locker to bypass the lock block
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
            'locked_at'         => window_timestamp_safe ?? now(),
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
            // Dispatch internal PM logic
            \App\Models\Thread::createSystemMessage($sender, [$locker], 'Are you finished with Chapter #' . $chapter->id . '?');
        } elseif ($channel === 'email' && !empty($locker->email)) {
            // Dispatch notification mailable
            \Illuminate\Support\Facades\Mail::to($locker->email)->send(new \App\Mail\LockPingMail($chapter, $sender));
        } elseif ($channel === 'discord') {
            // Dispatch Discord webhook / mention
            // Integration via Phase 5 notification pipeline
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Ping successfully sent to ' . $locker->name . '.'
        ]);
    }
}
