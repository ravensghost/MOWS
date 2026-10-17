<?php

namespace Tests\Feature;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class RoleAndPermissionManagementTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_can_create_security_role(): void
    {
        $admin = User::factory()->create(['role' => 'webmaster']);

        $response = $this->actingAs($admin)->postJson(route('admin.roles.store'), [
            'name' => 'Fleet Overseer',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('roles', [
            'name'       => 'Fleet Overseer',
            'guard_name' => 'web',
        ]);
    }
}
