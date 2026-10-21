<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\ServiceRecord;
use App\Models\Character;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookReviewController extends Controller
{
    public function showModal(Book $book)
    {
        $chapters = $book->chapters()->with(['charactersPresent.user', 'authorUser'])->get();

        $recipients = collect();

        foreach ($chapters as $chapter) {
            foreach ($chapter->charactersPresent as $character) {
                if ($character->user) {
                    $recipients->push([
                        'type' => 'character',
                        'id' => $character->id,
                        'name' => $character->name . ' (' . $character->user->name . ') [Character]',
                    ]);
                }
            }
            if ($chapter->authorUser) {
                $recipients->push([
                    'type' => 'user',
                    'id' => $chapter->authorUser->id,
                    'name' => $chapter->authorUser->name . ' (Co-Writer)',
                ]);
            }
        }

        return response()->json([
            'recipients' => $recipients->unique(fn($r) => $r['type'] . ':' . $r['id'])->values()
        ]);
    }

    public function store(Request $request, Book $book)
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
                    $character = Character::find($recipient['id']);
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