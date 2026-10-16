<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\TimeSystem;

class TimeSystemController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'engine_type' => 'required|in:real_world,fractional,offset',
            'prefix' => 'nullable|string|max:255',
            'suffix' => 'nullable|string|max:255',
            'base_earth_year' => 'nullable|integer',
            'base_system_value' => 'nullable|integer',
            'multiplier' => 'nullable|numeric',
        ]);

        TimeSystem::create($validated);
        return response()->json(['success' => true, 'message' => 'Time System created.']);
    }

    public function update(Request $request, TimeSystem $timeSystem)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'engine_type' => 'required|in:real_world,fractional,offset',
            'prefix' => 'nullable|string|max:255',
            'suffix' => 'nullable|string|max:255',
            'base_earth_year' => 'nullable|integer',
            'base_system_value' => 'nullable|integer',
            'multiplier' => 'nullable|numeric',
        ]);

        $timeSystem->update($validated);
        Cache::forget('active_time_system');
        return response()->json(['success' => true, 'message' => 'Time System updated.']);
    }

    public function destroy(TimeSystem $timeSystem)
    {
        $timeSystem->delete();
        Cache::forget('active_time_system');
        return response()->json(['success' => true, 'message' => 'Time System deleted.']);
    }
}
