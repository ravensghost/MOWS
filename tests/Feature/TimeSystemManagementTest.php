<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class TimeSystemManagementTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_can_create_time_system(): void
    {
        $admin = User::factory()->create(['role' => 'webmaster']);

        $response = $this->actingAs($admin)->postJson('/admin/chronological-engines', [
            'engine_title' => 'Stardate Standard',
            'date_prefix' => 'SD',
            'calculation_algorithm' => 'fractional',
            'base_earth_year' => 2387,
            'temporal_multiplier' => 1000,
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('chronological_engines', [
            'engine_title' => 'Stardate Standard',
            'date_prefix' => 'SD',
            'calculation_algorithm' => 'fractional',
            'temporal_multiplier' => 1000,
        ]);
    }
}
