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

    public function storeChapter(Request $request)
    {
        $validated = $request->validate([
            'book_id'             => 'required|exists:books,id',
            'active_character_id' => 'required|exists:characters,id',
            'character_ids'       => 'nullable|array',
            'character_ids.*'     => 'exists:characters,id',
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

        $validated['author_user_id'] = auth()->id();
        $validated['content'] = Purify::clean($validated['content']);

        $secondaryCharacters = $validated['character_ids'] ?? [];
        unset($validated['character_ids']);

        $chapter = Chapter::create($validated);

        if (!empty($secondaryCharacters)) {
            $chapter->characters()->sync($secondaryCharacters);
        }

        return response()->json([
            'status'   => 'success',
            'success'  => true,
            'message'  => lexicon('msg_WriteChapter_SavedSuccess', 'Chapter successfully saved!'),
            'redirect' => '/dashboard',
        ]);
    }
}
