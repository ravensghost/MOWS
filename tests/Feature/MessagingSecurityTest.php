<?php

namespace Tests\Feature;

use App\Models\CorrespondenceEntry;
use App\Models\CorrespondenceThread;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class MessagingSecurityTest extends TestCase
{
    use DatabaseTransactions;

    public function test_user_can_start_correspondence_with_another_user(): void
    {
        $sender = User::factory()->create(['role' => 'user']);
        $recipient = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($sender)->postJson('/correspondence/new', [
            'thread_mode' => 'OOC',
            'recipients' => [$recipient->id],
            'thread_title' => 'Classified Briefing',
            'correspondence_text' => 'Meet me in sector four.',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.thread_title', 'Classified Briefing');

        $thread = CorrespondenceThread::where('thread_title', 'Classified Briefing')->firstOrFail();
        $this->assertDatabaseHas('correspondence_threads', [
            'id' => $thread->id,
            'thread_mode' => 'OOC',
        ]);
        $this->assertDatabaseHas('correspondence_entries', [
            'correspondence_thread_id' => $thread->id,
            'user_id' => $sender->id,
            'correspondence_text' => 'Meet me in sector four.',
        ]);
        $this->assertDatabaseHas('correspondence_participants', [
            'correspondence_thread_id' => $thread->id,
            'user_id' => $recipient->id,
        ]);
    }
}
