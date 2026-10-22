<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Thread extends Model
{
    protected $fillable = ['type', 'title'];

    public function participants()
    {
        return $this->hasMany(ThreadParticipant::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    /**
     * Create an automated OOC system message thread for one or more recipients.
     */
    public static function createSystemMessage(?User $sender, iterable $recipients, string $body, string $subject = 'System Notification'): ?self
    {
        $senderId = $sender?->id ?? User::orderBy('id')->value('id');
        if (!$senderId) {
            return null;
        }

        $recipientIds = collect($recipients)
            ->map(fn($r) => $r instanceof User ? $r->id : (int) $r)
            ->filter(fn($id) => $id > 0 && $id !== $senderId)
            ->unique()
            ->values();

        if ($recipientIds->isEmpty()) {
            return null;
        }

        $thread = self::create([
            'type'  => 'OOC',
            'title' => $subject,
        ]);

        // Add sender participant (marked as read immediately)
        ThreadParticipant::create([
            'thread_id'    => $thread->id,
            'user_id'      => $senderId,
            'character_id' => null,
            'last_read_at' => now(),
        ]);

        // Add each recipient participant (unread)
        foreach ($recipientIds as $recipientId) {
            ThreadParticipant::create([
                'thread_id'    => $thread->id,
                'user_id'      => $recipientId,
                'character_id' => null,
                'last_read_at' => null,
            ]);
        }

        Message::create([
            'thread_id'    => $thread->id,
            'user_id'      => $senderId,
            'character_id' => null,
            'body'         => $body,
        ]);

        return $thread;
    }
}
