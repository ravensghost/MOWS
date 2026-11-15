<?php

namespace Tests\Feature;

use App\Mail\MowsNotificationMail;
use App\Models\Configuration;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailIntegrationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_correspondence_sends_an_email_notification_when_enabled(): void
    {
        Mail::fake();

        Configuration::updateOrCreate(
            ['configuration_token' => 'notifications_email_enabled'],
            ['configuration_payload' => '1']
        );

        $sender = User::factory()->create();
        $recipient = User::factory()->create();

        $response = $this->actingAs($sender)->postJson('/correspondence/new', [
            'thread_mode' => 'OOC',
            'recipients' => [$recipient->id],
            'thread_title' => 'Subspace Transmission',
            'correspondence_text' => 'Incoming transmission.',
        ]);

        $response->assertOk();

        Mail::assertSent(MowsNotificationMail::class, function ($mail) use ($recipient) {
            return $mail->hasTo($recipient->email)
                && str_contains($mail->customSubject, 'New Private Message');
        });
    }
}
