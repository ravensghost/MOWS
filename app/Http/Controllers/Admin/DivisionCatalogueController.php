<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\DivisionCatalogue;
use App\Models\Section;

class DivisionCatalogueController extends Controller
{
    /**
     * Clear cached section and roster manifest data.
     */
    private function clearDivisionCaches(): void
    {
        Cache::forget('global_section_colors');
        Cache::forget('widget_roster_sections');
        Cache::forget('widget_manifest_sections');
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()?->can('manage_sections'), 403, lexicon('msg_SectionsPositions_Unauthorised', 'Unauthorised access to Sections & Positions.'));

        $validated = $request->validate([
            'catalogue_title'       => 'required|string|max:255',
            'catalogue_description' => 'nullable|string',
        ]);

        $catalogue = DivisionCatalogue::create([
            'catalogue_title'       => $validated['catalogue_title'],
            'catalogue_description' => $validated['catalogue_description'] ?? null,
        ]);

        $this->clearDivisionCaches();

        return response()->json([
            'status'  => 'success',
            'success' => true,
            'data'    => $catalogue,
            'message' => lexicon('msg_SectionsPositions_CatalogueCreated', 'Division Catalogue created successfully.'),
        ]);
    }

    public function update(Request $request, DivisionCatalogue $divisionCatalogue)
    {
        abort_unless(auth()->user()?->can('manage_sections'), 403, lexicon('msg_SectionsPositions_Unauthorised', 'Unauthorised access to Sections & Positions.'));

        $validated = $request->validate([
            'catalogue_title'       => 'required|string|max:255',
            'catalogue_description' => 'nullable|string',
        ]);

        $divisionCatalogue->update([
            'catalogue_title'       => $validated['catalogue_title'],
            'catalogue_description' => $validated['catalogue_description'] ?? null,
        ]);

        $this->clearDivisionCaches();

        return response()->json([
            'status'  => 'success',
            'success' => true,
            'data'    => $divisionCatalogue,
            'message' => lexicon('msg_SectionsPositions_CatalogueUpdated', 'Division Catalogue updated successfully.'),
        ]);
    }

    public function destroy(DivisionCatalogue $divisionCatalogue)
    {
        abort_unless(auth()->user()?->can('manage_sections'), 403, lexicon('msg_SectionsPositions_Unauthorised', 'Unauthorised access to Sections & Positions.'));

        $divisionCatalogue->delete();

        $this->clearDivisionCaches();

        return response()->json([
            'status'  => 'success',
            'success' => true,
            'message' => lexicon('msg_SectionsPositions_CatalogueDeleted', 'Division Catalogue deleted successfully.'),
        ]);
    }

    public function assignDivisions(Request $request, DivisionCatalogue $divisionCatalogue)
    {
        abort_unless(auth()->user()?->can('manage_sections'), 403, lexicon('msg_SectionsPositions_Unauthorised', 'Unauthorised access to Sections & Positions.'));

        // Reset division_catalogue_id for divisions previously linked to this catalogue
        Section::where('division_catalogue_id', $divisionCatalogue->id)->update(['division_catalogue_id' => null]);

        // Link newly selected divisions
        if ($request->has('sections')) {
            Section::whereIn('id', $request->input('sections', []))->update(['division_catalogue_id' => $divisionCatalogue->id]);
        }

        $this->clearDivisionCaches();

        return response()->json([
            'status'  => 'success',
            'success' => true,
            'message' => lexicon('msg_SectionsPositions_CatalogueAssigned', 'Divisions assigned to catalogue successfully.'),
        ]);
    }
}
