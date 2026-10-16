<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Character;
use App\Models\Ranks;
use App\Models\ServiceRecord;

class PromoteCharacterController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'character_id' => 'required|exists:characters,id',
            'rank_id' => 'required|exists:ranks,id',
            'description' => 'required|string',
        ]);

        $character = Character::findOrFail($request->character_id);
        $newRank = Ranks::findOrFail($request->rank_id);
        $oldRank = $character->rank;

        $actionText = 'Rank changed to';
        if ($oldRank) {
            if ($newRank->sort_order > $oldRank->sort_order) {
                $actionText = 'Promoted to';
            } elseif ($newRank->sort_order < $oldRank->sort_order) {
                $actionText = 'Demoted to';
            }
        }

        $character->rank_id = $newRank->id;
        $character->save();

        \Illuminate\Support\Facades\Cache::forget('widget_roster_sections');

        ServiceRecord::create([
            'user_id' => $character->user_id,
            'character_id' => $character->id,
            'event_type' => 'promotion',
            'description' => "{$actionText} {$newRank->name}. Reason: " . $request->description,
            'issued_by' => auth()->id(),
            'display_target' => 'IC',
        ]);

        return response()->json(['success' => true, 'message' => 'Rank updated and logged in service record!']);
    }
}
