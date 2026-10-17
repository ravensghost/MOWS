<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Bookcase;
use App\Models\BookStatus;
use App\Models\Chapter;
use App\Models\Character;
use App\Models\Position;
use App\Models\Ranks;
use App\Models\RankSet;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class StoryAndSanitisationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_chapter_content_is_sanitised_for_xss(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $bookcase = Bookcase::firstOrCreate(['name' => 'Test Bookcase']);
        $status = BookStatus::firstOrCreate(['slug' => 'active'], ['name' => 'Active', 'sort_order' => 1]);
        $book = Book::firstOrCreate(['title' => 'Test Book'], ['bookcase_id' => $bookcase->id, 'status_id' => $status->id]);

        $rankSet = RankSet::firstOrCreate(['name' => 'writers']);
        $section = Section::firstOrCreate(['name' => 'Writers'], ['color' => '#dc2626', 'order' => 1]);
        $position = Position::firstOrCreate(['name' => 'Writer', 'section_id' => $section->id]);
        $rank = Ranks::firstOrCreate(['name' => 'Head Writer', 'rank_set_id' => $rankSet->id], ['short_name' => 'HW']);

        $character = Character::create([
            'user_id'        => $user->id,
            'position_id'    => $position->id,
            'rank_id'        => $rank->id,
            'character_type' => 'PC',
            'name'           => 'Writer Character',
            'status'         => 'active',
        ]);

        $maliciousContent = "<script>alert('xss_attack');</script><p>Safe story paragraph text.</p>";

        // Use postJson and provide all fields required by WriteController validation
        $response = $this->actingAs($user)->postJson('/chapters', [
            'book_id'             => $book->id,
            'active_character_id' => $character->id,
            'chapter_type'        => 'mission',
            'ic_day'              => '1', // Must be string per WriteController validation
            'ic_time'             => '0800',
            'location'            => 'Bridge Hub', // Required by validation
            'rating_l'            => 1,
            'rating_s'            => 1,
            'rating_v'            => 1,
            'content'             => $maliciousContent,
            'status'              => 'draft',
        ]);

        $response->assertStatus(200);

        // Assert chapter was created successfully
        $this->assertDatabaseHas('chapters', [
            'book_id' => $book->id,
            'status'  => 'draft',
        ]);

        $chapter = Chapter::latest('id')->first();

        // Verify that the <script> tag was stripped out, but the safe <p> tag remained
        $this->assertStringNotContainsString('<script>', $chapter->content);
        $this->assertStringContainsString('<p>Safe story paragraph text.</p>', $chapter->content);
    }
}
