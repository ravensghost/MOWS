<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\EncyclopediaCodex;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Stevebauman\Purify\Facades\Purify;

class EncyclopediaCodexController extends Controller
{
    public function index(): View
    {
        abort_unless(
            auth()->user()?->can('manage_wiki'),
            403,
            lexicon('msg_Wiki_Unauthorised', 'Unauthorised access to the Encyclopedia Codex.')
        );

        $systemBook = Book::forUrl('/admin/encyclopedia-codex')->publishedWithLayout()->firstOrFail();

        $articles = EncyclopediaCodex::orderBy('article_category')
            ->orderBy('article_title')
            ->get();

        return view('admin.wiki', compact('systemBook', 'articles'));
    }

    public function store(Request $request): JsonResponse
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
            'is_visible'       => 'nullable|boolean',
        ]);

        $validated['article_text'] = Purify::clean($validated['article_text']);
        $validated['article_url'] = Str::slug($validated['article_title']);
        $validated['is_visible'] = $request->boolean('is_visible', true);
        $validated['created_by_user_id'] = $request->user()?->id ?? auth()->id();

        $article = EncyclopediaCodex::create($validated);

        return response()->json([
            'status'   => 'success',
            'message'  => lexicon('msg_Wiki_CreatedSuccess', 'Wiki article created successfully.'),
            'data'     => $article,
            'redirect' => null,
        ], 200);
    }

    public function update(Request $request, EncyclopediaCodex $encyclopediaCodex): JsonResponse
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
            'is_visible'       => 'nullable|boolean',
        ]);

        $validated['article_text'] = Purify::clean($validated['article_text']);
        $validated['article_url'] = Str::slug($validated['article_title']);
        $validated['is_visible'] = $request->has('is_visible')
            ? $request->boolean('is_visible')
            : (bool) $encyclopediaCodex->is_visible;

        $encyclopediaCodex->update($validated);

        return response()->json([
            'status'   => 'success',
            'message'  => lexicon('msg_Wiki_UpdatedSuccess', 'Wiki article updated successfully.'),
            'data'     => $encyclopediaCodex,
            'redirect' => null,
        ], 200);
    }

    public function destroy(EncyclopediaCodex $encyclopediaCodex): JsonResponse
    {
        abort_unless(
            auth()->user()?->can('manage_wiki'),
            403,
            lexicon('msg_Wiki_Unauthorised', 'Unauthorised access to the Encyclopedia Codex.')
        );

        $encyclopediaCodex->delete();

        return response()->json([
            'status'   => 'success',
            'message'  => lexicon('msg_Wiki_DeletedSuccess', 'Wiki article deleted successfully.'),
            'data'     => null,
            'redirect' => null,
        ], 200);
    }
}