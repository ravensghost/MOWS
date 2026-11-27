<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Bulletin;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Stevebauman\Purify\Facades\Purify;

class BulletinController extends Controller
{
    private function authoriseBulletinAccess(): void
    {
        $user = auth()->user();

        abort_unless(
            $user && ($user->can('manage_news') || $user->can('manage_bulletins') || $user->can('access-admin')),
            403,
            lexicon('msg_Bulletins_Unauthorised', 'Unauthorised access to Bulletin Management.')
        );
    }

    public function index(): View
    {
        $this->authoriseBulletinAccess();

        $systemBook = Book::forUrl('/admin/bulletins')->publishedWithLayout()->firstOrFail();

        $news = Bulletin::orderBy('created_at', 'desc')->get();

        return view('admin.news', compact('systemBook', 'news'));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authoriseBulletinAccess();

        $validated = $request->validate([
            'bulletin_title' => 'required|string|max:255',
            'bulletin_text'  => 'required|string',
        ]);

        $model = Bulletin::create([
            'bulletin_title' => $validated['bulletin_title'],
            'bulletin_text'  => Purify::clean($validated['bulletin_text']),
            'user_id'        => $request->user()?->id ?? auth()->id(),
            'published_at'   => now(),
        ]);

        NotificationService::sendDiscord(
            'news',
            "📰 **New Station Announcement!**\n**{$model->bulletin_title}**\nCheck out the latest broadcast on the station."
        );

        return response()->json([
            'status'   => 'success',
            'message'  => lexicon('msg_Bulletins_Created', 'Bulletin published successfully.'),
            'data'     => $model,
            'redirect' => null,
        ], 200);
    }

    public function update(Request $request, int|string $id): JsonResponse
    {
        $this->authoriseBulletinAccess();

        $validated = $request->validate([
            'bulletin_title' => 'required|string|max:255',
            'bulletin_text'  => 'required|string',
        ]);

        $validated['bulletin_text'] = Purify::clean($validated['bulletin_text']);

        $model = Bulletin::findOrFail($id);
        $model->update($validated);

        return response()->json([
            'status'   => 'success',
            'message'  => lexicon('msg_Bulletins_Updated', 'Bulletin updated successfully.'),
            'data'     => $model,
            'redirect' => null,
        ], 200);
    }

    public function destroy(int|string $id): JsonResponse
    {
        $this->authoriseBulletinAccess();

        $model = Bulletin::findOrFail($id);
        $model->delete();

        return response()->json([
            'status'   => 'success',
            'message'  => lexicon('msg_Bulletins_Deleted', 'Bulletin deleted successfully.'),
            'data'     => $model,
            'redirect' => null,
        ], 200);
    }
}