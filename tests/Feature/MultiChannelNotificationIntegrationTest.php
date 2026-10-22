<?php

namespace Tests\Feature;

use App\Mail\MowsNotificationMail;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MultiChannelNotificationIntegrationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_private_message_dispatches_across_all_channels_when_enabled(): void
    {
        Http::fake();
        Mail::fake();

        Setting::updateOrCreate(['key' => 'notifications_discord_enabled'], ['value' => '1']);
        Setting::updateOrCreate(['key' => 'notifications_email_enabled'], ['value' => '1']);
        Setting::updateOrCreate(['key' => 'notifications_pm_enabled'], ['value' => '1']);
        Setting::updateOrCreate(['key' => 'discord_webhook_alerts'], ['value' => 'https://discord.com/api/webhooks/test/alerts']);

        $sender = User::factory()->create();
        $recipient = User::factory()->create();

        $response = $this->actingAs($sender)->postJson('/messenger/new', [
            'type'       => 'OOC',
            'recipients' => [$recipient->id],
            'subject'    => 'Urgent Briefing',
            'body'       => 'Please review the attached manifest.',
        ]);

        $response->assertStatus(200);

        // 1. Verify On-site thread & participant were created in database
        $this->assertDatabaseHas('threads', [
            'type'  => 'OOC',
            'title' => 'Urgent Briefing',
        ]);

        $this->assertDatabaseHas('thread_participants', [
            'user_id' => $recipient->id,
        ]);

        // 2. Verify Email notification was dispatched
        Mail::assertSent(MowsNotificationMail::class, function ($mail) use ($recipient) {
            return $mail->hasTo($recipient->email);
        });

        // 3. Verify Discord webhook alert was fired
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'discord.com/api/webhooks');
        });
    }

    public function test_kill_switches_suppress_external_notifications_when_disabled(): void
    {
        Http::fake();
        Mail::fake();

        Setting::updateOrCreate(['key' => 'notifications_discord_enabled'], ['value' => '0']);
        Setting::updateOrCreate(['key' => 'notifications_email_enabled'], ['value' => '0']);
        Setting::updateOrCreate(['key' => 'notifications_pm_enabled'], ['value' => '0']);

        $sender = User::factory()->create();
        $recipient = User::factory()->create();

        $response = $this->actingAs($sender)->postJson('/messenger/new', [
            'type'       => 'OOC',
            'recipients' => [$recipient->id],
            'subject'    => 'Silent Briefing',
            'body'       => 'No external pings should fire.',
        ]);

        $response->assertStatus(200);

        Mail::assertNothingSent();
        Http::assertNothingSent();
    }
}
