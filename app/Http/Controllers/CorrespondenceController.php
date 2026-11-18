<?php

namespace App\Http\Controllers;

use App\Models\CorrespondenceEntry;
use App\Models\CorrespondenceParticipant;
use App\Models\CorrespondenceThread;
use App\Models\Persona;
use App\Models\User;
use App\Models\UserAttribute;
use App\Services\MultiChannelNotificationService;
use Illuminate\Http\Request;

class CorrespondenceController extends Controller
{
    public function index(Request $request, $threadId = null)
    {
        $userId = auth()->id();

        $threads = CorrespondenceThread::whereHas('participants', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })
            ->with(['participants.user', 'participants.persona', 'entries.user', 'entries.persona'])
            ->orderByDesc('updated_at')
            ->get();

        $activeThread = $threadId
            ? CorrespondenceThread::with(['participants.user', 'participants.persona', 'entries.user', 'entries.persona'])->findOrFail($threadId)
            : null;

        if ($activeThread) {
            CorrespondenceParticipant::where('correspondence_thread_id', $activeThread->id)
                ->where('user_id', $userId)
                ->update(['last_read_at' => now()]);
        }

        $users = User::where('id', '!=', $userId)
            ->orderBy('name')
            ->get(['id', 'name']);

        $characters = Persona::where('persona_status', 'active')
            ->orderBy('persona_name')
            ->get(['id', 'persona_name', 'user_id'])
            ->map(function (Persona $p) {
                $p->setAttribute('name', $p->persona_name);
                return $p;
            });

        $myCharacters = Persona::where('user_id', $userId)
            ->where('persona_status', 'active')
            ->orderBy('persona_name')
            ->get();

        if ($request->wantsJson()) {
            return response()->json([
                'status'   => 'success',
                'message'  => lexicon('msg_messenger_Loaded', 'Correspondence threads retrieved.'),
                'data'     => [
                    'threads'      => $threads,
                    'activeThread' => $activeThread,
                    'users'        => $users,
                    'characters'   => $characters,
                    'myCharacters' => $myCharacters,
                ],
                'redirect' => null,
            ], 200);
        }

        return redirect()->route('dashboard');
    }

    public function store(Request $request)
    {
        if (! $request->filled('persona_id') && $request->filled('sender_persona_id')) {
            $request->merge(['persona_id' => $request->input('sender_persona_id')]);
        }

        $validated = $request->validate([
            'thread_mode'         => 'required|in:IC,OOC',
            'recipients'          => 'required|array|min:1',
            'thread_title'        => 'nullable|string|max:255',
            'correspondence_text' => 'required|string',
            'persona_id'          => 'nullable|required_if:thread_mode,IC|exists:personae,id',
        ]);

        $senderPersonaId = $validated['thread_mode'] === 'IC' ? ($validated['persona_id'] ?? null) : null;
        $cleanText = function_exists('purify_html')
            ? purify_html($validated['correspondence_text'])
            : trim(strip_tags($validated['correspondence_text']));

        $thread = CorrespondenceThread::create([
            'thread_mode'  => $validated['thread_mode'],
            'thread_title' => $validated['thread_title'] ?? null,
        ]);

        CorrespondenceParticipant::create([
            'correspondence_thread_id' => $thread->id,
            'user_id'                  => auth()->id(),
            'persona_id'               => $senderPersonaId,
            'last_read_at'             => now(),
        ]);

        $characterUserMap = $validated['thread_mode'] === 'IC'
            ? Persona::whereIn('id', $validated['recipients'])->pluck('user_id', 'id')
            : collect();

        $notifiedUserIds = [];

        foreach ($validated['recipients'] as $recipientId) {
            $targetUserId = $validated['thread_mode'] === 'OOC'
                ? (int) $recipientId
                : (int) ($characterUserMap[$recipientId] ?? 0);

            if ($targetUserId > 0) {
                CorrespondenceParticipant::firstOrCreate([
                    'correspondence_thread_id' => $thread->id,
                    'user_id'                  => $targetUserId,
                    'persona_id'               => $validated['thread_mode'] === 'IC' ? (int) $recipientId : null,
                ]);
                if ($targetUserId !== (int) auth()->id()) {
                    $notifiedUserIds[] = $targetUserId;
                }
            }
        }

        CorrespondenceEntry::create([
            'correspondence_thread_id' => $thread->id,
            'user_id'                  => auth()->id(),
            'persona_id'               => $senderPersonaId,
            'correspondence_text'      => $cleanText,
        ]);

        $this->dispatchExternalAlerts($thread, array_unique($notifiedUserIds), $cleanText);

        return response()->json([
            'status'   => 'success',
            'message'  => lexicon('msg_messenger_MessageSent', 'Message sent successfully.'),
            'data'     => $thread->load(['participants.user', 'participants.persona', 'entries']),
            'redirect' => null,
        ], 200);
    }

    public function reply(Request $request, CorrespondenceThread $thread)
    {
        if (! $request->filled('persona_id') && $request->filled('sender_persona_id')) {
            $request->merge(['persona_id' => $request->input('sender_persona_id')]);
        }

        $validated = $request->validate([
            'correspondence_text' => 'required|string',
            'persona_id'          => 'nullable|exists:personae,id',
        ]);

        $senderPersonaId = $thread->thread_mode === 'IC' ? ($validated['persona_id'] ?? null) : null;
        $cleanText = function_exists('purify_html')
            ? purify_html($validated['correspondence_text'])
            : trim(strip_tags($validated['correspondence_text']));

        $entry = CorrespondenceEntry::create([
            'correspondence_thread_id' => $thread->id,
            'user_id'                  => auth()->id(),
            'persona_id'               => $senderPersonaId,
            'correspondence_text'      => $cleanText,
        ]);

        $thread->touch();

        CorrespondenceParticipant::where('correspondence_thread_id', $thread->id)
            ->where('user_id', auth()->id())
            ->update(['last_read_at' => now()]);

        $recipientUserIds = $thread->participants()
            ->where('user_id', '!=', auth()->id())
            ->pluck('user_id')
            ->unique()
            ->toArray();

        $this->dispatchExternalAlerts($thread, $recipientUserIds, $cleanText);

        return response()->json([
            'status'   => 'success',
            'message'  => lexicon('msg_messenger_ReplySent', 'Reply sent.'),
            'data'     => $entry,
            'redirect' => null,
        ], 200);
    }

    protected function dispatchExternalAlerts(CorrespondenceThread $thread, array $recipientUserIds, string $correspondenceText): void
    {
        if (empty($recipientUserIds)) {
            return;
        }

        $senderName = auth()->user()?->name ?? 'System';
        $subjectLine = $thread->thread_title ?: 'Direct Message';
        $recipients = User::whereIn('id', $recipientUserIds)->get();

        foreach ($recipients as $recipient) {
            if (! empty($recipient->email)) {
                MultiChannelNotificationService::sendEmail(
                    $recipient->email,
                    "New {$thread->thread_mode} Message: {$subjectLine}",
                    'emails.notification',
                    [
                        'title' => "New {$thread->thread_mode} Transmission",
                        'body'  => "{$senderName} sent you a message in '{$subjectLine}':\n\n" . strip_tags($correspondenceText),
                    ]
                );
            }

            $discordId = UserAttribute::where('user_id', $recipient->id)
                ->where('attribute_key', 'discord_id')
                ->value('attribute_value');

            $mention = ! empty($discordId) ? "<@{$discordId}>" : $recipient->name;

            MultiChannelNotificationService::sendDiscord(
                'alerts',
                "✉️ **New {$thread->thread_mode} Message:** {$mention}, you have received a transmission from **{$senderName}** (*{$subjectLine}*)."
            );
        }
    }
}