<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChronologicalRegister;
use App\Models\Folio;
use App\Models\Persona;
use App\Models\User;
use App\Models\Volume;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VolumeReviewController extends Controller
{
    private function authoriseReviewAccess(): void
    {
        $user = auth()->user();

        abort_unless(
            $user && ($user->can('manage_stories') || $user->can('manage_awards') || $user->can('issue_awards') || $user->can('access-admin')),
            403,
            lexicon('msg_Story_Unauthorised', 'Unauthorised access to Volume Review.')
        );
    }

    public function showModal(Volume $volume): JsonResponse
    {
        $this->authoriseReviewAccess();

        $folios = $volume->folios()->with(['personae.user', 'authorUser'])->get();

        $recipients = collect();

        foreach ($folios as $folio) {
            /** @var Folio $folio */
            $personae = $folio->personae ?? collect();

            foreach ($personae as $persona) {
                if ($persona->user) {
                    $recipients->push([
                        'type' => 'character',
                        'id'   => $persona->id,
                        'name' => $persona->persona_name . ' (' . $persona->user->name . ') [Character]',
                    ]);
                }
            }

            if ($folio->authorUser) {
                $recipients->push([
                    'type' => 'user',
                    'id'   => $folio->authorUser->id,
                    'name' => $folio->authorUser->name . ' (Co-Writer)',
                ]);
            }
        }

        $payload = [
            'volume_id'       => $volume->id,
            'volume_title'    => $volume->volume_title,
            'volume_synopsis' => $volume->volume_synopsis,
            'recipients'      => $recipients->unique(fn (array $r) => $r['type'] . ':' . $r['id'])->values(),
        ];

        return response()->json([
            'status'   => 'success',
            'message'  => lexicon('msg_Story_ReviewDataLoaded', 'Volume review data loaded successfully.'),
            'data'     => $payload,
            'redirect' => null,
        ], 200);
    }

    public function store(Request $request, Volume $volume): JsonResponse
    {
        $this->authoriseReviewAccess();

        $validated = $request->validate([
            'register_event_mode' => 'nullable|in:milestone,assignment,commendation,award',
            'chronicle_text'      => 'nullable|string|max:255',
            'volume_synopsis'     => 'nullable|string|max:255',
            'recipients'          => 'required|array',
            'recipients.*.id'     => 'required|integer',
            'recipients.*.type'   => 'required|in:character,user',
        ]);

        $eventMode = $validated['register_event_mode'] ?? 'milestone';
        $citationText = $validated['chronicle_text']
            ?? $validated['volume_synopsis']
            ?? $volume->volume_title;

        $createdRecords = [];

        DB::transaction(function () use ($validated, $eventMode, $citationText, &$createdRecords) {
            foreach ($validated['recipients'] as $recipient) {
                if ($recipient['type'] === 'character') {
                    $persona = Persona::find($recipient['id']);
                    if ($persona) {
                        $createdRecords[] = ChronologicalRegister::create([
                            'persona_id'          => $persona->id,
                            'user_id'             => $persona->user_id,
                            'authorising_user_id' => auth()->id(),
                            'scope_target'        => 'IC',
                            'register_event_mode' => $eventMode,
                            'chronicle_text'      => $citationText,
                        ]);
                    }
                } else {
                    $user = User::find($recipient['id']);
                    if ($user) {
                        $createdRecords[] = ChronologicalRegister::create([
                            'user_id'             => $user->id,
                            'authorising_user_id' => auth()->id(),
                            'scope_target'        => 'OOC',
                            'register_event_mode' => $eventMode,
                            'chronicle_text'      => $citationText,
                        ]);
                    }
                }
            }
        });

        return response()->json([
            'status'   => 'success',
            'message'  => lexicon('msg_Story_ServiceRecordsIssued', 'Service records and milestone citations successfully issued.'),
            'data'     => $createdRecords,
            'redirect' => null,
        ], 200);
    }
}