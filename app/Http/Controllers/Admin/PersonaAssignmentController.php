<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\ChronologicalRegister;
use App\Models\Insignia;
use App\Models\Persona;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PersonaAssignmentController extends Controller
{
    /**
     * Reassign an active persona's duty assignment and insignia rank.
     */
    public function __invoke(Request $request, $id)
    {
        $validated = $request->validate([
            'assignment_id' => 'required|exists:assignments,id',
            'insignia_id'   => 'required|exists:insignia,id',
            'reason'        => 'nullable|string',
        ]);

        $model = Persona::with(['insignia', 'assignment.division'])->findOrFail($id);

        $oldInsignia = $model->insignia;
        $oldAssignment = $model->assignment;

        $insigniaChanged = (int) $model->insignia_id !== (int) $validated['insignia_id'];
        $assignmentChanged = (int) $model->assignment_id !== (int) $validated['assignment_id'];

        $model->update([
            'assignment_id' => $validated['assignment_id'],
            'insignia_id'   => $validated['insignia_id'],
        ]);

        $reasonSuffix = ! empty($validated['reason'])
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
                'user_id'             => $model->user_id,
                'persona_id'          => $model->id,
                'register_event_mode' => 'promotion',
                'chronicle_text'      => "{$actionText} {$newInsignia->insignia_title}.{$reasonSuffix}",
                'authorising_user_id' => auth()->id(),
                'scope_target'        => 'IC',
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
                'user_id'             => $model->user_id,
                'persona_id'          => $model->id,
                'register_event_mode' => 'transfer',
                'chronicle_text'      => "Transferred from {$oldPosLabel} to {$newPosLabel}.{$reasonSuffix}",
                'authorising_user_id' => auth()->id(),
                'scope_target'        => 'IC',
            ]);
        }

        Cache::forget('widget_roster_sections');
        Cache::forget('widget_personae_table_sections');

        return response()->json(['status' => 'success', 'message' => lexicon('msg_Roster_PersonaReassigned', "Persona '{$model->persona_name}' successfully reassigned."), 'data' => $model, 'redirect' => null], 200);
    }
}