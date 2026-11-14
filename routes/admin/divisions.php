<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AssignmentController extends Controller
{
    /**
     * Clear cached roster and manifest assignment data.
     */
    private function clearAssignmentCaches(): void
    {
        Cache::forget('widget_personae_table_sections');
        Cache::forget('widget_roster_sections');
        Cache::forget('widget_manifest_sections');
    }

    public function store(Request $request,$id = null)
    {
        abort_unless(auth()->user()?->can('manage_sections'), 403, lexicon('msg_SectionsPositions_Unauthorised', 'Unauthorised access to Sections & Positions.'));

        $divisionId = $id ?? $request->input('division_id');
        $request->merge(['division_id' =>$divisionId]);

        $validated =$request->validate([
            'division_id'            => 'required|exists:divisions,id',
            'assignment_title'       => 'required|string|max:255',
            'assignment_description' => 'nullable|string',
            'maximum_occupants'      => 'nullable|integer|min:1|max:999',
            'is_recruitment_open'    => 'nullable|boolean',
            'is_visible'             => 'nullable|boolean',
        ]);

        $model = Assignment::create([
            'division_id'            => $validated['division_id'],
            'assignment_title'       => $validated['assignment_title'],
            'assignment_description' => $validated['assignment_description'] ?? null,
            'maximum_occupants'      => $validated['maximum_occupants'] ?? 1, 'is_recruitment_open'    =>$request->boolean('is_recruitment_open', false),
            'is_visible'             => $request->boolean('is_visible', false),
        ]);

        $this->clearAssignmentCaches();

        return response()->json(['status' => 'success', 'message' => lexicon('msg_SectionsPositions_PositionCreated', 'Position created successfully.'), 'data' => $model, 'redirect' => null], 200);
    }

    public function update(Request $request, Assignment$assignment)
    {
        abort_unless(auth()->user()?->can('manage_sections'), 403, lexicon('msg_SectionsPositions_Unauthorised', 'Unauthorised access to Sections & Positions.'));

        $validated =$request->validate([
            'division_id'            => 'required|exists:divisions,id',
            'assignment_title'       => 'required|string|max:255',
            'assignment_description' => 'nullable|string',
            'maximum_occupants'      => 'nullable|integer|min:1|max:999',
            'is_recruitment_open'    => 'nullable|boolean',
            'is_visible'             => 'nullable|boolean',
        ]);

        $assignment->update([
            'division_id'            => $validated['division_id'],
            'assignment_title'       => $validated['assignment_title'],
            'assignment_description' => $validated['assignment_description'] ?? null,
            'maximum_occupants'      => $validated['maximum_occupants'] ?? 1, 'is_recruitment_open'    =>$request->boolean('is_recruitment_open', false),
            'is_visible'             => $request->boolean('is_visible', false),
        ]);

        $model =$assignment;

        $this->clearAssignmentCaches();

        return response()->json(['status' => 'success', 'message' => lexicon('msg_SectionsPositions_PositionUpdated', 'Position updated successfully.'), 'data' => $model, 'redirect' => null], 200);
    }

    public function destroy(Assignment $assignment)
    {
        abort_unless(auth()->user()?->can('manage_sections'), 403, lexicon('msg_SectionsPositions_Unauthorised', 'Unauthorised access to Sections & Positions.'));

        $model =$assignment;
        $assignment->delete();

        $this->clearAssignmentCaches();

        return response()->json(['status' => 'success', 'message' => lexicon('msg_SectionsPositions_PositionDeleted', 'Position deleted successfully.'), 'data' => $model, 'redirect' => null], 200);
    }
}