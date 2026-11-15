<?php

namespace Tests\Feature;

use App\Models\ThematicBinding;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SkinManagementTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_can_switch_active_skin(): void
    {
        $admin = User::factory()->create(['role' => 'webmaster']);

        $skin = ThematicBinding::create([
            'binding_title' => 'LCARS Blue',
            'directory_name' => 'lcars_blue',
            'package_version' => '1.0',
            'is_active' => true,
            'is_global_default' => false,
        ]);

        $response = $this->actingAs($admin)->postJson(route('admin.thematic_bindings.activate', $skin));

        $response->assertStatus(200);

        // Verify that the skin is now set as the global default in the database
        $this->assertDatabaseHas('thematic_bindings', [
            'id' => $skin->id,
            'directory_name' => 'lcars_blue',
            'is_global_default' => 1,
        ]);
    }
}
