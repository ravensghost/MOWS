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

        $response = $this->actingAs($admin)->postJson('/admin/forms', [
            'name' => 'Transfer Request Form',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('form_fields', [
            'name'      => 'Transfer Request Form',
            'form_type' => 'form',
        ]);
    }
}
