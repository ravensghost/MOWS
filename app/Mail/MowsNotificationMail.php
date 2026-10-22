<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MowsNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $customSubject;
    public string $viewName;
    public array $viewData;

    public function __construct(string $customSubject, string $viewName, array $viewData = [])
    {
        $this->customSubject = $customSubject;
        $this->viewName = $viewName;
        $this->viewData = $viewData;
    }

    public function build()
    {
        return $this->subject($this->customSubject)
            ->view($this->viewName)
            ->with($this->viewData);
    }
}
