<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Bookcase;
use App\Models\Book;
use App\Models\Page;
use App\Models\BookStatus;

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
}
