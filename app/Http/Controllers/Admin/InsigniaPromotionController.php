<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\Persona;
use App\Models\Insignia;
use App\Models\ChronologicalRegister;

class InsigniaPromotionController extends Controller
{
    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'persona_id'     => 'required|exists:personae,id',
            'insignia_id'    => 'required|exists:insignia,id',
            'chronicle_text' => 'required|string',
        ]);

        $persona = Persona::findOrFail($validated['persona_id']);
        $newInsignia = Insignia::findOrFail($validated['insignia_id']);
        $oldInsignia = $persona->insignia;

        $actionText = 'Rank changed to';
        if ($oldInsignia) {
            if ($newInsignia->insignia_sort_order > $oldInsignia->insignia_sort_order) {
                $actionText = 'Promoted to';
            } elseif ($newInsignia->insignia_sort_order < $oldInsignia->insignia_sort_order) {
                $actionText = 'Demoted to';
            }
        }

        $persona->insignia_id = $newInsignia->id;
        $persona->save();

        Cache::forget('widget_roster_sections');

        ChronologicalRegister::create([
            'user_id'             => $persona->user_id,
            'persona_id'          => $persona->id,
            'register_event_mode' => 'promotion',
            'chronicle_text'      => "{$actionText} {$newInsignia->insignia_title}. Reason: " . $validated['chronicle_text'],
            'authorising_user_id' => auth()->id(),
            'scope_target'        => 'IC',
        ]);

        return response()->json(['success' => true, 'message' => 'Rank updated and logged in chronological register!']);
    }
}
