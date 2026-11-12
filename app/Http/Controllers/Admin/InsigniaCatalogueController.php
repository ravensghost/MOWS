<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\InsigniaCatalogue;
use App\Models\Division;

class InsigniaCatalogueController extends Controller
{
    /**
     * Clear cached roster insignia data.
     */
    private function clearRankCaches(): void
    {
        Cache::forget('widget_roster_sections');
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->can('manage_ranks'), 403, lexicon('msg_Ranks_Unauthorised', 'Unauthorised access to Rank Management.'));

        $request->validate([
            'catalogue_title' => 'required|string|max:255',
            'is_visible'      => 'nullable|boolean',
        ]);

        InsigniaCatalogue::create([
            'catalogue_title' => $request->input('catalogue_title'),
            'is_visible'      => $request->boolean('is_visible', true),
        ]);

        $this->clearRankCaches();

        return response()->json(['status' => 'success', 'success' => true]);
    }

    public function update(Request $request, InsigniaCatalogue $rankSet)
    {
        abort_unless(auth()->user()->can('manage_ranks'), 403, lexicon('msg_Ranks_Unauthorised', 'Unauthorised access to Rank Management.'));

        $request->validate([
            'catalogue_title' => 'sometimes|required|string|max:255',
            'is_visible'      => 'nullable|boolean',
        ]);

        $rankSet->update([
            'catalogue_title' => $request->input('catalogue_title', $rankSet->catalogue_title),
            'is_visible'      => $request->boolean('is_visible', $rankSet->is_visible),
        ]);

        $this->clearRankCaches();

        return response()->json(['status' => 'success', 'success' => true]);
    }

    public function destroy(InsigniaCatalogue $rankSet)
    {
        abort_unless(auth()->user()->can('manage_ranks'), 403, lexicon('msg_Ranks_Unauthorised', 'Unauthorised access to Rank Management.'));

        $rankSet->delete();

        $this->clearRankCaches();

        return response()->json(['status' => 'success', 'success' => true]);
    }

    public function assignSections(Request $request, InsigniaCatalogue $rankSet)
    {
        abort_unless(auth()->user()->can('manage_ranks'), 403, lexicon('msg_Ranks_Unauthorised', 'Unauthorised access to Rank Management.'));

        Division::where('insignia_catalogue_id', $rankSet->id)->update(['insignia_catalogue_id' => null]);

        if ($request->has('divisions') && is_array($request->input('divisions'))) {
            Division::whereIn('id', $request->input('divisions'))->update(['insignia_catalogue_id' => $rankSet->id]);
        }

        $this->clearRankCaches();

        return response()->json(['status' => 'success', 'success' => true]);
    }
}
