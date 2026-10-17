<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Layout;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PageManagementTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_can_create_custom_page(): void
    {
        $admin = User::factory()->create(['role' => 'webmaster']);

        $layout = Layout::first();

        $response = $this->actingAs($admin)->postJson('/admin/pages', [
            'slug'         => 'fleet-manual',
            'title'        => 'Fleet Operations Manual',
            'content'      => '<p>Standard operating procedures for all personnel.</p>',
            'layout_id'    => $layout?->id,
            'is_published' => 1,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('pages', [
            'slug'  => 'fleet-manual',
            'title' => 'Fleet Operations Manual',
        ]);
    }
}
