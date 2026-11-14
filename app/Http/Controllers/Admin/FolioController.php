<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Folio;

class FolioController extends Controller
{
    public function move(Request $request,$id)
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

    public function destroy($id)
    {
        $folio = Folio::findOrFail($id);$folio->delete();

        return response()->json([
            'status'   => 'success',
            'message'  => lexicon('msg_Folio_Deleted', 'Folio deleted successfully.'),
            'data'     => null,
            'redirect' => null,
        ], 200);
    }

    public function reorder(Request $request)
    {
        foreach ($request->input('items', []) as$item) {
            Folio::where('id', $item['id'])->update(['folio_sort_order' =>$item['folio_sort_order']]);
        }

        return response()->json([
            'status'   => 'success',
            'message'  => lexicon('msg_Folio_Reordered', 'Folios reordered successfully.'),
            'data'     => null,
            'redirect' => null,
        ], 200);
    }
}