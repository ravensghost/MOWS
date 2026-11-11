<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\Persona;
use App\Models\Assignment;
use App\Models\Insignia;
use App\Models\ChronologicalRegister;

class PersonaAssignmentController extends Controller
{
    public function __invoke(Request $request, $id)
    {
        $validated = $request->validate([
            'assignment_id' => 'required|exists:assignments,id',
            'insignia_id'   => 'required|exists:insignia,id',
            'reason'        => 'nullable|string',
        ]);

        $persona = Persona::with(['insignia', 'assignment.division'])->findOrFail($id);

        $oldInsignia = $persona->insignia;
        $oldAssignment = $persona->assignment;

        $insigniaChanged = (int) $persona->insignia_id !== (int) $validated['insignia_id'];
        $assignmentChanged = (int) $persona->assignment_id !== (int) $validated['assignment_id'];

        $persona->update([
            'assignment_id' => $validated['assignment_id'],
            'insignia_id'   => $validated['insignia_id'],
        ]);

        $reasonSuffix = !empty($validated['reason'])
            ? ' Reason: ' . $validated['reason']
            : ' (Updated via Roster Management)';

        if ($insigniaChanged) {
            $newInsignia = Insignia::findOrFail($validated['insignia_id']);
            $actionText = 'Rank changed to';

            if ($oldInsignia) {
                if ($newInsignia->insignia_sort_order > $oldInsignia->insignia_sort_order) {
                    $actionText = 'Promoted to';
                } elseif ($newInsignia->insignia_sort_order < $oldInsignia->insignia_sort_order) {
                    $actionText = 'Demoted to';
                }
            }

            ChronologicalRegister::create([
                'user_id'              => $persona->user_id,
                'persona_id'           => $persona->id,
                'register_event_mode'  => 'promotion',
                'chronicle_text'       => "{$actionText} {$newInsignia->insignia_title}.{$reasonSuffix}",
                'authorising_user_id'  => auth()->id(),
                'scope_target'         => 'IC',
            ]);
        }

        if ($assignmentChanged) {
            $newAssignment = Assignment::with('division')->findOrFail($validated['assignment_id']);
            $newPosLabel = $newAssignment->division
                ? "{$newAssignment->assignment_title} ({$newAssignment->division->division_title})"
                : $newAssignment->assignment_title;

            $oldPosLabel = $oldAssignment
                ? ($oldAssignment->division ? "{$oldAssignment->assignment_title} ({$oldAssignment->division->division_title})" : $oldAssignment->assignment_title)
                : 'Unassigned';

            ChronologicalRegister::create([
                'user_id'              => $persona->user_id,
                'persona_id'           => $persona->id,
                'register_event_mode'  => 'transfer',
                'chronicle_text'       => "Transferred from {$oldPosLabel} to {$newPosLabel}.{$reasonSuffix}",
                'authorising_user_id'  => auth()->id(),
                'scope_target'         => 'IC',
            ]);
        }

        Cache::forget('widget_roster_sections');

        $message = "Character '{$persona->persona_name}' successfully reassigned.";

        if ($request->wantsJson()) {
            return response()->json(['status' => 'success', 'success' => true, 'message' => $message]);
        }

        return back()->with('status', $message);
    }
}
