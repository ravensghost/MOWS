<?php

namespace Tests\Feature;

use App\Models\Division;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SectionsAndPositionsTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_can_create_a_division_and_assignment(): void
    {
        $admin = User::factory()->create(['role' => 'webmaster']);

        $response = $this->actingAs($admin)->postJson('/admin/divisions', [
            'division_title' => 'Engineering',
            'division_description' => 'Maintains station technical systems and warp drives.',
            'division_base_colour' => '#10b981',
            'division_sort_order' => 2,
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('divisions', [
            'division_title' => 'Engineering',
            'division_base_colour' => '#10b981',
        ]);

        $division = Division::where('division_title', 'Engineering')->firstOrFail();

        $assignmentResponse = $this->actingAs($admin)->postJson("/admin/assignments/divisions/{$division->id}", [
            'assignment_title' => 'Chief Engineer',
            'maximum_occupants' => 1,
            'is_recruitment_open' => true,
        ]);

        $assignmentResponse->assertOk();

        $this->assertDatabaseHas('assignments', [
            'division_id' => $division->id,
            'assignment_title' => 'Chief Engineer',
            'maximum_occupants' => 1,
            'is_recruitment_open' => 1,
        ]);
    }
}
