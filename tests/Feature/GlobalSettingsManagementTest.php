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

        $response = $this->actingAs($admin)->postJson('/admin/settings/update', [
            'settings' => [
                'site_name' => 'Starbase Alpha - MOWS Engine',
            ],
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('settings', [
            'key'   => 'site_name',
            'value' => 'Starbase Alpha - MOWS Engine',
        ]);
    }
}
