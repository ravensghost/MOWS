<?php

namespace Tests\Feature\Integrations;

use App\Models\User;
use App\Models\Section;
use App\Models\Chapter;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AiIntegrationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_ai_integration_generates_section_and_chapter_content(): void
    {
        $admin = User::factory()->create(['role' => 'webmaster']);

        // 1. Test AI Section Description Generation hook
        $sectionResponse = $this->actingAs($admin)->postJson('/admin/sections/generate-description', [
            'name'        => 'Tactical Operations',
            'prompt_hint' => 'Focus on defense and security.',
        ]);

        $sectionResponse->assertStatus(200);
        $sectionResponse->assertJsonStructure(['description']);

        // 2. Test AI Chapter Writing / NPC Generation hook
        $chapterResponse = $this->actingAs($admin)->postJson('/admin/ai/chapter-generate', [
            'prompt' => 'Write an introductory scene for an NPC security chief.',
        ]);

        $chapterResponse->assertStatus(200);
        $chapterResponse->assertJsonStructure(['content']);
    }
}
