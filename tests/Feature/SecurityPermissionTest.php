<?php

namespace Tests\Feature;

use App\Models\LayoutBlock;
use App\Models\Page;
use App\Models\User;
use App\Models\Widget;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SecurityPermissionTest extends TestCase
{
    use DatabaseTransactions;

    public function test_guests_are_redirected_away_from_protected_pages(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/admin')->assertRedirect('/login');
        $this->get('/admin/applications')->assertRedirect('/login');
    }

    public function test_authenticated_users_can_view_their_dashboard(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);
        $user->assignRole('user');

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
    }

    public function test_standard_users_cannot_access_any_admin_routes(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);
        $user->assignRole('user');

        $this->actingAs($user)->get('/admin')->assertStatus(403);
        $this->actingAs($user)->get('/admin/applications')->assertStatus(403);
        $this->actingAs($user)->get('/admin/settings')->assertStatus(403);
        $this->actingAs($user)->get('/admin/users')->assertStatus(403);
    }

    public function test_staff_with_granular_permissions_can_only_access_permitted_admin_hubs(): void
    {
        // Create a custom staff role that ONLY has access to the Admin Hub and Applications Queue
        $reviewerRole = Role::firstOrCreate([
            'name' => 'app_reviewer',
            'guard_name' => 'web',
        ]);
        $reviewerRole->syncPermissions(['access-admin', 'applications_view']);

        $staffUser = User::factory()->create([
            'role' => 'senior user',
        ]);
        $staffUser->assignRole($reviewerRole);

        // Allowed routes (has 'access-admin' and 'applications_view')
        $this->actingAs($staffUser)->get('/admin')->assertStatus(200);
        $this->actingAs($staffUser)->get('/admin/applications')->assertStatus(200);

        // Restricted routes (lacks 'manage_settings' and 'manage_users')
        $this->actingAs($staffUser)->get('/admin/settings')->assertStatus(403);
        $this->actingAs($staffUser)->get('/admin/users')->assertStatus(403);
    }

    public function test_page_widgets_respect_required_permission_column(): void
    {
        // 1. Grab the Landing page ('/') and its first Layout Block
        $landingPage = Page::where('slug', '/')->firstOrFail();
        $block = LayoutBlock::where('layout_id', $landingPage->layout_id)->firstOrFail();

        // 2. Ensure the Rich Text Document widget exists
        $docWidget = Widget::firstOrCreate(
            ['widget_key' => 'document'],
            [
                'name' => 'Rich Text Document',
                'default_options' => json_encode(['title' => '', 'content' => '<p>Start typing here...</p>']),
            ]
        );

        // 3. Attach a restricted widget to the Landing Page requiring 'applications_view'
        $secretText = 'CLASSIFIED_COUNCIL_EYES_ONLY_WIDGET_TEXT';

        DB::table('page_widget')->insert([
            'page_id' => $landingPage->id,
            'widget_id' => $docWidget->id,
            'layout_block_id' => $block->id,
            'sort_order' => 99,
            'instance_options' => json_encode(['title' => 'Council Box', 'content' => "<p>{$secretText}</p>"]),
            'required_permission' => 'applications_view',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 4. A standard user visiting '/' should NOT see the restricted widget text
        $standardUser = User::factory()->create(['role' => 'user']);
        $standardUser->assignRole('user');

        $this->actingAs($standardUser)
            ->get('/')
            ->assertStatus(200)
            ->assertDontSee($secretText);

        // 5. A user WITH 'applications_view' visiting '/' SHOULD see the restricted widget text
        $councilUser = User::factory()->create(['role' => 'senior user']);
        $councilUser->givePermissionTo('applications_view');

        $this->actingAs($councilUser)
            ->get('/')
            ->assertStatus(200)
            ->assertSee($secretText);
    }
}
