<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class WidgetManagementTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_can_sync_widgets(): void
    {
        $admin = User::factory()->create(['role' => 'webmaster']);

        $response = $this->actingAs($admin)->postJson('/admin/widgets/sync');

        $response->assertStatus(200);

        $response->assertJson([
            'status' => 'success',
        ]);
    }
}
