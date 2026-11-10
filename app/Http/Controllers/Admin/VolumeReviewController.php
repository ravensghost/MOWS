<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Volume;
use App\Models\ServiceRecord;
use App\Models\Persona;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VolumeReviewController extends Controller
{
    public function showModal(Volume $volume)
    {
        $folios = $volume->folios()->with(['charactersPresent.user', 'authorUser'])->get();

        $recipients = collect();

        foreach ($folios as $folio) {
            foreach ($folio->charactersPresent as $character) {
                if ($character->user) {
                    $recipients->push([
                        'type' => 'character',
                        'id' => $character->id,
                        'name' => $character->name . ' (' . $character->user->name . ') [Character]',
                    ]);
                }
            }
            if ($folio->authorUser) {
                $recipients->push([
                    'type' => 'user',
                    'id' => $folio->authorUser->id,
                    'name' => $folio->authorUser->name . ' (Co-Writer)',
                ]);
            }
        }

        return response()->json([
            'recipients' => $recipients->unique(fn($r) => $r['type'] . ':' . $r['id'])->values()
        ]);
    }

    public function store(Request $request, Volume $volume)
    {
        $request->validate([
            'event_type' => 'required|in:milestone,assignment,commendation,award',
            'description' => 'required|string|max:255',
            'recipients' => 'required|array',
            'recipients.*.id' => 'required|integer',
            'recipients.*.type' => 'required|in:character,user',
        ]);

        DB::transaction(function () use ($request) {
            foreach ($request->recipients as $recipient) {
                if ($recipient['type'] === 'character') {
                    $character = Persona::find($recipient['id']);
                    if ($character) {
                        ServiceRecord::create([
                            'character_id' => $character->id,
                            'user_id' => $character->user_id,
                            'issued_by' => auth()->id(),
                            'display_target' => 'IC',
                            'event_type' => $request->event_type,
                            'description' => $request->description,
                        ]);
                    }
                } else {
                    $user = User::find($recipient['id']);
                    if ($user) {
                        ServiceRecord::create([
                            'user_id' => $user->id,
                            'issued_by' => auth()->id(),
                            'display_target' => 'OOC',
                            'event_type' => $request->event_type,
                            'description' => $request->description,
                        ]);
                    }
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Service records and milestone citations successfully issued.'
        ]);
    }
}
