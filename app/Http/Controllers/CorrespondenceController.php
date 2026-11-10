<?php

namespace App\Http\Controllers;

use App\Models\Persona;
use App\Models\Message;
use App\Models\Book;
use App\Models\Thread;
use App\Models\ThreadParticipant;
use App\Models\User;
use App\Models\UserAttribute;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class CorrespondenceController extends Controller
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
        $characters = Persona::where('persona_status', 'active')->select('id', 'persona_name as name', 'user_id')->get();
        $myCharacters = Persona::where('user_id', $userId)->where('persona_status', 'active')->get();

        $systemPage = Book::where('slug', 'messenger')
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
            'sender_character_id' => 'nullable|required_if:type,IC|exists:personae,id',
        ]);

        $thread = Thread::create([
            'type'  => $validated['type'],
            'title' => $validated['subject'],
        ]);

        ThreadParticipant::create([
            'thread_id'    => $thread->id,
            'user_id'      => auth()->id(),
            'character_id' => $validated['type'] === 'IC' ? $validated['sender_character_id'] : null,
            'last_read_at' => now(),
        ]);

        $characterUserMap = $validated['type'] === 'IC'
            ? Persona::whereIn('id', $validated['recipients'])->pluck('user_id', 'id')
            : collect();

        $notifiedUserIds = [];

        foreach ($validated['recipients'] as $recipientId) {
            $targetUserId = $validated['type'] === 'OOC'
                ? (int) $recipientId
                : ($characterUserMap[$recipientId] ?? null);

            if ($targetUserId) {
                ThreadParticipant::create([
                    'thread_id'    => $thread->id,
                    'user_id'      => $targetUserId,
                    'character_id' => $validated['type'] === 'IC' ? $recipientId : null,
                ]);
                $notifiedUserIds[] = $targetUserId;
            }
        }

        Message::create([
            'thread_id'    => $thread->id,
            'user_id'      => auth()->id(),
            'character_id' => $validated['type'] === 'IC' ? $validated['sender_character_id'] : null,
            'body'         => $validated['body'],
        ]);

        $this->dispatchExternalAlerts($thread, array_unique($notifiedUserIds), $validated['body']);

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
            'sender_character_id' => 'nullable|exists:personae,id',
        ]);

        Message::create([
            'thread_id'    => $thread->id,
            'user_id'      => auth()->id(),
            'character_id' => $thread->type === 'IC' ? ($validated['sender_character_id'] ?? null) : null,
            'body'         => $validated['body'],
        ]);

        $thread->touch();

        $recipientUserIds = $thread->participants()
            ->where('user_id', '!=', auth()->id())
            ->pluck('user_id')
            ->unique()
            ->toArray();

        $this->dispatchExternalAlerts($thread, $recipientUserIds, $validated['body']);

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

    protected function dispatchExternalAlerts(Thread $thread, array $recipientUserIds, string $body): void
    {
        if (empty($recipientUserIds)) {
            return;
        }

        $recipients = User::whereIn('id', $recipientUserIds)->get();
        $senderName = auth()->user()->name ?? 'A writer';
        $subjectLine = $thread->title ?: 'Direct Message';

        foreach ($recipients as $recipient) {
            if ($recipient->id === auth()->id()) {
                continue;
            }

            if (!empty($recipient->email)) {
                NotificationService::sendEmail(
                    $recipient->email,
                    "New Private Message: {$subjectLine}",
                    'emails.notification',
                    [
                        'heading'     => "New {$thread->type} Message from {$senderName}",
                        'messageText' => $body,
                        'actionUrl'   => url('/messenger/' . $thread->id),
                        'actionLabel' => 'Open Messenger',
                    ]
                );
            }

            $discordId = UserAttribute::where('user_id', $recipient->id)
                ->where('key', 'discord_id')
                ->value('value');
            $mention = !empty($discordId) ? "<@{$discordId}>" : $recipient->name;

            NotificationService::sendDiscord(
                'alerts',
                "✉️ **New {$thread->type} Message:** {$mention}, you have received a transmission from **{$senderName}** (*{$subjectLine}*)."
            );
        }
    }
}
