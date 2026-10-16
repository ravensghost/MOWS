<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RankSet;
use App\Models\Section;

class RankSetController extends Controller
{
    public function store(Request $request)
    {
        abort_unless(auth()->user()->can('manage_ranks'), 403, lexicon('msg_Ranks_Unauthorised', 'Unauthorised access to Rank Management.'));

        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        RankSet::create([
            'name' => $request->name,
        ]);

        return response()->json(['status' => 'success', 'success' => true]);
    }

    public function destroy(RankSet $rankSet)
    {
        abort_unless(auth()->user()->can('manage_ranks'), 403, lexicon('msg_Ranks_Unauthorised', 'Unauthorised access to Rank Management.'));

        $rankSet->delete();
        return response()->json(['status' => 'success', 'success' => true]);
    }

    public function assignSections(Request $request, RankSet $rankSet)
    {
        abort_unless(auth()->user()->can('manage_ranks'), 403, lexicon('msg_Ranks_Unauthorised', 'Unauthorised access to Rank Management.'));

        Section::where('rank_set_id', $rankSet->id)->update(['rank_set_id' => null]);

        if ($request->has('sections')) {
            Section::whereIn('id', $request->sections)->update(['rank_set_id' => $rankSet->id]);
        }

        return response()->json(['status' => 'success', 'success' => true]);
    }
}
