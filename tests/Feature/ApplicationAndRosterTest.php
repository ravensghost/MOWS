<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Character;
use App\Models\FormField;
use App\Models\Position;
use App\Models\Ranks;
use App\Models\RankSet;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ApplicationAndRosterTest extends TestCase
{
    use DatabaseTransactions;

    public function test_user_can_submit_character_application(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        // Submit using the field_{id} naming convention expected by PublicFormController
        $response = $this->actingAs($user)->post('/form/submit/25', [
            'field_1'  => 'Ensign Test Character', // Matches field ID 1 (Character Name)
            'field_30' => 'Male',                  // Gender
            'field_34' => 'Human',                 // Species / Heritage
            'field_35' => '25',                    // Age
            'field_39' => 'Standard description.', // Physical Description
            'field_40' => 'Brave and eager.',      // Personality & Traits
        ]);

        $response->assertSessionHasNoErrors();

        // Assert application is created and pending in the database with the correct name
        $this->assertDatabaseHas('applications', [
            'user_id'        => $user->id,
            'character_name' => 'Ensign Test Character',
            'status'         => 'Pending',
        ]);
    }

    public function test_admin_can_approve_application_and_integrate_character(): void
    {
        $admin = User::factory()->create(['role' => 'webmaster']);
        $applicant = User::factory()->create(['role' => 'user']);

        $rankSet = RankSet::firstOrCreate(['name' => 'writers']);
        $section = Section::firstOrCreate(['name' => 'Writers'], ['color' => '#dc2626', 'order' => 1]);

        $position = Position::firstOrCreate(
            ['name' => 'Lead Writer', 'section_id' => $section->id],
            ['capacity' => 1, 'is_open' => 1]
        );

        $rank = Ranks::firstOrCreate(
            ['name' => 'Head Writer', 'rank_set_id' => $rankSet->id],
            ['short_name' => 'HW', 'sort_order' => 1]
        );

        $application = Application::create([
            'user_id' => $applicant->id,
            'character_name' => 'Lieutenant Test',
            'status' => 'Pending',
        ]);

        $response = $this->actingAs($admin)->postJson("/admin/applications/{$application->id}/approve", [
            'position_id' => $position->id,
            'rank_id' => $rank->id,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('applications', [
            'id' => $application->id,
            'status' => 'Approved',
        ]);

        $this->assertDatabaseHas('characters', [
            'user_id' => $applicant->id,
            'name' => 'Lieutenant Test',
            'position_id' => $position->id,
            'rank_id' => $rank->id,
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('service_records', [
            'user_id' => $applicant->id,
            'event_type' => 'transfer',
        ]);
    }
}
