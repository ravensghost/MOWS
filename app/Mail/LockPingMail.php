<?php

namespace App\Mail;

use App\Models\Chapter;
use App\Models\Configuration;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LockPingMail extends Mailable
{
    use Queueable, SerializesModels;

    public Chapter $chapter;
    public User $sender;

    public function __construct(Chapter $chapter, User $sender)
    {
        $this->chapter = $chapter;
        $this->sender = $sender;
    }

    public function build()
    {
        $prefix = Configuration::where('configuration_token', 'email_subject_prefix')->value('configuration_payload') ?? '[MOWS Engine]';
        $subject = trim($prefix . ' Co-Writing Lock Ping: Chapter #' . $this->chapter->id);

        return $this->subject($subject)
            ->view('emails.lock_ping')
            ->with([
                'chapter' => $this->chapter,
                'sender'  => $this->sender,
            ]);
    }
}
