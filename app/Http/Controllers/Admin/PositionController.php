<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\Position;

class PositionController extends Controller
{
    /**
     * Clear cached roster and manifest position data.
     */
    private function clearPositionCaches(): void
    {
        Cache::forget('widget_roster_sections');
        Cache::forget('widget_manifest_sections');
    }

    public function store(Request $request, $id)
    {
        abort_unless(auth()->user()?->can('manage_sections'), 403, lexicon('msg_SectionsPositions_Unauthorised', 'Unauthorised access to Sections & Positions.'));

        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'capacity'    => 'nullable|integer|min:1|max:999',
            'is_open'     => 'nullable|boolean',
            'is_visible'  => 'nullable|boolean',
        ]);

        $position = Position::create([
            'section_id'  => $id,
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
            'capacity'    => $validated['capacity'] ?? 1,
            'is_open'     => $request->boolean('is_open', false),
            'is_visible'  => $request->boolean('is_visible', false),
        ]);

        $this->clearPositionCaches();

        return response()->json([
            'status'  => 'success',
            'success' => true,
            'data'    => $position,
            'message' => lexicon('msg_SectionsPositions_PositionCreated', 'Position created successfully.'),
        ]);
    }

    public function update(Request $request, Position $position)
    {
        abort_unless(auth()->user()?->can('manage_sections'), 403, lexicon('msg_SectionsPositions_Unauthorised', 'Unauthorised access to Sections & Positions.'));

        $validated = $request->validate([
            'section_id'  => 'required|exists:sections,id',
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'capacity'    => 'nullable|integer|min:1|max:999',
            'is_open'     => 'nullable|boolean',
            'is_visible'  => 'nullable|boolean',
        ]);

        $position->update([
            'section_id'  => $validated['section_id'],
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
            'capacity'    => $validated['capacity'] ?? 1,
            'is_open'     => $request->boolean('is_open', false),
            'is_visible'  => $request->boolean('is_visible', false),
        ]);

        $this->clearPositionCaches();

        return response()->json([
            'status'  => 'success',
            'success' => true,
            'data'    => $position,
            'message' => lexicon('msg_SectionsPositions_PositionUpdated', 'Position updated successfully.'),
        ]);
    }

    public function destroy(Position $position)
    {
        abort_unless(auth()->user()?->can('manage_sections'), 403, lexicon('msg_SectionsPositions_Unauthorised', 'Unauthorised access to Sections & Positions.'));

        $position->delete();

        $this->clearPositionCaches();

        return response()->json([
            'status'  => 'success',
            'success' => true,
            'message' => lexicon('msg_SectionsPositions_PositionDeleted', 'Position deleted successfully.'),
        ]);
    }
}
