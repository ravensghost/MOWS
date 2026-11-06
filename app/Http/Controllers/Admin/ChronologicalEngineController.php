<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\ChronologicalEngine;

class ChronologicalEngineController extends Controller
{
    public function store(Request $request)
    {
        abort_unless(auth()->user()?->can('manage_settings'), 403, lexicon('msg_Settings_Unauthorised', 'Unauthorised access to Global Settings.'));

        $validated = $request->validate([
            'engine_title'          => 'required|string|max:255',
            'calculation_algorithm' => 'required|in:real_world,fractional,offset',
            'date_prefix'           => 'nullable|string|max:255',
            'date_suffix'           => 'nullable|string|max:255',
            'base_earth_year'       => 'nullable|integer',
            'base_system_value'     => 'nullable|integer',
            'temporal_multiplier'   => 'nullable|numeric',
        ]);

        ChronologicalEngine::create($validated);

        return response()->json([
            'success' => true,
            'message' => lexicon('msg_TimeSystem_Created', 'Chronological engine created successfully.'),
        ]);
    }

    public function update(Request $request, ChronologicalEngine $chronologicalEngine)
    {
        abort_unless(auth()->user()?->can('manage_settings'), 403, lexicon('msg_Settings_Unauthorised', 'Unauthorised access to Global Settings.'));

        $validated = $request->validate([
            'engine_title'          => 'required|string|max:255',
            'calculation_algorithm' => 'required|in:real_world,fractional,offset',
            'date_prefix'           => 'nullable|string|max:255',
            'date_suffix'           => 'nullable|string|max:255',
            'base_earth_year'       => 'nullable|integer',
            'base_system_value'     => 'nullable|integer',
            'temporal_multiplier'   => 'nullable|numeric',
        ]);

        $chronologicalEngine->update($validated);
        Cache::forget('active_time_system');

        return response()->json([
            'success' => true,
            'message' => lexicon('msg_TimeSystem_Updated', 'Chronological engine updated successfully.'),
        ]);
    }

    public function destroy(ChronologicalEngine $chronologicalEngine)
    {
        abort_unless(auth()->user()?->can('manage_settings'), 403, lexicon('msg_Settings_Unauthorised', 'Unauthorised access to Global Settings.'));

        $chronologicalEngine->delete();
        Cache::forget('active_time_system');

        return response()->json([
            'success' => true,
            'message' => lexicon('msg_TimeSystem_Deleted', 'Chronological engine deleted successfully.'),
        ]);
    }
}
