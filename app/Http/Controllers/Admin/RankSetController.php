<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\RankSet;
use App\Models\Section;

class RankSetController extends Controller
{
    /**
     * Clear cached roster rank data.
     */
    private function clearRankCaches(): void
    {
        Cache::forget('widget_roster_sections');
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->can('manage_ranks'), 403, lexicon('msg_Ranks_Unauthorised', 'Unauthorised access to Rank Management.'));

        $request->validate([
            'name'       => 'required|string|max:255',
            'is_visible' => 'nullable|boolean',
        ]);

        RankSet::create([
            'name'       => $request->name,
            'is_visible' => $request->boolean('is_visible', false),
        ]);

        $this->clearRankCaches();

        return response()->json(['status' => 'success', 'success' => true]);
    }

    public function update(Request $request, RankSet $rankSet)
    {
        abort_unless(auth()->user()->can('manage_ranks'), 403, lexicon('msg_Ranks_Unauthorised', 'Unauthorised access to Rank Management.'));

        $request->validate([
            'name'       => 'sometimes|required|string|max:255',
            'is_visible' => 'nullable|boolean',
        ]);

        $rankSet->update([
            'name'       => $request->input('name', $rankSet->name),
            'is_visible' => $request->boolean('is_visible', false),
        ]);

        $this->clearRankCaches();

        return response()->json(['status' => 'success', 'success' => true]);
    }

    public function destroy(RankSet $rankSet)
    {
        abort_unless(auth()->user()->can('manage_ranks'), 403, lexicon('msg_Ranks_Unauthorised', 'Unauthorised access to Rank Management.'));

        $rankSet->delete();

        $this->clearRankCaches();

        return response()->json(['status' => 'success', 'success' => true]);
    }

    public function assignSections(Request $request, RankSet $rankSet)
    {
        abort_unless(auth()->user()->can('manage_ranks'), 403, lexicon('msg_Ranks_Unauthorised', 'Unauthorised access to Rank Management.'));

        Section::where('rank_set_id', $rankSet->id)->update(['rank_set_id' => null]);

        if ($request->has('sections')) {
            Section::whereIn('id', $request->sections)->update(['rank_set_id' => $rankSet->id]);
        }

        $this->clearRankCaches();

        return response()->json(['status' => 'success', 'success' => true]);
    }
}
