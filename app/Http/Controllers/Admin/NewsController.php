<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\News;
use App\Models\Page;

class NewsController extends Controller
{
    public function index()
    {
        $systemPage = Page::with([
            'layout.blocks',
            'widgets' => function ($query) {
                $query->orderBy('page_widget.sort_order');
            }
        ])
            ->where('slug', 'admin/news')
            ->where('is_published', true)
            ->firstOrFail();

        $news = News::orderBy('created_at', 'desc')->get();

        return view('admin.news', compact('systemPage', 'news'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string'
        ]);

        News::create([
            'title' => $validated['title'],
            'content' => purify($validated['content']),
            'author_id' => $request->user()->id,
            'published_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string'
        ]);

        $validated['content'] = purify($validated['content']);
        News::where('id', $id)->update($validated);

        return response()->json(['success' => true]);
    }

    public function destroy($id)
    {
        News::where('id', $id)->delete();
        return response()->json(['success' => true]);
    }
}
