<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EncyclopediaCodex;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Stevebauman\Purify\Facades\Purify;

class EncyclopediaCodexController extends Controller
{
    public function index()
    {
        abort_unless(
            auth()->user()?->can('manage_wiki'),
            403,
            lexicon('msg_Wiki_Unauthorised', 'Unauthorised access to the Encyclopedia Codex.')
        );

        $systemPage = Page::where('page_url', 'admin/wiki')
            ->where('is_published', true)
            ->with(['layout.blocks', 'widgets' => function ($query) {
                $query->orderBy('page_widget.sort_order');
            }])
            ->firstOrFail();

        $articles = EncyclopediaCodex::orderBy('article_category')
            ->orderBy('article_title')
            ->get();

        return view('admin.wiki', compact('systemPage', 'articles'));
    }

    public function store(Request $request)
    {
        abort_unless(
            auth()->user()?->can('manage_wiki'),
            403,
            lexicon('msg_Wiki_Unauthorised', 'Unauthorised access to the Encyclopedia Codex.')
        );

        $validated = $request->validate([
            'article_title'    => 'required|string|max:255',
            'article_category' => 'required|string|max:255',
            'article_text'     => 'required|string',
            'is_visible'       => 'boolean',
        ]);

        $validated['article_text'] = Purify::clean($validated['article_text']);
        $validated['article_url']  = Str::slug($validated['article_title']);

        EncyclopediaCodex::create($validated);

        return response()->json([
            'status'  => 'success',
            'message' => lexicon('msg_Wiki_CreatedSuccess', 'Wiki article created successfully.')
        ]);
    }

    public function update(Request $request, EncyclopediaCodex $encyclopediaCodex)
    {
        abort_unless(
            auth()->user()?->can('manage_wiki'),
            403,
            lexicon('msg_Wiki_Unauthorised', 'Unauthorised access to the Encyclopedia Codex.')
        );

        $validated = $request->validate([
            'article_title'    => 'required|string|max:255',
            'article_category' => 'required|string|max:255',
            'article_text'     => 'required|string',
            'is_visible'       => 'boolean',
        ]);

        $validated['article_text'] = Purify::clean($validated['article_text']);
        $validated['article_url']  = Str::slug($validated['article_title']);

        $encyclopediaCodex->update($validated);

        return response()->json([
            'status'  => 'success',
            'message' => lexicon('msg_Wiki_UpdatedSuccess', 'Wiki article updated successfully.')
        ]);
    }

    public function destroy(EncyclopediaCodex $encyclopediaCodex)
    {
        abort_unless(
            auth()->user()?->can('manage_wiki'),
            403,
            lexicon('msg_Wiki_Unauthorised', 'Unauthorised access to the Encyclopedia Codex.')
        );

        $encyclopediaCodex->delete();

        return response()->json([
            'status'  => 'success',
            'message' => lexicon('msg_Wiki_DeletedSuccess', 'Wiki article deleted successfully.')
        ]);
    }
}
