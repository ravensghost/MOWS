<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Book;

class BookController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'bookcase_id' => 'nullable|exists:bookcases,id',
            'status_id' => 'required|exists:book_statuses,id',
            'ic_day' => 'nullable|integer',
            'ic_time' => 'nullable|string|max:255',
        ]);

        $book = Book::create($validated);

        return response()->json([
            'status' => 'success',
            'data' => $book,
            'message' => 'Book created successfully.'
        ]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'bookcase_id' => 'nullable|exists:bookcases,id',
            'status_id' => 'required|exists:book_statuses,id',
            'ic_day' => 'nullable|integer',
            'ic_time' => 'nullable|string|max:255',
        ]);

        $book = Book::findOrFail($id);
        $book->update($validated);

        return response()->json([
            'status' => 'success',
            'data' => $book,
            'message' => 'Book updated successfully.'
        ]);
    }

    public function destroy($id)
    {
        $book = Book::findOrFail($id);
        $book->delete();

        return response()->json([
            'status' => 'success',
            'data' => null,
            'message' => 'Book deleted successfully.'
        ]);
    }

    public function reorder(Request $request)
    {
        foreach ($request->input('items', []) as $item) {
            Book::where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
        }
        return response()->json([
            'status' => 'success',
            'data' => null,
            'message' => 'Books reordered successfully.'
        ]);
    }
}
