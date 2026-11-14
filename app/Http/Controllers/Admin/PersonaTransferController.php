<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\Persona;
use App\Models\Assignment;
use App\Models\ChronologicalRegister;

class PersonaTransferController extends Controller
{
    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'persona_id'     => 'required|exists:personae,id',
            'assignment_id'  => 'required|exists:assignments,id',
            'chronicle_text' => 'required|string',
        ]);

        $persona = Persona::findOrFail($validated['persona_id']);
        $newAssignment = Assignment::with('division')->findOrFail($validated['assignment_id']);

        $persona->assignment_id = $newAssignment->id;
        $persona->save();

        Cache::forget('widget_roster_sections');
        Cache::forget('widget_personae_table_sections');

        $assignmentLabel = $newAssignment->division
            ? "{$newAssignment->assignment_title} ({$newAssignment->division->division_title})"
            : $newAssignment->assignment_title;

        ChronologicalRegister::create([
            'user_id'             => $persona->user_id,
            'persona_id'          => $persona->id,
            'register_event_mode' => 'transfer',
            'chronicle_text'      => "Transferred to {$assignmentLabel}. Orders: " . $validated['chronicle_text'],
            'authorising_user_id' => auth()->id(),
            'scope_target'        => 'IC',
        ]);

        return response()->json(['status' => 'success', 'message' => lexicon('msg_PersonaTransfer_Updated', 'Persona transferred and logged in chronological register!'), 'data' => $persona, 'redirect' => null], 200);
    }
}