<?php

namespace Tests\Feature;

use App\Models\TableOfContentParameter;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class NavigationManagementTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_can_create_a_table_of_contents_entry(): void
    {
        $admin = User::factory()->create(['role' => 'webmaster']);

        TableOfContentParameter::create([
            'parameter_mode' => 'display_location',
            'parameter_value' => 'primary',
            'parameter_label' => 'Primary',
        ]);
        TableOfContentParameter::create([
            'parameter_mode' => 'access_clearance',
            'parameter_value' => 'public',
            'parameter_label' => 'Public',
        ]);

        $response = $this->actingAs($admin)->postJson('/admin/table-of-contents', [
            'translation_code_key' => 'nav_fleet_roster',
            'destination_url' => '/roster',
            'display_location' => 'primary',
            'access_clearance' => 'public',
            'window_target' => '_self',
            'entry_sort_order' => 10,
            'is_visible' => true,
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('table_of_contents', [
            'translation_code_key' => 'nav_fleet_roster',
            'destination_url' => '/roster',
            'display_location' => 'primary',
            'access_clearance' => 'public',
        ]);
    }
}
