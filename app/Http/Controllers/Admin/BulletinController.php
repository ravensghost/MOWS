<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Bulletin;
use App\Models\Book;
use Stevebauman\Purify\Facades\Purify;

class BulletinController extends Controller
{
    public function index()
    {
        $systemPage = Book::with([
            'grid.gridModules',
            'module' => function ($query) {
                $query->orderBy('module_sort_order');
            }
        ])
            ->where('slug', 'admin/news')
            ->where('is_published', true)
            ->firstOrFail();

        $news = Bulletin::orderBy('created_at', 'desc')->get();

        return view('admin.news', compact('systemPage', 'news'));
    }

    public function store(Request $request)
    {
        // 1. Validate FIRST. If this fails, it stops execution immediately.
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        // 2. Create SECOND (only runs if validation passes)
        $news = Bulletin::create([
            'title' => $validated['title'],
            'content' => Purify::clean($validated['content']),
            'author_id' => $request->user()->id,
            'published_at' => now(),
        ]);

        // 3. Push to Discord THIRD (only runs if creation succeeds)
        \App\Services\NotificationService::sendDiscord(
            'news',
            "📰 **New Station Announcement!**\n**{$news->title}**\nCheck out the latest broadcast on the station."
        );

        return response()->json(['success' => true]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'title'   => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $validated['content'] = Purify::clean($validated['content']);
        Bulletin::where('id', $id)->update($validated);

        return response()->json(['success' => true]);
    }

    public function destroy($id)
    {
        Bulletin::where('id', $id)->delete();
        return response()->json(['success' => true]);
    }
}
