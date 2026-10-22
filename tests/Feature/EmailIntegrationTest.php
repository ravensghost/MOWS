<?php

namespace Tests\Feature;

use App\Mail\MowsNotificationMail;
use App\Models\Book;
use App\Models\Character;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailIntegrationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_email_notifications_are_sent_for_core_events_when_enabled(): void
    {
        Mail::fake();

        Setting::updateOrCreate(['key' => 'notifications_email_enabled'], ['value' => '1']);

        $author = User::factory()->create();
        $coWriter = User::factory()->create();

        $book = Book::first() ?? Book::create(['title' => 'Test Book', 'ic_day' => 1, 'ic_time' => '0800']);

        $authorChar = Character::create([
            'user_id'        => $author->id,
            'character_type' => 'PC',
            'name'           => 'Captain Test',
            'status'         => 'active',
        ]);

        $taggedChar = Character::create([
            'user_id'        => $coWriter->id,
            'character_type' => 'PC',
            'name'           => 'Commander Tagged',
            'status'         => 'active',
        ]);

        // 1. Test chapter publication + character tag triggers email dispatch
        $chapterResponse = $this->actingAs($author)->postJson('/chapters', [
            'book_id'             => $book->id,
            'active_character_id' => $authorChar->id,
            'character_ids'       => [(string) $taggedChar->id],
            'chapter_type'        => 'mission',
            'ic_day'              => '1',
            'ic_time'             => '0800',
            'location'            => 'Main Bridge',
            'rating_l'            => 1,
            'rating_s'            => 1,
            'rating_v'            => 1,
            'content'             => '<p>Mission briefing details.</p>',
            'status'              => 'published',
        ]);

        $chapterResponse->assertStatus(200);

        Mail::assertSent(MowsNotificationMail::class, function ($mail) use ($coWriter) {
            return $mail->hasTo($coWriter->email);
        });

        // 2. Test Private Message triggers recipient email alert
        $pmResponse = $this->actingAs($author)->postJson('/messenger/new', [
            'type'       => 'OOC',
            'recipients' => [$coWriter->id],
            'subject'    => 'Subspace Transmission',
            'body'       => 'Incoming transmission.',
        ]);

        $pmResponse->assertStatus(200);

        Mail::assertSent(MowsNotificationMail::class, function ($mail) use ($coWriter) {
            return $mail->hasTo($coWriter->email) && str_contains($mail->customSubject, 'New Private Message');
        });
    }
}
