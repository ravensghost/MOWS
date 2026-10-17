<?php

namespace Tests\Feature\Integrations;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Mailable;
use Tests\TestCase;

class MultiChannelNotificationIntegrationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_private_message_dispatches_across_all_channels(): void
    {
        Http::fake();
        Mail::fake();

        $sender = User::factory()->create(['role' => 'writer']);
        $recipient = User::factory()->create();

        // Send Private Message triggering multi-channel notifications
        $response = $this->actingAs($sender)->postJson('/messenger/new', [
            'recipient_id' => $recipient->id,
            'subject'      => 'Urgent Briefing',
            'message'      => 'Please review the attached manifest.',
        ]);

        $response->assertStatus(200);

        // 1. Verify On-site notification was created in database
        $this->assertDatabaseHas('notifications', [
            'user_id' => $recipient->id,
            'type'    => 'private_message',
        ]);

        // 2. Verify Email notification was dispatched
        Mail::assertSent(function (Mailable $mail) {
            return true;
        });

        // 3. Verify Discord webhook alert was fired
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'discord.com/api/webhooks');
        });
    }
}
