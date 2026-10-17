<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\NavigationOption;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class NavigationManagementTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_can_create_navigation_link(): void
    {
        $admin = User::factory()->create(['role' => 'webmaster']);

        // Ensure valid options exist for location and visibility validation
        NavigationOption::firstOrCreate(
            ['option_type' => 'location', 'value' => 'primary'],
            ['label' => 'Primary']
        );
        NavigationOption::firstOrCreate(
            ['option_type' => 'visibility', 'value' => 'public'],
            ['label' => 'Public']
        );

        $response = $this->actingAs($admin)->postJson('/admin/navigation', [
            'lexicon_key' => 'nav_fleet_roster',
            'url'         => '/roster',
            'location'    => 'primary',
            'visibility'  => 'public',
            'target'      => '_self',
            'order'       => 10,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('navigation_links', [
            'lexicon_key' => 'nav_fleet_roster',
            'url'         => '/roster',
            'location'    => 'primary',
        ]);
    }
}
