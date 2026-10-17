<?php

namespace Tests\Feature;

use App\Models\Section;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SectionsAndPositionsTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_can_create_section_and_position(): void
    {
        $admin = User::factory()->create(['role' => 'webmaster']);

        // 1. Create an organizational section
        $response = $this->actingAs($admin)->postJson('/admin/sections', [
            'name'        => 'Engineering',
            'description' => 'Maintains station technical systems and warp drives.',
            'color'       => '#10b981',
            'order'       => 2,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sections', [
            'name'  => 'Engineering',
            'color' => '#10b981',
        ]);

        $section = Section::where('name', 'Engineering')->first();

        // 2. Create a position under this section
        $posResponse = $this->actingAs($admin)->postJson("/admin/sections/{$section->id}/positions", [
            'name'     => 'Chief Engineer',
            'capacity' => 1,
            'is_open'  => 1,
        ]);

        $posResponse->assertStatus(200);

        $this->assertDatabaseHas('positions', [
            'section_id' => $section->id,
            'name'       => 'Chief Engineer',
            'capacity'   => 1,
        ]);
    }
}
