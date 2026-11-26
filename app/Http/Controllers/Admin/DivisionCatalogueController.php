<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Division;
use App\Models\DivisionCatalogue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DivisionCatalogueController extends Controller
{
    /**
     * Clear cached division base colours and roster manifest data.
     */
    private function clearDivisionCaches(): void
    {
        Cache::forget('global_section_colors');
        Cache::forget('widget_personae_table_sections');
        Cache::forget('widget_roster_sections');
        Cache::forget('widget_manifest_sections');
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless(
            auth()->user()?->can('manage_sections'),
            403,
            lexicon('msg_SectionsPositions_Unauthorised', 'Unauthorised access to Sections & Positions.')
        );

        $validated = $request->validate([
            'catalogue_title'       => 'required|string|max:255',
            'catalogue_description' => 'nullable|string',
            'is_visible'            => 'nullable|boolean',
        ]);

        $model = DivisionCatalogue::create([
            'catalogue_title'       => $validated['catalogue_title'],
            'catalogue_description' => $validated['catalogue_description'] ?? null,
            'is_visible'            => $request->boolean('is_visible', true),
        ]);

        $this->clearDivisionCaches();

        return response()->json([
            'status'   => 'success',
            'message'  => lexicon('msg_SectionsPositions_CatalogueCreated', 'Division Catalogue created successfully.'),
            'data'     => $model,
            'redirect' => null,
        ], 200);
    }

    public function update(Request $request, DivisionCatalogue $divisionCatalogue): JsonResponse
    {
        abort_unless(
            auth()->user()?->can('manage_sections'),
            403,
            lexicon('msg_SectionsPositions_Unauthorised', 'Unauthorised access to Sections & Positions.')
        );

        $validated = $request->validate([
            'catalogue_title'       => 'required|string|max:255',
            'catalogue_description' => 'nullable|string',
            'is_visible'            => 'nullable|boolean',
        ]);

        $divisionCatalogue->update([
            'catalogue_title'       => $validated['catalogue_title'],
            'catalogue_description' => $validated['catalogue_description'] ?? null,
            'is_visible'            => $request->has('is_visible') ? $request->boolean('is_visible') : $divisionCatalogue->is_visible,
        ]);

        $this->clearDivisionCaches();

        return response()->json([
            'status'   => 'success',
            'message'  => lexicon('msg_SectionsPositions_CatalogueUpdated', 'Division Catalogue updated successfully.'),
            'data'     => $divisionCatalogue,
            'redirect' => null,
        ], 200);
    }

    public function destroy(DivisionCatalogue $divisionCatalogue): JsonResponse
    {
        abort_unless(
            auth()->user()?->can('manage_sections'),
            403,
            lexicon('msg_SectionsPositions_Unauthorised', 'Unauthorised access to Sections & Positions.')
        );

        $model = $divisionCatalogue;
        $divisionCatalogue->delete();

        $this->clearDivisionCaches();

        return response()->json([
            'status'   => 'success',
            'message'  => lexicon('msg_SectionsPositions_CatalogueDeleted', 'Division Catalogue deleted successfully.'),
            'data'     => $model,
            'redirect' => null,
        ], 200);
    }

    public function assignDivisions(Request $request, DivisionCatalogue $divisionCatalogue): JsonResponse
    {
        abort_unless(
            auth()->user()?->can('manage_sections'),
            403,
            lexicon('msg_SectionsPositions_Unauthorised', 'Unauthorised access to Sections & Positions.')
        );

        $validated = $request->validate([
            'division_ids'   => 'nullable|array',
            'division_ids.*' => 'exists:divisions,id',
        ]);

        $divisionIds = $validated['division_ids'] ?? [];

        Division::where('division_catalogue_id', $divisionCatalogue->id)
            ->whereNotIn('id', $divisionIds)
            ->update(['division_catalogue_id' => null]);

        if (! empty($divisionIds)) {
            Division::whereIn('id', $divisionIds)
                ->update(['division_catalogue_id' => $divisionCatalogue->id]);
        }

        $model = $divisionCatalogue->load('divisions');

        $this->clearDivisionCaches();

        return response()->json([
            'status'   => 'success',
            'message'  => lexicon('msg_SectionsPositions_CatalogueAssigned', 'Divisions assigned to catalogue successfully.'),
            'data'     => $model,
            'redirect' => null,
        ], 200);
    }
}