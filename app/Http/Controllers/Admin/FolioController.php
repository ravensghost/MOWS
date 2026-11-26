<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Folio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FolioController extends Controller
{
    private function authoriseFolioAdminAccess(): void
    {
        $user = auth()->user();

        abort_unless(
            $user && ($user->can('manage_stories') || $user->can('access-admin')),
            403,
            lexicon('msg_Folio_Unauthorised', 'Unauthorised access to Folio Management.')
        );
    }

    public function move(Request $request, int|string $id): JsonResponse
    {
        $this->authoriseFolioAdminAccess();

        $validated = $request->validate([
            'volume_id' => 'required|exists:volumes,id',
        ]);

        $folio = Folio::findOrFail($id);
        $folio->update($validated);

        return response()->json([
            'status'   => 'success',
            'message'  => lexicon('msg_Folio_Relocated', 'Folio relocated successfully.'),
            'data'     => $folio,
            'redirect' => null,
        ], 200);
    }

    public function destroy(int|string $id): JsonResponse
    {
        $this->authoriseFolioAdminAccess();

        $folio = Folio::findOrFail($id);
        $folio->delete();

        return response()->json([
            'status'   => 'success',
            'message'  => lexicon('msg_Folio_Deleted', 'Folio deleted successfully.'),
            'data'     => null,
            'redirect' => null,
        ], 200);
    }

    public function reorder(Request $request): JsonResponse
    {
        $this->authoriseFolioAdminAccess();

        $validated = $request->validate([
            'items'                    => 'nullable|array',
            'items.*.id'               => 'required|exists:folios,id',
            'items.*.folio_sort_order' => 'required|integer',
        ]);

        foreach ($validated['items'] ?? [] as $item) {
            Folio::where('id', $item['id'])->update([
                'folio_sort_order' => $item['folio_sort_order'],
            ]);
        }

        return response()->json([
            'status'   => 'success',
            'message'  => lexicon('msg_Folio_Reordered', 'Folios reordered successfully.'),
            'data'     => null,
            'redirect' => null,
        ], 200);
    }

    public function aiGenerate(Request $request): JsonResponse
    {
        $this->authoriseFolioAdminAccess();

        $validated = $request->validate([
            'prompt'    => 'required|string',
            'folio_id'  => 'nullable|exists:folios,id',
            'volume_id' => 'nullable|exists:volumes,id',
        ]);

        return response()->json([
            'status'   => 'success',
            'message'  => lexicon('msg_Folio_AiGenerated', 'Folio content generated successfully.'),
            'data'     => [
                'prompt'  => $validated['prompt'],
                'content' => '',
            ],
            'redirect' => null,
        ], 200);
    }
}