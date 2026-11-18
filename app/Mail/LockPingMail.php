<?php

namespace App\Mail;

use App\Models\Configuration;
use App\Models\Folio;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LockPingMail extends Mailable
{
    use Queueable, SerializesModels;

    public Folio $folio;

    public User $requester;

    public function __construct(Folio $folio, User $requester)
    {
        $this->folio = $folio;
        $this->requester = $requester;
    }

    public function build()
    {
        $prefix = Configuration::where('configuration_token', 'email_subject_prefix')
            ->value('configuration_payload') ?? '[MOWS Engine]';

        $subject = trim($prefix . ' Co-Writing Lock Ping: Folio #' . $this->folio->id);

        return $this->subject($subject)
            ->view('emails.folio_lock_ping')
            ->with([
                'folio'     => $this->folio,
                'requester' => $this->requester,
                'chapter'   => $this->folio,
                'sender'    => $this->requester,
            ]);
    }
}