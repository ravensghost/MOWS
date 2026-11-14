<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Folio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FolioController extends Controller
{
    public function move(Request $request,$id): JsonResponse
    {
        $validated =$request->validate([
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

    public function destroy($id): JsonResponse
    {
        $folio = Folio::findOrFail($id);$folio->delete();

        return response()->json([
            'status'   => 'success',
            'message'  => lexicon('msg_Folio_Deleted', 'Folio deleted successfully.'),
            'data'     => null,
            'redirect' => null,
        ], 200);
    }

    public function reorder(Request $request): JsonResponse
    {
        foreach ($request->input('items', []) as$item) {
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
        $validated =$request->validate([
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