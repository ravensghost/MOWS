<?php

namespace Tests\Feature\Integrations;

use App\Models\User;
use App\Models\Chapter;
use App\Models\News;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DiscordIntegrationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_discord_integration_dispatches_webhooks_for_events(): void
    {
        Http::fake();

        $admin = User::factory()->create(['role' => 'webmaster']);
        $recipient = User::factory()->create();

        // 1. Test chapter publication triggers Discord webhook notification
        $this->actingAs($admin)->postJson('/admin/chapters', [
            'title'        => 'Stardate Log 01',
            'content'      => 'Initial exploration log.',
            'status'       => 'published',
            'discord_notify' => true,
        ]);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'discord.com/api/webhooks');
        });

        // 2. Test Private Message with @mention triggers Discord alert
        $this->actingAs($admin)->postJson('/messenger/new', [
            'recipient_id' => $recipient->id,
            'message'      => 'Commander, report to the bridge.',
            'mention_discord' => true,
        ]);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'discord.com/api/webhooks');
        });
    }
}
