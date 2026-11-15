<?php

namespace Tests\Feature;

use App\Models\CandidatePetition;
use App\Models\Metadata;
use App\Models\Persona;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ApplicationAndRosterTest extends TestCase
{
    use DatabaseTransactions;

    public function test_user_can_submit_a_candidate_petition(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        Metadata::updateOrCreate(['id' => 1], [
            'metadata_title' => 'Persona Name',
            'metadata_mode' => 'text',
            'meta_mode' => 'application',
            'linked_metadata' => '25',
            'metadata_sort_order' => 1,
        ]);

        $response = $this->actingAs($user)->postJson('/form/submit/application', [
            'field_1' => 'Ensign Test Persona',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.proposed_persona_name', 'Ensign Test Persona');

        $this->assertDatabaseHas('candidate_petitions', [
            'user_id' => $user->id,
            'proposed_persona_name' => 'Ensign Test Persona',
            'petition_status' => 'Pending',
        ]);
    }

    public function test_admin_can_approve_a_candidate_petition_and_create_a_persona(): void
    {
        $admin = User::factory()->create(['role' => 'webmaster']);
        $applicant = User::factory()->create(['role' => 'user']);
        $petition = CandidatePetition::create([
            'user_id' => $applicant->id,
            'proposed_persona_name' => 'Lieutenant Test',
            'petition_status' => 'Pending',
            'lodged_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->postJson("/admin/candidate-petitions/{$petition->id}/approve");

        $response->assertOk();

        $this->assertDatabaseHas('candidate_petitions', [
            'id' => $petition->id,
            'petition_status' => 'Approved',
        ]);

        $this->assertDatabaseHas('personae', [
            'user_id' => $applicant->id,
            'persona_name' => 'Lieutenant Test',
            'persona_status' => 'active',
        ]);
    }
}
