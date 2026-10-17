<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_can_update_user_role(): void
    {
        $admin = User::factory()->create(['role' => 'webmaster']);
        $targetUser = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($admin)->putJson("/admin/users/{$targetUser->id}/role", [
            'role' => 'senior user',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'id'   => $targetUser->id,
            'role' => 'senior user',
        ]);
    }
}
