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

        $response = $this->actingAs($admin)->postJson('/admin/time-systems', [
            'name'            => 'Stardate Standard',
            'prefix'          => 'SD',
            'engine_type'     => 'fractional',
            'base_earth_year' => 2387,
            'multiplier'      => 1000,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('time_systems', [
            'name'   => 'Stardate Standard',
            'prefix' => 'SD',
        ]);
    }
}
