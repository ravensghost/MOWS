<?php

namespace Tests\Feature;

use App\Mail\MowsNotificationMail;
use App\Models\Configuration;
use App\Models\CorrespondenceThread;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MultiChannelNotificationIntegrationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_correspondence_dispatches_email_and_discord_notifications_when_enabled(): void
    {
        Http::fake();
        Mail::fake();

        Configuration::updateOrCreate(
            ['configuration_token' => 'notifications_discord_enabled'],
            ['configuration_payload' => '1']
        );
        Configuration::updateOrCreate(
            ['configuration_token' => 'notifications_email_enabled'],
            ['configuration_payload' => '1']
        );
        Configuration::updateOrCreate(
            ['configuration_token' => 'discord_webhook_alerts'],
            ['configuration_payload' => 'https://discord.com/api/webhooks/test/alerts']
        );

        $sender = User::factory()->create();
        $recipient = User::factory()->create();

        $response = $this->actingAs($sender)->postJson('/correspondence/new', [
            'thread_mode' => 'OOC',
            'recipients' => [$recipient->id],
            'thread_title' => 'Urgent Briefing',
            'correspondence_text' => 'Please review the attached manifest.',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'success');

        $thread = CorrespondenceThread::where('thread_title', 'Urgent Briefing')->firstOrFail();
        $this->assertDatabaseHas('correspondence_participants', [
            'correspondence_thread_id' => $thread->id,
            'user_id' => $recipient->id,
        ]);

        Mail::assertSent(MowsNotificationMail::class, function ($mail) use ($recipient) {
            return $mail->hasTo($recipient->email);
        });

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'discord.com/api/webhooks');
        });
    }

    public function test_kill_switches_suppress_external_notifications_when_disabled(): void
    {
        Http::fake();
        Mail::fake();

        Configuration::updateOrCreate(
            ['configuration_token' => 'notifications_discord_enabled'],
            ['configuration_payload' => '0']
        );
        Configuration::updateOrCreate(
            ['configuration_token' => 'notifications_email_enabled'],
            ['configuration_payload' => '0']
        );

        $sender = User::factory()->create();
        $recipient = User::factory()->create();

        $response = $this->actingAs($sender)->postJson('/correspondence/new', [
            'thread_mode' => 'OOC',
            'recipients' => [$recipient->id],
            'thread_title' => 'Silent Briefing',
            'correspondence_text' => 'No external pings should fire.',
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('correspondence_threads', [
            'thread_title' => 'Silent Briefing',
            'thread_mode' => 'OOC',
        ]);
        Mail::assertNothingSent();
        Http::assertNothingSent();
    }
}
