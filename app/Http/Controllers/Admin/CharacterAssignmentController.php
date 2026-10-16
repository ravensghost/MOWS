<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\Character;
use App\Models\Position;
use App\Models\Ranks;
use App\Models\ServiceRecord;

class CharacterAssignmentController extends Controller
{
    public function __invoke(Request $request, $id)
    {
        $validated = $request->validate([
            'position_id' => 'required|exists:positions,id',
            'rank_id'     => 'required|exists:ranks,id',
            'reason'      => 'nullable|string',
        ]);

        $character = Character::with(['rank', 'position.section'])->findOrFail($id);

        $oldRank = $character->rank;
        $oldPosition = $character->position;

        $rankChanged = (int) $character->rank_id !== (int) $validated['rank_id'];
        $positionChanged = (int) $character->position_id !== (int) $validated['position_id'];

        $character->update([
            'position_id' => $validated['position_id'],
            'rank_id'     => $validated['rank_id'],
        ]);

        $reasonSuffix = !empty($validated['reason'])
            ? ' Reason: ' . $validated['reason']
            : ' (Updated via Roster Management)';

        // 1. Automatically log Rank Change if rank_id was modified
        if ($rankChanged) {
            $newRank = Ranks::findOrFail($validated['rank_id']);
            $actionText = 'Rank changed to';

            if ($oldRank) {
                if ($newRank->sort_order > $oldRank->sort_order) {
                    $actionText = 'Promoted to';
                } elseif ($newRank->sort_order < $oldRank->sort_order) {
                    $actionText = 'Demoted to';
                }
            }

            ServiceRecord::create([
                'user_id'        => $character->user_id,
                'character_id'   => $character->id,
                'event_type'     => 'promotion',
                'description'    => "{$actionText} {$newRank->name}.{$reasonSuffix}",
                'issued_by'      => auth()->id(),
                'display_target' => 'IC',
            ]);
        }

        // 2. Automatically log Position Transfer if position_id was modified
        if ($positionChanged) {
            $newPosition = Position::with('section')->findOrFail($validated['position_id']);
            $newPosLabel = $newPosition->section
                ? "{$newPosition->name} ({$newPosition->section->name})"
                : $newPosition->name;

            $oldPosLabel = $oldPosition
                ? ($oldPosition->section ? "{$oldPosition->name} ({$oldPosition->section->name})" : $oldPosition->name)
                : 'Unassigned';

            ServiceRecord::create([
                'user_id'        => $character->user_id,
                'character_id'   => $character->id,
                'event_type'     => 'transfer',
                'description'    => "Transferred from {$oldPosLabel} to {$newPosLabel}.{$reasonSuffix}",
                'issued_by'      => auth()->id(),
                'display_target' => 'IC',
            ]);
        }

        Cache::forget('widget_roster_sections');

        $message = "Character '{$character->name}' successfully reassigned.";

        if ($request->wantsJson()) {
            return response()->json(['status' => 'success', 'success' => true, 'message' => $message]);
        }

        return back()->with('status', $message);
    }
}
