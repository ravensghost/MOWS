<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CorrespondenceThread extends Model
{
    protected $table = 'correspondence_threads';

    protected $fillable = [
        'thread_mode',
        'thread_title',
    ];

    public function participants(): HasMany
    {
        return $this->hasMany(CorrespondenceParticipant::class, 'correspondence_thread_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(CorrespondenceEntry::class, 'correspondence_thread_id');
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
            'thread_mode'  => 'OOC',
            'thread_title' => $subject,
        ]);

        CorrespondenceParticipant::create([
            'correspondence_thread_id' => $thread->id,
            'user_id' => $senderId,
            'persona_id' => null,
            'last_read_at' => now(),
        ]);

        foreach ($recipientIds as $recipientId) {
            CorrespondenceParticipant::create([
                'correspondence_thread_id' => $thread->id,
                'user_id' => $recipientId,
                'persona_id' => null,
                'last_read_at' => null,
            ]);
        }

        CorrespondenceEntry::create([
            'correspondence_thread_id' => $thread->id,
            'user_id' => $senderId,
            'persona_id' => null,
            'correspondence_text' => $body,
        ]);

        return $thread;
    }
}
