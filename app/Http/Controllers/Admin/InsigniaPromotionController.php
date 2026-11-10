<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Persona;
use App\Models\Ranks;
use App\Models\ServiceRecord;

class InsigniaPromotionController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'character_id' => 'required|exists:personae,id',
            'rank_id'      => 'required|exists:ranks,id',
            'description'  => 'required|string',
        ]);

        $persona = Persona::findOrFail($request->character_id);
        $newRank = Ranks::findOrFail($request->rank_id);
        $oldRank = $persona->rank;

        $actionText = 'Rank changed to';
        if ($oldRank) {
            if ($newRank->sort_order > $oldRank->sort_order) {
                $actionText = 'Promoted to';
            } elseif ($newRank->sort_order < $oldRank->sort_order) {
                $actionText = 'Demoted to';
            }
        }

        $persona->insignia_id = $newRank->id;
        $persona->save();

        \Illuminate\Support\Facades\Cache::forget('widget_roster_sections');

        ServiceRecord::create([
            'user_id'        => $persona->user_id,
            'character_id'   => $persona->id,
            'event_type'     => 'promotion',
            'description'    => "{$actionText} {$newRank->name}. Reason: " . $request->description,
            'issued_by'      => auth()->id(),
            'display_target' => 'IC',
        ]);

        return response()->json(['success' => true, 'message' => 'Rank updated and logged in service record!']);
    }
}
