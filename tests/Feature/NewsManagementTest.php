<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class NewsManagementTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_can_publish_a_station_bulletin(): void
    {
        $admin = User::factory()->create(['role' => 'webmaster']);

        $response = $this->actingAs($admin)->postJson('/admin/bulletins', [
            'bulletin_title' => 'Fleet Directive Alpha',
            'bulletin_text' => 'All personnel are instructed to report to designated stations.',
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('bulletins', [
            'bulletin_title' => 'Fleet Directive Alpha',
            'bulletin_text' => 'All personnel are instructed to report to designated stations.',
            'user_id' => $admin->id,
        ]);
    }
}
