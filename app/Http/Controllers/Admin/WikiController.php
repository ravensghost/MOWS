<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WikiArticle;
use App\Models\Page;
use Illuminate\Http\Request;
use Stevebauman\Purify\Facades\Purify;

class WikiController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()?->can('manage_wiki'), 403, 'Unauthorised access.');

        $systemPage = Page::where('slug', 'admin/wiki')
            ->where('is_published', true)
            ->with(['layout.blocks', 'widgets' => function ($query) {
                $query->orderBy('page_widget.sort_order');
            }])
            ->firstOrFail();

        $articles = WikiArticle::orderBy('category')->orderBy('title')->get();

        return view('admin.wiki', compact('systemPage', 'articles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'      => 'required|string|max:255',
            'category'   => 'required|string|max:255',
            'content'    => 'required|string',
            'is_visible' => 'boolean',
        ]);

        $validated['content'] = Purify::clean($validated['content']);
        $validated['slug'] = \Illuminate\Support\Str::slug($validated['title']);

        WikiArticle::create($validated);

        return response()->json(['status' => 'success', 'message' => 'Wiki article created successfully.']);
    }

    public function update(Request $request, WikiArticle $wikiArticle)
    {
        $validated = $request->validate([
            'title'      => 'required|string|max:255',
            'category'   => 'required|string|max:255',
            'content'    => 'required|string',
            'is_visible' => 'boolean',
        ]);

        $validated['content'] = Purify::clean($validated['content']);
        $wikiArticle->update($validated);

        return response()->json(['status' => 'success', 'message' => 'Wiki article updated successfully.']);
    }

    public function destroy(WikiArticle $wikiArticle)
    {
        $wikiArticle->delete();
        return response()->json(['status' => 'success', 'message' => 'Wiki article deleted successfully.']);
    }
}
