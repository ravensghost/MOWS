<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Blade;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SecurityPermissionTest extends TestCase
{
    use DatabaseTransactions;

    public function test_guests_are_redirected_away_from_protected_pages(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/admin/command-centre')->assertRedirect('/login');
        $this->get('/admin/candidate-petitions')->assertRedirect('/login');
    }

    public function test_authenticated_users_can_view_their_dashboard(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);
        $user->assignRole('user');

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
    }

    public function test_standard_users_cannot_access_any_admin_routes(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);
        $user->assignRole('user');

        $this->actingAs($user)->get('/admin/command-centre')->assertForbidden();
        $this->actingAs($user)->get('/admin/candidate-petitions')->assertForbidden();
        $this->actingAs($user)->get('/admin/users')->assertForbidden();
        $this->actingAs($user)
            ->postJson('/admin/configurations/update', ['settings' => ['site_name' => 'Blocked']])
            ->assertForbidden();
    }

    public function test_petition_reviewers_can_access_the_petition_queue_without_user_or_settings_access(): void
    {
        // Grant queue access without user-management or settings permissions.
        $reviewerRole = Role::firstOrCreate([
            'name' => 'app_reviewer',
            'guard_name' => 'web',
        ]);
        $reviewerRole->syncPermissions(['applications_vote']);

        $staffUser = User::factory()->create([
            'role' => 'user',
        ]);
        $staffUser->syncRoles([$reviewerRole]);

        $this->actingAs($staffUser)->get('/admin/candidate-petitions')->assertOk();

        $this->actingAs($staffUser)->get('/admin/users')->assertForbidden();
        $this->actingAs($staffUser)
            ->postJson('/admin/configurations/update', ['settings' => ['site_name' => 'Blocked']])
            ->assertForbidden();
    }

    public function test_book_modules_require_their_configured_access_clearance(): void
    {
        $secretText = 'CLASSIFIED_COUNCIL_EYES_ONLY_WIDGET_TEXT';
        $gridModule = (object) [
            'id' => 1,
            'grid_module_title' => 'Content',
            'column_span' => 12,
            'row_span' => 1,
        ];
        $module = (object) [
            'module_token' => 'document',
            'module_default_settings' => [],
            'pivot' => (object) [
                'is_visible' => true,
                'grid_module_id' => 1,
                'module_sort_order' => 0,
                'access_clearance' => 'applications_view',
                'book_config' => [],
                'instance_options' => ['title' => 'Council Box', 'content' => "<p>{$secretText}</p>"],
            ],
        ];
        $book = (object) [
            'grid' => (object) ['gridModules' => collect([$gridModule])],
            'modules' => collect([$module]),
        ];

        $standardUser = User::factory()->create(['role' => 'user']);
        $this->actingAs($standardUser);
        $hiddenContent = Blade::render('<x-grid-wrapper :book="$book" />', ['book' => $book]);
        $this->assertStringNotContainsString($secretText, $hiddenContent);

        $councilUser = User::factory()->create(['role' => 'user']);
        $councilUser->givePermissionTo('applications_view');
        $this->actingAs($councilUser);
        $visibleContent = Blade::render('<x-grid-wrapper :book="$book" />', ['book' => $book]);
        $this->assertStringContainsString($secretText, $visibleContent);
    }
}
