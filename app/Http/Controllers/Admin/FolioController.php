<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Folio;

class FolioController extends Controller
{
    public function move(Request $request, $id)
    {
        $validated = $request->validate([
            'volume_id' => 'required|exists:volumes,id'
        ]);

        $folio = Folio::findOrFail($id);
        $folio->update($validated);

        return response()->json([
            'status' => 'success',
            'data'   => $folio,
            'message' => 'Folio relocated successfully.'
        ]);
    }

    public function destroy($id)
    {
        $folio = Folio::findOrFail($id);
        $folio->delete();

        return response()->json([
            'status' => 'success',
            'data'   => null,
            'message' => 'Folio deleted successfully.'
        ]);
    }

    public function reorder(Request $request)
    {
        foreach ($request->input('items', []) as $item) {
            Folio::where('id', $item['id'])->update(['folio_sort_order' => $item['folio_sort_order']]);
        }
        return response()->json([
            'status' => 'success',
            'data'   => null,
            'message' => 'Folios reordered successfully.'
        ]);
    }
}
