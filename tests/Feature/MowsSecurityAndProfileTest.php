<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions; // <--- Switch this
use Tests\TestCase;

class MowsSecurityAndProfileTest extends TestCase
{
    use DatabaseTransactions;

    public function test_unauthenticated_guests_cannot_access_dashboard_or_admin()
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/admin')->assertRedirect('/login');
    }

    public function test_user_can_update_profile_including_social_handles()
    {
        $user = User::factory()->create([
            'role' => 'user'
        ]);

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Commander Test',
                'email' => 'commander@mows.engine',
                'twitter_handle' => '@testwriter',
                'facebook_handle' => 'testprofile',
            ]);

        $response->assertSessionHasNoErrors();

        $user->refresh();

        $this->assertSame('Commander Test', $user->name);
        $this->assertSame('commander@mows.engine', $user->email);
        $this->assertSame('@testwriter', $user->twitter_handle);
        $this->assertSame('testprofile', $user->facebook_handle);
    }

    public function test_standard_users_cannot_access_admin_command_centre()
    {
        $user = User::factory()->create([
            'role' => 'user'
        ]);

        $response = $this->actingAs($user)->get('/admin');

        $response->assertStatus(403);
    }

    public function test_webmaster_or_authorized_roles_can_access_admin_command_centre()
    {
        $admin = User::factory()->create([
            'role' => 'webmaster',
        ]);
        $admin->assignRole('webmaster');

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertStatus(200);
    }
}
