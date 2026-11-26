<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Division;
use App\Models\InsigniaCatalogue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class InsigniaCatalogueController extends Controller
{
    /**
     * Clear cached roster insignia data.
     */
    private function clearRankCaches(): void
    {
        Cache::forget('widget_roster_sections');
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless(
            auth()->user()?->can('manage_ranks'),
            403,
            lexicon('msg_Ranks_Unauthorised', 'Unauthorised access to Rank Management.')
        );

        $request->validate([
            'catalogue_title' => 'required|string|max:255',
            'is_visible'      => 'nullable|boolean',
        ]);

        $catalogue = InsigniaCatalogue::create([
            'catalogue_title' => $request->input('catalogue_title'),
            'is_visible'      => $request->boolean('is_visible', true),
        ]);

        $this->clearRankCaches();

        return response()->json([
            'status'   => 'success',
            'message'  => lexicon('msg_RankSets_Created', 'Rank set created successfully.'),
            'data'     => $catalogue,
            'redirect' => null,
        ], 200);
    }

    public function update(Request $request, InsigniaCatalogue $catalogue): JsonResponse
    {
        abort_unless(
            auth()->user()?->can('manage_ranks'),
            403,
            lexicon('msg_Ranks_Unauthorised', 'Unauthorised access to Rank Management.')
        );

        $request->validate([
            'catalogue_title' => 'sometimes|required|string|max:255',
            'is_visible'      => 'nullable|boolean',
        ]);

        $catalogue->update([
            'catalogue_title' => $request->input('catalogue_title', $catalogue->catalogue_title),
            'is_visible'      => $request->has('is_visible')
                ? $request->boolean('is_visible')
                : (bool) $catalogue->is_visible,
        ]);

        $this->clearRankCaches();

        return response()->json([
            'status'   => 'success',
            'message'  => lexicon('msg_RankSets_Updated', 'Rank set updated successfully.'),
            'data'     => $catalogue,
            'redirect' => null,
        ], 200);
    }

    public function destroy(InsigniaCatalogue $catalogue): JsonResponse
    {
        abort_unless(
            auth()->user()?->can('manage_ranks'),
            403,
            lexicon('msg_Ranks_Unauthorised', 'Unauthorised access to Rank Management.')
        );

        $catalogue->delete();

        $this->clearRankCaches();

        return response()->json([
            'status'   => 'success',
            'message'  => lexicon('msg_RankSets_Deleted', 'Rank set deleted successfully.'),
            'data'     => null,
            'redirect' => null,
        ], 200);
    }

    public function assignSections(Request $request, InsigniaCatalogue $catalogue): JsonResponse
    {
        abort_unless(
            auth()->user()?->can('manage_ranks'),
            403,
            lexicon('msg_Ranks_Unauthorised', 'Unauthorised access to Rank Management.')
        );

        $validated = $request->validate([
            'divisions'   => 'nullable|array',
            'divisions.*' => 'exists:divisions,id',
        ]);

        Division::where('insignia_catalogue_id', $catalogue->id)->update(['insignia_catalogue_id' => null]);

        if (! empty($validated['divisions']) && is_array($validated['divisions'])) {
            Division::whereIn('id', $validated['divisions'])->update(['insignia_catalogue_id' => $catalogue->id]);
        }

        $this->clearRankCaches();

        return response()->json([
            'status'   => 'success',
            'message'  => lexicon('msg_RankSets_SectionsAssigned', 'Organisational sections assigned successfully.'),
            'data'     => $catalogue->load('divisions'),
            'redirect' => null,
        ], 200);
    }
}