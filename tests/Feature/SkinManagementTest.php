<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Skin;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SkinManagementTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_can_switch_active_skin(): void
    {
        $admin = User::factory()->create(['role' => 'webmaster']);

        $skin = Skin::create([
            'name'              => 'LCARS Blue',
            'folder_name'       => 'lcars_blue',
            'version'           => '1.0',
            'is_global_default' => 0,
        ]);

        $response = $this->actingAs($admin)->postJson("/admin/themes/{$skin->id}/activate");

        $response->assertStatus(200);

        // Verify that the skin is now set as the global default in the database
        $this->assertDatabaseHas('skins', [
            'id'                => $skin->id,
            'folder_name'       => 'lcars_blue',
            'is_global_default' => 1,
        ]);
    }
}
