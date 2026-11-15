<?php

namespace Tests\Feature;

use App\Models\EncyclopediaCodex;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PageManagementTest extends TestCase
{
    use DatabaseTransactions;

    public function test_authorised_editor_can_create_an_encyclopedia_article(): void
    {
        $admin = User::factory()->create(['role' => 'webmaster']);
        $admin->givePermissionTo(Permission::firstOrCreate([
            'name' => 'manage_wiki',
            'guard_name' => 'web',
        ]));

        $response = $this->actingAs($admin)->postJson('/admin', [
            'article_title' => 'Fleet Operations Manual',
            'article_category' => 'Operations',
            'article_text' => '<p>Standard operating procedures for all personnel.</p>',
            'is_visible' => true,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.article_url', 'fleet-operations-manual');

        $this->assertDatabaseHas('encyclopedia_codex', [
            'article_url' => 'fleet-operations-manual',
            'article_title' => 'Fleet Operations Manual',
            'article_category' => 'Operations',
        ]);
        $this->assertSame(
            '<p>Standard operating procedures for all personnel.</p>',
            EncyclopediaCodex::where('article_url', 'fleet-operations-manual')->value('article_text')
        );
    }
}
