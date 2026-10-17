<?php

namespace Tests\Feature;

use App\Models\Character;
use App\Models\Position;
use App\Models\RankSet;
use App\Models\Ranks;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class LeaveOfAbsenceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_user_can_set_short_term_loa_under_thirty_days(): void
    {
        // Create an admin/staff user with 'manage_users' permission
        $admin = User::factory()->create(['role' => 'webmaster']);
        $admin->givePermissionTo('manage_users');

        $user = User::factory()->create(['role' => 'user']);

        // Set up a rank set, section, position, and rank for the test character
        $rankSet = RankSet::firstOrCreate(['name' => 'writers']);
        $section = Section::firstOrCreate(['name' => 'Writers'], ['color' => '#dc2626', 'order' => 1]);
        $position = Position::firstOrCreate(['name' => 'Writer', 'section_id' => $section->id], ['capacity' => 10, 'is_open' => 1]);
        $rank = Ranks::firstOrCreate(['name' => 'Head Writer', 'rank_set_id' => $rankSet->id], ['short_name' => 'HW', 'sort_order' => 1]);

        // Create an active character owned by this regular user
        $character = Character::create([
            'user_id'        => $user->id,
            'position_id'    => $position->id,
            'rank_id'        => $rank->id,
            'character_type' => 'PC',
            'name'           => 'LOA Test Character',
            'status'         => 'active',
        ]);

        // Return date 10 days from now (Short LOA)
        $returnDate = now()->addDays(10)->toDateString();

        // Act as the admin/staff member updating the user's LOA
        $response = $this->actingAs($admin)->put("/admin/users/{$user->id}/loa", [
            'is_on_loa'       => true,
            'loa_return_date' => $returnDate,
        ]);

        $response->assertStatus(200);

        // Verify user LOA attributes updated
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_on_loa' => 1,
            'loa_return_date' => $returnDate,
        ]);

        // Verify character status cascaded to 'loa'
        $character->refresh();
        $this->assertEquals('loa', $character->status);
    }
}
