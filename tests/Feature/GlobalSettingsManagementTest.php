<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class GlobalSettingsManagementTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_can_update_global_settings(): void
    {
        $admin = User::factory()->create(['role' => 'webmaster']);

        $response = $this->actingAs($admin)->postJson('/admin/configurations/update', [
            'settings' => [
                'site_name' => 'Starbase Alpha - MOWS Engine',
            ],
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('configurations', [
            'configuration_token' => 'site_name',
            'configuration_payload' => 'Starbase Alpha - MOWS Engine',
        ]);

    }
}
