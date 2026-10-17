<?php

namespace Tests\Feature;

use App\Models\RankSet;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class RankManagementTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_can_create_rank_set(): void
    {
        $admin = User::factory()->create(['role' => 'webmaster']);

        $response = $this->actingAs($admin)->postJson('/admin/rank-sets', [
            'name' => 'Starfleet Command Ranks',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('rank_sets', [
            'name' => 'Starfleet Command Ranks',
        ]);
    }
}
