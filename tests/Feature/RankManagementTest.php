<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class RankManagementTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_can_create_an_insignia_catalogue(): void
    {
        $admin = User::factory()->create(['role' => 'webmaster']);

        $response = $this->actingAs($admin)->postJson('/admin/insignia/catalogues', [
            'catalogue_title' => 'Starfleet Command Insignia',
            'is_visible' => true,
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('insignia_catalogues', [
            'catalogue_title' => 'Starfleet Command Insignia',
            'is_visible' => 1,
        ]);
    }
}
