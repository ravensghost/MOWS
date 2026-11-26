<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shelf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShelfController extends Controller
{
    private function authoriseShelfAccess(): void
    {
        $user = auth()->user();

        abort_unless(
            $user && ($user->can('manage_stories') || $user->can('access-admin')),
            403,
            lexicon('msg_Story_Unauthorised', 'Unauthorised access to Shelf Management.')
        );
    }

    public function store(Request $request): JsonResponse
    {
        $this->authoriseShelfAccess();

        $validated = $request->validate([
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

    public function update(Request $request, int|string $id): JsonResponse
    {
        $this->authoriseShelfAccess();

        $validated = $request->validate([
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

    public function destroy(int|string $id): JsonResponse
    {
        $this->authoriseShelfAccess();

        $shelf = Shelf::findOrFail($id);
        $shelf->delete();

        return response()->json([
            'status'   => 'success',
            'message'  => lexicon('msg_Story_ShelfDeleted', 'Shelf deleted successfully.'),
            'data'     => null,
            'redirect' => null,
        ], 200);
    }

    public function reorder(Request $request): JsonResponse
    {
        $this->authoriseShelfAccess();

        $validated = $request->validate([
            'items'                    => 'nullable|array',
            'items.*.id'               => 'required|exists:shelves,id',
            'items.*.shelf_sort_order' => 'required|integer',
        ]);

        foreach ($validated['items'] ?? [] as $item) {
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