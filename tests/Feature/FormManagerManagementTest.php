<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class FormManagerManagementTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_can_create_custom_form(): void
    {
        $admin = User::factory()->create(['role' => 'webmaster']);

        $response = $this->actingAs($admin)->postJson('/admin/metadata', [
            'metadata_title' => 'Transfer Request Form',
            'is_visible' => true,
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('metadata', [
            'metadata_title' => 'Transfer Request Form',
            'metadata_mode' => 'custom_form',
            'meta_mode' => 'form',
            'is_visible' => 1,
        ]);
    }
}
