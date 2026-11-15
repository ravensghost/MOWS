<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\CorrespondenceEntry;
use App\Models\CorrespondenceParticipant;
use App\Models\CorrespondenceThread;
use App\Models\Persona;
use App\Models\User;
use App\Models\UserAttribute;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class CorrespondenceController extends Controller
{
    public function index($threadId = null)
    {
        $userId = auth()->id();

        $threads = CorrespondenceThread::whereHas('participants', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })->with(['participants.user', 'participants.persona', 'messages.user', 'messages.persona'])->orderByDesc('updated_at')->get();

        $activeThread = $threadId
            ? CorrespondenceThread::with(['participants.user', 'participants.persona', 'messages.user', 'messages.persona'])->findOrFail($threadId)
            : null;

        $users = User::where('id', '!=', $userId)->select('id', 'user_handle')->get();
        $characters = Persona::where('persona_status', 'active')->select('id', 'persona_name', 'user_id')->get();
        $myCharacters = Persona::where('user_id', $userId)->where('persona_status', 'active')->get();

        $systemBook = Book::where('book_url', 'messenger')
            ->where('is_published', true)
            ->with([
                'grid.gridModules',
                'modules' => function ($q) {
                    $q->orderBy('module_sort_order');
                },
            ])
            ->first();

        return view('messenger', compact('threads', 'activeThread', 'users', 'characters', 'myCharacters', 'systemBook'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'thread_mode' => 'required|in:IC,OOC',
            'recipients' => 'required|array|min:1',
            'thread_title' => 'nullable|string|max:255',
            'correspondence_text' => 'required|string',
            'persona_id' => 'nullable|required_if:thread_mode,IC|exists:personae,id',
        ]);

        $thread = CorrespondenceThread::create([
            'thread_mode' => $validated['thread_mode'],
            'thread_title' => $validated['thread_title'] ?? null,
        ]);

        CorrespondenceParticipant::create([
            'correspondence_thread_id' => $thread->id,
            'user_id' => auth()->id(),
            'persona_id' => $validated['thread_mode'] === 'IC' ? $validated['persona_id'] : null,
            'last_read_at' => now(),
        ]);

        $characterUserMap = $validated['thread_mode'] === 'IC'
            ? Persona::whereIn('id', $validated['recipients'])->pluck('user_id', 'id')
            : collect();

        $notifiedUserIds = [];

        foreach ($validated['recipients'] as $recipientId) {
            $targetUserId = $validated['thread_mode'] === 'OOC'
                ? (int) $recipientId
                : ($characterUserMap[$recipientId] ?? null);

            if ($targetUserId) {
                CorrespondenceParticipant::create([
                    'correspondence_thread_id' => $thread->id,
                    'user_id' => $targetUserId,
                    'persona_id' => $validated['thread_mode'] === 'IC' ? $recipientId : null,
                ]);
                $notifiedUserIds[] = $targetUserId;
            }
        }

        CorrespondenceEntry::create([
            'correspondence_thread_id' => $thread->id,
            'user_id' => auth()->id(),
            'persona_id' => $validated['thread_mode'] === 'IC' ? $validated['persona_id'] : null,
            'correspondence_text' => $validated['correspondence_text'],
        ]);

        $this->dispatchExternalAlerts($thread, array_unique($notifiedUserIds), $validated['correspondence_text']);

        return response()->json(['status' => 'success', 'message' => lexicon('msg_messenger_MessageSent', 'Message sent successfully.'), 'data' => $thread, 'redirect' => null], 200);
    }

    public function reply(Request $request, CorrespondenceThread $thread)
    {
        $validated = $request->validate([
            'correspondence_text' => 'required|string',
            'persona_id' => 'nullable|exists:personae,id',
        ]);

        $entry = CorrespondenceEntry::create([
            'correspondence_thread_id' => $thread->id,
            'user_id' => auth()->id(),
            'persona_id' => $thread->thread_mode === 'IC' ? ($validated['persona_id'] ?? null) : null,
            'correspondence_text' => $validated['correspondence_text'],
        ]);

        $thread->touch();

        $recipientUserIds = $thread->participants()
            ->where('user_id', '!=', auth()->id())
            ->pluck('user_id')
            ->unique()
            ->toArray();

        $this->dispatchExternalAlerts($thread, $recipientUserIds, $validated['correspondence_text']);

        return response()->json(['status' => 'success', 'message' => lexicon('msg_messenger_ReplySent', 'Reply sent.'), 'data' => $entry, 'redirect' => null], 200);
    }

    protected function dispatchExternalAlerts(CorrespondenceThread $thread, array $recipientUserIds, string $correspondenceText): void
    {
        if (empty($recipientUserIds)) {
            return;
        }

        $recipients = User::whereIn('id', $recipientUserIds)->get();
        $senderName = auth()->user()->user_handle ?? 'A writer';
        $subjectLine = $thread->thread_title ?: 'Direct Message';

        foreach ($recipients as $recipient) {
            if ($recipient->id === auth()->id()) {
                continue;
            }

            if (! empty($recipient->email)) {
                NotificationService::sendEmail(
                    $recipient->email,
                    "New Private Message: {$subjectLine}",
                    'emails.notification',
                    [
                        'heading' => "New {$thread->thread_mode} Message from {$senderName}",
                        'messageText' => $correspondenceText,
                        'actionUrl' => url('/messenger/'.$thread->id),
                        'actionLabel' => 'Open Messenger',
                    ]
                );
            }

            $discordId = UserAttribute::where('user_id', $recipient->id)
                ->where('key', 'discord_id')
                ->value('value');
            $mention = ! empty($discordId) ? "<@{$discordId}>" : $recipient->user_handle;

            NotificationService::sendDiscord(
                'alerts',
                "✉️ **New {$thread->thread_mode} Message:** {$mention}, you have received a transmission from **{$senderName}** (*{$subjectLine}*)."
            );
        }
    }
}
