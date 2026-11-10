<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DistinctionCatalogue;

class DistinctionCatalogueController extends Controller
{
    public function store(Request $request)
    {
        abort_unless(auth()->user()->can('issue_awards'), 403, 'Authorised action.');

        $validated = $request->validate([
            'catalogue_title' => 'required|string|max:255',
        ]);

        DistinctionCatalogue::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => lexicon('msg_distinction_catalogue_created', 'Distinction catalogue created successfully!')
        ]);
    }

    public function update(Request $request, $id)
    {
        abort_unless(auth()->user()->can('issue_awards'), 403, 'Authorised action.');

        $validated = $request->validate([
            'catalogue_title' => 'required|string|max:255',
        ]);

        $catalogue = DistinctionCatalogue::findOrFail($id);
        $catalogue->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => lexicon('msg_distinction_catalogue_updated', 'Distinction catalogue updated successfully!')
        ]);
    }

    public function destroy($id)
    {
        abort_unless(auth()->user()->can('issue_awards'), 403, 'Authorised action.');

        $catalogue = DistinctionCatalogue::findOrFail($id);
        $catalogue->delete();

        return response()->json([
            'status' => 'success',
            'message' => lexicon('msg_distinction_catalogue_deleted', 'Distinction catalogue deleted successfully!')
        ]);
    }
}
