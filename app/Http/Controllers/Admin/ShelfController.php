<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Shelf;

class ShelfController extends Controller
{
    public function store(Request $request)
    {
        $validated =$request->validate([
            'shelf_title' => 'required|string|max:255',
        ]);

        $validated['shelf_sort_order'] = (Shelf::max('shelf_sort_order') ?? 0) + 1;

        $shelf = Shelf::create($validated);

        return response()->json([
            'status'   => 'success',
            'message'  => lexicon('msg_Story_ShelfCreated', 'Shelf created successfully.'),
            'data'     => $shelf,
            'redirect' => null,
        ], 200);
    }

    public function update(Request $request,$id)
    {
        $validated =$request->validate([
            'shelf_title' => 'required|string|max:255',
        ]);

        $shelf = Shelf::findOrFail($id);
        $shelf->update($validated);

        return response()->json([
            'status'   => 'success',
            'message'  => lexicon('msg_Story_ShelfUpdated', 'Shelf updated successfully.'),
            'data'     => $shelf,
            'redirect' => null,
        ], 200);
    }

    public function destroy($id)
    {
        Shelf::findOrFail($id)->delete();

        return response()->json([
            'status'   => 'success',
            'message'  => lexicon('msg_Story_ShelfDeleted', 'Shelf deleted successfully.'),
            'data'     => null,
            'redirect' => null,
        ], 200);
    }

    public function reorder(Request $request)
    {
        foreach ($request->input('items', []) as$item) {
            Shelf::where('id', $item['id'])->update([
                'shelf_sort_order' => $item['shelf_sort_order'],
            ]);
        }

        return response()->json([
            'status'   => 'success',
            'message'  => lexicon('msg_Story_ShelvesReordered', 'Shelves reordered successfully.'),
            'data'     => null,
            'redirect' => null,
        ], 200);
    }
}