<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DistinctionCatalogue;
use Illuminate\Http\Request;

class DistinctionCatalogueController extends Controller
{
    public function store(Request $request)
    {
        abort_unless(auth()->user()->can('issue_awards'), 403, 'Unauthorised action.');

        $validated = $request->validate([
            'catalogue_title' => 'required|string|max:255',
        ]);

        $catalogue = DistinctionCatalogue::create($validated);

        return response()->json([
            'status'   => 'success',
            'message'  => lexicon('msg_distinction_catalogue_created', 'Distinction catalogue created successfully!'),
            'data'     => $catalogue,
            'redirect' => null,
        ], 200);
    }

    public function update(Request $request, DistinctionCatalogue $catalogue)
    {
        abort_unless(auth()->user()->can('issue_awards'), 403, 'Unauthorised action.');

        $validated = $request->validate([
            'catalogue_title' => 'required|string|max:255',
        ]);

        $catalogue->update($validated);

        return response()->json([
            'status'   => 'success',
            'message'  => lexicon('msg_distinction_catalogue_updated', 'Distinction catalogue updated successfully!'),
            'data'     => $catalogue,
            'redirect' => null,
        ], 200);
    }

    public function destroy(DistinctionCatalogue $catalogue)
    {
        abort_unless(auth()->user()->can('issue_awards'), 403, 'Unauthorised action.');

        $catalogue->delete();

        return response()->json([
            'status'   => 'success',
            'message'  => lexicon('msg_distinction_catalogue_deleted', 'Distinction catalogue deleted successfully!'),
            'data'     => $catalogue,
            'redirect' => null,
        ], 200);
    }
}