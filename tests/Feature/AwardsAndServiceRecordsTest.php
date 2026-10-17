<?php

namespace Tests\Feature;

use App\Models\Award;
use App\Models\Character;
use App\Models\Position;
use App\Models\Ranks;
use App\Models\RankSet;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AwardsAndServiceRecordsTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_can_issue_award_to_character(): void
    {
        $admin = User::factory()->create(['role' => 'webmaster']);
        $characterOwner = User::factory()->create(['role' => 'user']); // Separate owner

        $rankSet = RankSet::firstOrCreate(['name' => 'writers']);
        $section = Section::firstOrCreate(['name' => 'Writers'], ['color' => '#dc2626', 'order' => 1]);
        $position = Position::firstOrCreate(['name' => 'Writer', 'section_id' => $section->id]);
        $rank = Ranks::firstOrCreate(['name' => 'Head Writer', 'rank_set_id' => $rankSet->id], ['short_name' => 'HW']);

        $character = Character::create([
            'user_id'        => $characterOwner->id, // Owned by another user
            'position_id'    => $position->id,
            'rank_id'        => $rank->id,
            'character_type' => 'PC',
            'name'           => 'Award Recipient Character',
            'status'         => 'active',
        ]);

        $award = Award::firstOrCreate(
            ['name' => 'Medal of Valor'],
            ['master_group' => 'IC', 'base_color' => '#1a1a1a']
        );

        $response = $this->actingAs($admin)->postJson('/admin/service-records/award', [
            'character_id'   => $character->id,
            'award_id'       => $award->id,
            'reason'         => 'For outstanding bravery in the line of duty.',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('service_records', [
            'character_id' => $character->id,
            'award_id'     => $award->id,
            'event_type'   => 'award',
            'description'  => 'For outstanding bravery in the line of duty.',
        ]);
    }
}
