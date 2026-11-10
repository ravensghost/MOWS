<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\Persona;
use App\Models\Position;
use App\Models\Ranks;
use App\Models\ServiceRecord;

class PersonaAssignmentController extends Controller
{
    public function __invoke(Request $request, $id)
    {
        $validated = $request->validate([
            'assignment_id' => 'required|exists:positions,id',
            'insignia_id'   => 'required|exists:ranks,id',
            'reason'        => 'nullable|string',
        ]);

        $persona = Persona::with(['rank', 'position.section'])->findOrFail($id);

        $oldRank = $persona->rank;
        $oldPosition = $persona->position;

        $rankChanged = (int) $persona->insignia_id !== (int) $validated['insignia_id'];
        $positionChanged = (int) $persona->assignment_id !== (int) $validated['assignment_id'];

        $persona->update([
            'assignment_id' => $validated['assignment_id'],
            'insignia_id'   => $validated['insignia_id'],
        ]);

        $reasonSuffix = !empty($validated['reason'])
            ? ' Reason: ' . $validated['reason']
            : ' (Updated via Roster Management)';

        if ($rankChanged) {
            $newRank = Ranks::findOrFail($validated['insignia_id']);
            $actionText = 'Rank changed to';

            if ($oldRank) {
                if ($newRank->sort_order > $oldRank->sort_order) {
                    $actionText = 'Promoted to';
                } elseif ($newRank->sort_order < $oldRank->sort_order) {
                    $actionText = 'Demoted to';
                }
            }

            ServiceRecord::create([
                'user_id'        => $persona->user_id,
                'character_id'   => $persona->id,
                'event_type'     => 'promotion',
                'description'    => "{$actionText} {$newRank->name}.{$reasonSuffix}",
                'issued_by'      => auth()->id(),
                'display_target' => 'IC',
            ]);
        }

        if ($positionChanged) {
            $newPosition = Position::with('section')->findOrFail($validated['assignment_id']);
            $newPosLabel = $newPosition->section
                ? "{$newPosition->name} ({$newPosition->section->name})"
                : $newPosition->name;

            $oldPosLabel = $oldPosition
                ? ($oldPosition->section ? "{$oldPosition->name} ({$oldPosition->section->name})" : $oldPosition->name)
                : 'Unassigned';

            ServiceRecord::create([
                'user_id'        => $persona->user_id,
                'character_id'   => $persona->id,
                'event_type'     => 'transfer',
                'description'    => "Transferred from {$oldPosLabel} to {$newPosLabel}.{$reasonSuffix}",
                'issued_by'      => auth()->id(),
                'display_target' => 'IC',
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
