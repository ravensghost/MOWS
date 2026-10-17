<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Thread;
use App\Models\Message;
use App\Models\ThreadParticipant;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class MessagingSecurityTest extends TestCase
{
    use DatabaseTransactions;

    public function test_user_can_start_new_conversation(): void
    {
        $sender = User::factory()->create(['role' => 'user']);
        $recipient = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($sender)->postJson('/messenger/new', [
            'recipients' => [$recipient->id],
            'subject'    => 'Classified Briefing',
            'body'       => 'Meet me in sector four.',
            'type'       => 'OOC', // Matches MessengerController validation rule
        ]);

        $response->assertStatus(200);

        // Verify thread and message were created in the database
        $this->assertDatabaseHas('threads', [
            'title' => 'Classified Briefing',
            'type'  => 'OOC',
        ]);

        $this->assertDatabaseHas('messages', [
            'body'    => 'Meet me in sector four.',
            'user_id' => $sender->id,
        ]);
    }
}
