<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_can_update_user_role(): void
    {
        $admin = User::factory()->create(['role' => 'webmaster']);
        $targetUser = User::factory()->create(['role' => 'user']);

        Role::firstOrCreate([
            'name' => 'senior user',
            'guard_name' => 'web',
        ]);

        $response = $this->actingAs($admin)->putJson("/admin/users/{$targetUser->id}/role", [
            'role' => 'senior user',
        ]);

        $response->assertOk();

        $this->assertTrue($targetUser->fresh()->hasRole('senior user'));
    }
}
