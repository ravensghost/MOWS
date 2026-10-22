<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Character;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserAttribute;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DiscordIntegrationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_discord_integration_dispatches_webhooks_for_events_when_enabled(): void
    {
        Http::fake();

        Setting::updateOrCreate(['key' => 'notifications_discord_enabled'], ['value' => '1']);
        Setting::updateOrCreate(['key' => 'discord_webhook_chapters'], ['value' => 'https://discord.com/api/webhooks/test/chapters']);
        Setting::updateOrCreate(['key' => 'discord_webhook_alerts'], ['value' => 'https://discord.com/api/webhooks/test/alerts']);

        $author = User::factory()->create();
        $recipient = User::factory()->create();

        UserAttribute::updateOrCreate(
            ['user_id' => $recipient->id, 'key' => 'discord_id'],
            ['value' => '987654321012345678']
        );

        $book = Book::first() ?? Book::create(['title' => 'Test Book', 'ic_day' => 1, 'ic_time' => '0800']);
        $authorChar = Character::create([
            'user_id'        => $author->id,
            'character_type' => 'PC',
            'name'           => 'Commander Vanguard',
            'status'         => 'active',
        ]);

        // 1. Test chapter publication triggers Discord webhook notification
        $this->actingAs($author)->postJson('/chapters', [
            'book_id'             => $book->id,
            'active_character_id' => $authorChar->id,
            'chapter_type'        => 'mission',
            'ic_day'              => '1',
            'ic_time'             => '0800',
            'location'            => 'Observation Lounge',
            'rating_l'            => 1,
            'rating_s'            => 1,
            'rating_v'            => 1,
            'content'             => '<p>Initial exploration log.</p>',
            'status'              => 'published',
        ])->assertStatus(200);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'discord.com/api/webhooks/test/chapters');
        });

        // 2. Test Private Message with @mention triggers Discord alert
        $this->actingAs($author)->postJson('/messenger/new', [
            'type'       => 'OOC',
            'recipients' => [$recipient->id],
            'subject'    => 'Bridge Orders',
            'body'       => 'Commander, report to the bridge.',
        ])->assertStatus(200);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'discord.com/api/webhooks/test/alerts')
                && str_contains($request['content'], '<@987654321012345678>');
        });
    }
}
