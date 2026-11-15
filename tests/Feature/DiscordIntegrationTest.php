<?php

namespace Tests\Feature;

use App\Models\Configuration;
use App\Models\Persona;
use App\Models\PublicationState;
use App\Models\Shelf;
use App\Models\User;
use App\Models\UserAttribute;
use App\Models\Volume;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DiscordIntegrationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_publishing_a_folio_dispatches_the_chapters_webhook(): void
    {
        Http::fake();

        Configuration::updateOrCreate(
            ['configuration_token' => 'notifications_discord_enabled'],
            ['configuration_payload' => '1']
        );
        Configuration::updateOrCreate(
            ['configuration_token' => 'discord_webhook_chapters'],
            ['configuration_payload' => 'https://discord.com/api/webhooks/test/chapters']
        );

        $author = User::factory()->create();
        $persona = Persona::create([
            'user_id' => $author->id,
            'persona_name' => 'Commander Vanguard',
            'persona_status' => 'active',
        ]);
        $shelf = Shelf::create(['shelf_title' => 'Test Shelf', 'shelf_sort_order' => 1]);
        $publicationState = PublicationState::create([
            'state_title' => 'Active',
            'book_url' => 'active',
            'state_sort_order' => 1,
        ]);
        $volume = Volume::create([
            'shelf_id' => $shelf->id,
            'publication_state_id' => $publicationState->id,
            'volume_title' => 'Test Volume',
            'volume_sort_order' => 1,
        ]);

        $response = $this->actingAs($author)->postJson('/folios', [
            'volume_id' => $volume->id,
            'active_persona_id' => $persona->id,
            'folio_mode' => 'mission',
            'ic_day' => 1,
            'ic_time' => '0800',
            'scene_location' => 'Observation Lounge',
            'content_tier_l' => 1,
            'content_tier_s' => 1,
            'content_tier_v' => 1,
            'folio_text' => '<p>Initial exploration log.</p>',
            'folio_status' => 'published',
        ]);

        $response->assertOk();

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'discord.com/api/webhooks/test/chapters');
        });
    }

    public function test_correspondence_dispatches_a_discord_alert_to_the_recipient(): void
    {
        Http::fake();

        Configuration::updateOrCreate(
            ['configuration_token' => 'notifications_discord_enabled'],
            ['configuration_payload' => '1']
        );
        Configuration::updateOrCreate(
            ['configuration_token' => 'discord_webhook_alerts'],
            ['configuration_payload' => 'https://discord.com/api/webhooks/test/alerts']
        );

        $sender = User::factory()->create();
        $recipient = User::factory()->create();
        UserAttribute::updateOrCreate(
            ['user_id' => $recipient->id, 'attribute_key' => 'discord_id'],
            ['attribute_value' => '987654321012345678']
        );

        $response = $this->actingAs($sender)->postJson('/correspondence/new', [
            'thread_mode' => 'OOC',
            'recipients' => [$recipient->id],
            'thread_title' => 'Bridge Orders',
            'correspondence_text' => 'Commander, report to the bridge.',
        ]);

        $response->assertOk();
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'discord.com/api/webhooks/test/alerts')
                && str_contains($request['content'], '<@987654321012345678>');
        });
    }
}
