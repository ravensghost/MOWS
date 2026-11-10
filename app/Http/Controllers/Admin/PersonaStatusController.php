<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\Persona;
use App\Models\ServiceRecord;

class PersonaStatusController extends Controller
{
    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'character_id' => 'required|exists:personae,id',
            'status'       => 'required|in:active,loa,eloa,honoured_dead,memorial',
            'description'  => 'required|string',
        ]);

        $persona = Persona::findOrFail($validated['character_id']);
        $persona->persona_status = $validated['status'];
        $persona->save();

        Cache::forget('widget_roster_sections');

        $statusLabels = [
            'active'        => 'Active Duty',
            'loa'           => 'Leave of Absence (LOA)',
            'eloa'          => 'Extended Leave of Absence (ELOA)',
            'honoured_dead' => 'Honoured Dead',
            'memorial'      => 'Memorial',
        ];

        $statusName = $statusLabels[$validated['status']] ?? $validated['status'];

        ServiceRecord::create([
            'user_id'        => $persona->user_id,
            'character_id'   => $persona->id,
            'event_type'     => 'status_change',
            'description'    => "Status changed to {$statusName}. Reason: " . $validated['description'],
            'issued_by'      => auth()->id(),
            'display_target' => 'BOTH',
        ]);

        return response()->json(['success' => true, 'message' => 'Character status updated and logged in service record!']);
    }
}
