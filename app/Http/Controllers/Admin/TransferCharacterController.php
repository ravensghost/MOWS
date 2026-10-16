<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\Character;
use App\Models\Position;
use App\Models\ServiceRecord;

class TransferCharacterController extends Controller
{
    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'character_id' => 'required|exists:characters,id',
            'position_id'  => 'required|exists:positions,id',
            'description'  => 'required|string',
        ]);

        $character = Character::findOrFail($validated['character_id']);
        $newPosition = Position::with('section')->findOrFail($validated['position_id']);

        $character->position_id = $newPosition->id;
        $character->save();

        Cache::forget('widget_roster_sections');

        $positionLabel = $newPosition->section
            ? "{$newPosition->name} ({$newPosition->section->name})"
            : $newPosition->name;

        ServiceRecord::create([
            'user_id'        => $character->user_id,
            'character_id'   => $character->id,
            'event_type'     => 'transfer',
            'description'    => "Transferred to {$positionLabel}. Orders: " . $validated['description'],
            'issued_by'      => auth()->id(),
            'display_target' => 'IC',
        ]);

        return response()->json(['success' => true, 'message' => 'Character transferred and logged in service record!']);
    }
}
