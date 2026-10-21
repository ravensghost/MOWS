<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Bookcase;
use App\Models\Book;
use App\Models\Page;
use App\Models\Chapter;
use App\Models\ChapterVersion;
use App\Models\BookStatus;
use App\Models\ChapterUserContribution;

class StoryController extends Controller
{
    public function storyManager()
    {
        $systemPage = Page::where('slug', 'admin/story')
            ->where('is_published', true)
            ->with(['layout.blocks', 'widgets' => function ($query) {
                $query->orderBy('page_widget.sort_order');
            }])
            ->firstOrFail();

        $bookcases = Bookcase::with([
            'books' => function ($q) {
                $q->select('id', 'bookcase_id', 'status_id', 'title', 'description', 'ic_day', 'ic_time', 'sort_order')
                    ->orderBy('sort_order')
                    ->orderBy('title');
            },
            'books.chapters' => function ($q) {
                $q->select('id', 'book_id', 'chapter_type', 'ic_day', 'ic_time', 'rating_l', 'rating_s', 'rating_v', 'sort_order', 'created_at')
                    ->orderBy('sort_order')
                    ->orderBy('created_at', 'desc');
            },
            'books.status:id,name,slug'
        ])
            ->select('id', 'name', 'sort_order')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $looseBooks = Book::whereNull('bookcase_id')
            ->with([
                'chapters' => function ($q) {
                    $q->select('id', 'book_id', 'chapter_type', 'ic_day', 'ic_time', 'rating_l', 'rating_s', 'rating_v', 'sort_order', 'created_at')
                        ->orderBy('sort_order')
                        ->orderBy('created_at', 'desc');
                },
                'status:id,name,slug'
            ])
            ->select('id', 'status_id', 'title', 'description', 'ic_day', 'ic_time', 'sort_order')
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();

        $statuses = BookStatus::select('id', 'name', 'slug')->orderBy('sort_order')->get();

        return view('admin.story', compact('systemPage', 'bookcases', 'looseBooks', 'statuses'));
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

        DB::transaction(function () use ($validated, $rawCharacterIds, $chapterId, $userId, &$chapter) {

            // 1. Check for scene split markers in the content string
            $contentParts = preg_split('/<hr[^>]*class=["\']?[^"\']*scene-split-marker[^"\']*["\']?[^>]*>/i', $validated['content']);

            // Primary content block is the first part
            $validated['content'] = trim($contentParts[0]);
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

            // Record word contributions tally
            if ($userId && $wordDelta > 0) {
                ChapterUserContribution::updateOrCreate(
                    ['chapter_id' => $chapter->id, 'user_id' => $userId],
                    ['word_count' => DB::raw("word_count + {$wordDelta}")]
                );
            }

            $resolvedCharacterIds = $this->resolveCharacterIds($rawCharacterIds);
            $chapter->characters()->sync($resolvedCharacterIds);

            // 2. If additional split parts exist, automatically generate sequential child chapters
            if (count($contentParts) > 1) {
                array_shift($contentParts); // Remove first part already assigned to main chapter
                $sortOrderIncrement = $chapter->sort_order;

                foreach ($contentParts as $index => $partContent) {
                    $partContent = trim($partContent);
                    if (empty($partContent)) continue;

                    $sortOrderIncrement++;
                    $partWordCount = str_word_count(strip_tags($partContent));

                    $childChapter = Chapter::create([
                        'book_id'             => $chapter->book_id,
                        'author_user_id'      => $userId,
                        'active_character_id' => $chapter->active_character_id,
                        'chapter_type'        => $chapter->chapter_type,
                        'ic_day'              => $chapter->ic_day,
                        'ic_time'             => $chapter->ic_time,
                        'location'            => $chapter->location,
                        'rating_l'            => $chapter->rating_l,
                        'rating_s'            => $chapter->rating_s,
                        'rating_v'            => $chapter->rating_v,
                        'content'             => $partContent,
                        'status'              => $chapter->status,
                        'sort_order'          => $sortOrderIncrement,
                    ]);

                    $childChapter->characters()->sync($resolvedCharacterIds);

                    if ($userId && $partWordCount > 0) {
                        ChapterUserContribution::create([
                            'chapter_id' => $childChapter->id,
                            'user_id'    => $userId,
                            'word_count' => $partWordCount,
                        ]);
                    }
                }
            }
        });

        return response()->json([
            'status'     => 'success',
            'success'    => true,
            'chapter_id' => $chapter->id,
            'message'    => lexicon('msg_WriteChapter_SavedSuccess', 'Chapter successfully saved and split parts generated!'),
            'redirect'   => '/dashboard',
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $status = BookStatus::findOrFail($id);
        $status->update([
            'name' => $validated['name'],
            'slug' => \Illuminate\Support\Str::slug($validated['name']),
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $status,
            'message' => 'Book status updated successfully.'
        ]);
    }

    public function destroyStatus($id)
    {
        $status = BookStatus::findOrFail($id);
        $status->delete();

        return response()->json([
            'status' => 'success',
            'data' => null,
            'message' => 'Book status deleted successfully.'
        ]);
    }
}
