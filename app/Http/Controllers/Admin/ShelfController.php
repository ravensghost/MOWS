<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Shelf;

class ShelfController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'shelf_title' => 'required|string|max:255'
        ]);

        $shelf = Shelf::create($validated);

        return response()->json([
            'status' => 'success',
            'data' => $shelf,
            'message' => lexicon('msg_Story_ShelfCreated', 'Shelf created successfully.')
        ]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'shelf_title' => 'required|string|max:255'
        ]);

        $shelf = Shelf::findOrFail($id);
        $shelf->update($validated);

        return response()->json([
            'status' => 'success',
            'data' => $shelf,
            'message' => lexicon('msg_Story_ShelfUpdated', 'Shelf updated successfully.')
        ]);
    }

    public function destroy($id)
    {
        $shelf = Shelf::findOrFail($id);
        $shelf->delete();

        return response()->json([
            'status' => 'success',
            'data' => null,
            'message' => lexicon('msg_Story_ShelfDeleted', 'Shelf deleted successfully.')
        ]);
    }

    public function reorder(Request $request)
    {
        foreach ($request->input('items', []) as $item) {
            Shelf::where('id', $item['id'])->update(['shelf_sort_order' => $item['sort_order']]);
        }
        return response()->json([
            'status' => 'success',
            'data' => null,
            'message' => lexicon('msg_Story_ShelvesReordered', 'Shelves reordered successfully.')
        ]);
    }
}
