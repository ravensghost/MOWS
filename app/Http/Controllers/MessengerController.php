<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Thread;
use App\Models\ThreadParticipant;
use App\Models\Message;
use App\Models\User;
use App\Models\Character;
use App\Models\Page;

class MessengerController extends Controller
{
    public function index($threadId = null)
    {
        $userId = auth()->id();

        $threads = Thread::whereHas('participants', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })->with(['participants.user', 'participants.character', 'messages.user', 'messages.character'])->orderByDesc('updated_at')->get();

        $activeThread = $threadId
            ? Thread::with(['participants.user', 'participants.character', 'messages.user', 'messages.character'])->findOrFail($threadId)
            : null;

        $users = User::where('id', '!=', $userId)->select('id', 'name')->get();
        $characters = Character::where('status', 'active')->select('id', 'name', 'user_id')->get();
        $myCharacters = Character::where('user_id', $userId)->where('status', 'active')->get();

        $systemPage = Page::where('slug', 'messenger')
            ->where('is_published', true)
            ->with(['layout.blocks', 'widgets' => function ($q) {
                $q->orderBy('page_widget.sort_order');
            }])
            ->first();

        return view('messenger', compact('threads', 'activeThread', 'users', 'characters', 'myCharacters', 'systemPage'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type'                => 'required|in:IC,OOC',
            'recipients'          => 'required|array|min:1',
            'subject'             => 'nullable|string|max:255',
            'body'                => 'required|string',
            'sender_character_id' => 'nullable|required_if:type,IC|exists:characters,id',
        ]);

        $thread = Thread::create([
            'type'  => $validated['type'],
            'title' => $validated['subject'],
        ]);

        ThreadParticipant::create([
            'thread_id'    => $thread->id,
            'user_id'      => auth()->id(),
            'character_id' => $validated['type'] === 'IC' ? $validated['sender_character_id'] : null,
        ]);

        $characterUserMap = $validated['type'] === 'IC'
            ? Character::whereIn('id', $validated['recipients'])->pluck('user_id', 'id')
            : collect();

        foreach ($validated['recipients'] as $recipientId) {
            $targetUserId = $validated['type'] === 'OOC'
                ? $recipientId
                : ($characterUserMap[$recipientId] ?? null);

            if ($targetUserId) {
                ThreadParticipant::create([
                    'thread_id'    => $thread->id,
                    'user_id'      => $targetUserId,
                    'character_id' => $validated['type'] === 'IC' ? $recipientId : null,
                ]);
            }
        }

        Message::create([
            'thread_id'    => $thread->id,
            'user_id'      => auth()->id(),
            'character_id' => $validated['type'] === 'IC' ? $validated['sender_character_id'] : null,
            'body'         => $validated['body'],
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'status'    => 'success',
                'success'   => true,
                'thread_id' => $thread->id,
                'message'   => lexicon('msg_messenger_MessageSent', 'Message sent successfully.'),
            ]);
        }

        return back();
    }

    public function reply(Request $request, Thread $thread)
    {
        $validated = $request->validate([
            'body'                => 'required|string',
            'sender_character_id' => 'nullable|exists:characters,id',
        ]);

        Message::create([
            'thread_id'    => $thread->id,
            'user_id'      => auth()->id(),
            'character_id' => $thread->type === 'IC' ? $validated['sender_character_id'] : null,
            'body'         => $validated['body'],
        ]);

        $thread->touch();

        if ($request->wantsJson()) {
            return response()->json([
                'status'    => 'success',
                'success'   => true,
                'thread_id' => $thread->id,
                'message'   => lexicon('msg_messenger_ReplySent', 'Reply sent.'),
            ]);
        }

        return back();
    }
}
