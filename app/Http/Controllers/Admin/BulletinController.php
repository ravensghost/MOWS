<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Bulletin;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Stevebauman\Purify\Facades\Purify;

class BulletinController extends Controller
{
    public function index()
    {
        $systemBook = Book::with(['grid.gridModules', 'modules'])
            ->where('book_url', 'admin/news')
            ->where('is_published', true)
            ->firstOrFail();

        $news = Bulletin::orderBy('created_at', 'desc')->get();

        return view('admin.news', compact('systemBook', 'news'));
    }

    public function store(Request $request)
    {
        // 1. Validate FIRST. If this fails, it stops execution immediately.
        $validated = $request->validate([
            'bulletin_title' => 'required|string|max:255',
            'bulletin_text' => 'required|string',
        ]);

        // 2. Create SECOND (only runs if validation passes)
        $model = Bulletin::create([
            'bulletin_title' => $validated['bulletin_title'],
            'bulletin_text' => Purify::clean($validated['bulletin_text']),
            'user_id' => $request->user()->id,
            'published_at' => now(),
        ]);

        // 3. Push to Discord THIRD (only runs if creation succeeds)
        NotificationService::sendDiscord(
            'news',
            "📰 **New Station Announcement!**\n**{$model->bulletin_title}**\nCheck out the latest broadcast on the station."
        );

        return response()->json([
            'status' => 'success',
            'message' => lexicon('msg_Bulletins_Created', 'Bulletin published successfully.'),
            'data' => $model,
            'redirect' => null,
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'bulletin_title' => 'required|string|max:255',
            'bulletin_text' => 'required|string',
        ]);

        $validated['bulletin_text'] = Purify::clean($validated['bulletin_text']);

        $model = Bulletin::findOrFail($id);
        $model->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => lexicon('msg_Bulletins_Updated', 'Bulletin updated successfully.'),
            'data' => $model,
            'redirect' => null,
        ], 200);
    }

    public function destroy($id)
    {
        $model = Bulletin::findOrFail($id);
        $model->delete();

        return response()->json([
            'status' => 'success',
            'message' => lexicon('msg_Bulletins_Deleted', 'Bulletin deleted successfully.'),
            'data' => $model,
            'redirect' => null,
        ], 200);
    }
}
