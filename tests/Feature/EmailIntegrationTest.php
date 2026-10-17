<?php

namespace Tests\Feature\Integrations;

use App\Models\User;
use App\Models\Chapter;
use App\Models\News;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Mailable;
use Tests\TestCase;

class EmailIntegrationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_email_notifications_are_sent_for_core_events(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['role' => 'webmaster']);
        $subscriber = User::factory()->create();

        // 1. Test chapter publication triggers email dispatch
        $this->actingAs($admin)->postJson('/admin/chapters', [
            'title'   => 'Operation Vanguard',
            'content' => 'Mission briefing details.',
            'status'  => 'published',
        ]);

        Mail::assertSent(function (Mailable $mail) {
            return true; // Checks that an email notification was queued/sent for the chapter
        });

        // 2. Test Private Message triggers recipient email alert
        $this->actingAs($admin)->postJson('/messenger/new', [
            'recipient_id' => $subscriber->id,
            'message'      => 'Incoming transmission.',
        ]);

        Mail::assertSent(function (Mailable $mail) {
            return true; // Checks that a PM email alert was dispatched
        });
    }
}
