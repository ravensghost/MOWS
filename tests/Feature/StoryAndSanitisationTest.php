<?php

namespace Tests\Feature;

use App\Models\Folio;
use App\Models\Persona;
use App\Models\PublicationState;
use App\Models\Shelf;
use App\Models\User;
use App\Models\Volume;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class StoryAndSanitisationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_folio_content_is_sanitised_before_it_is_saved(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $persona = Persona::create([
            'user_id' => $user->id,
            'persona_name' => 'Writer Persona',
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

        $maliciousContent = "<script>alert('xss_attack');</script><p>Safe story paragraph text.</p>";

        $response = $this->actingAs($user)->postJson('/folios', [
            'volume_id' => $volume->id,
            'active_persona_id' => $persona->id,
            'folio_mode' => 'mission',
            'ic_day' => 1,
            'ic_time' => '0800',
            'scene_location' => 'Bridge Hub',
            'content_tier_l' => 1,
            'content_tier_s' => 1,
            'content_tier_v' => 1,
            'folio_text' => $maliciousContent,
            'folio_status' => 'draft',
        ]);

        $response->assertOk();

        $folio = Folio::findOrFail($response->json('data.id'));
        $this->assertDatabaseHas('folios', [
            'id' => $folio->id,
            'volume_id' => $volume->id,
            'folio_status' => 'draft',
        ]);

        $this->assertStringNotContainsString('<script>', $folio->folio_text);
        $this->assertStringContainsString('<p>Safe story paragraph text.</p>', $folio->folio_text);
    }
}
