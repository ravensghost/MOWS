<?php

namespace Tests\Feature\Integrations;

use App\Models\User;
use App\Services\AILoreService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AiIntegrationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_can_generate_a_division_description(): void
    {
        $admin = User::factory()->create(['role' => 'webmaster']);

        $aiLoreService = \Mockery::mock(AILoreService::class);
        $aiLoreService->shouldReceive('generateSectionDescription')
            ->once()
            ->with('Tactical Operations', 'Focus on defense and security.')
            ->andReturn('The division safeguards operational readiness.');
        $this->app->instance(AILoreService::class, $aiLoreService);

        $response = $this->actingAs($admin)->postJson('/admin/divisions/generate-description', [
            'division_title' => 'Tactical Operations',
            'prompt_hint' => 'Focus on defense and security.',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.division_description', 'The division safeguards operational readiness.');
    }
}
