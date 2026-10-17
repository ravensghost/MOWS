<?php

namespace Tests\Feature;

use App\Models\News;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class NewsManagementTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_can_publish_station_news(): void
    {
        $admin = User::factory()->create(['role' => 'webmaster']);

        $response = $this->actingAs($admin)->postJson('/admin/news', [
            'title'   => 'Fleet Directive Alpha',
            'content' => 'All personnel are instructed to report to designated stations.',
            'status'  => 'published',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('news', [
            'title'   => 'Fleet Directive Alpha',
            'content' => 'All personnel are instructed to report to designated stations.',
        ]);
    }
}
