<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions; // <--- Switch this
use Tests\TestCase;

class MowsSecurityAndProfileTest extends TestCase
{
    use DatabaseTransactions;

    public function test_unauthenticated_guests_are_redirected_from_dashboard_and_command_centre(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/admin/command-centre')->assertRedirect('/login');
    }

    public function test_user_can_update_profile_including_social_handles(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
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

    public function test_standard_users_cannot_access_admin_command_centre(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $response = $this->actingAs($user)->get('/admin/command-centre');

        $response->assertForbidden();
    }

    public function test_webmaster_can_access_admin_command_centre(): void
    {
        $admin = User::factory()->create(['role' => 'webmaster']);

        $response = $this->actingAs($admin)->get('/admin/command-centre');

        $response->assertOk();
    }
}
