<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Chapter;

class ChapterController extends Controller
{
    public function move(Request $request, $id)
    {
        $validated = $request->validate([
            'book_id' => 'required|exists:books,id'
        ]);

        $chapter = Chapter::findOrFail($id);
        $chapter->update($validated);

        return response()->json([
            'status' => 'success',
            'data' => $chapter,
            'message' => 'Chapter relocated successfully.'
        ]);
    }

    public function destroy($id)
    {
        $chapter = Chapter::findOrFail($id);
        $chapter->delete();

        return response()->json([
            'status' => 'success',
            'data' => null,
            'message' => 'Chapter deleted successfully.'
        ]);
    }

    public function reorder(Request $request)
    {
        foreach ($request->input('items', []) as $item) {
            Chapter::where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
        }
        return response()->json([
            'status' => 'success',
            'data' => null,
            'message' => 'Chapters reordered successfully.'
        ]);
    }
}
