<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Bookcase;

class BookcaseController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255'
        ]);

        $bookcase = Bookcase::create($validated);

        return response()->json([
            'status' => 'success',
            'data' => $bookcase,
            'message' => 'Bookcase created successfully.'
        ]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255'
        ]);

        $bookcase = Bookcase::findOrFail($id);
        $bookcase->update($validated);

        return response()->json([
            'status' => 'success',
            'data' => $bookcase,
            'message' => 'Bookcase updated successfully.'
        ]);
    }

    public function destroy($id)
    {
        $bookcase = Bookcase::findOrFail($id);
        $bookcase->delete();

        return response()->json([
            'status' => 'success',
            'data' => null,
            'message' => 'Bookcase deleted successfully.'
        ]);
    }

    public function reorder(Request $request)
    {
        foreach ($request->input('items', []) as $item) {
            Bookcase::where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
        }
        return response()->json([
            'status' => 'success',
            'data' => null,
            'message' => 'Bookcases reordered successfully.'
        ]);
    }
}
