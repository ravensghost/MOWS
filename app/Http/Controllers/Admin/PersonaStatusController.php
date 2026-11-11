<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\Persona;
use App\Models\ChronologicalRegister;

class PersonaStatusController extends Controller
{
    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'persona_id'     => 'required|exists:personae,id',
            'persona_status' => 'required|in:active,loa,eloa,honoured_dead,memorial',
            'chronicle_text' => 'required|string',
        ]);

        $persona = Persona::findOrFail($validated['persona_id']);
        $persona->persona_status = $validated['persona_status'];
        $persona->save();

        Cache::forget('widget_roster_sections');

        $statusLabels = [
            'active'        => 'Active Duty',
            'loa'           => 'Leave of Absence (LOA)',
            'eloa'          => 'Extended Leave of Absence (ELOA)',
            'honoured_dead' => 'Honoured Dead',
            'memorial'      => 'Memorial',
        ];

        $statusName = $statusLabels[$validated['persona_status']] ?? $validated['persona_status'];

        ChronologicalRegister::create([
            'user_id'             => $persona->user_id,
            'persona_id'          => $persona->id,
            'register_event_mode' => 'status_change',
            'chronicle_text'      => "Status changed to {$statusName}. Reason: " . $validated['chronicle_text'],
            'authorising_user_id' => auth()->id(),
            'scope_target'        => 'BOTH',
        ]);

        return response()->json(['success' => true, 'message' => 'Character status updated and logged in chronological register!']);
    }
}
