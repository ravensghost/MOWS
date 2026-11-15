<?php

namespace Tests\Feature;

use App\Models\CandidatePetition;
use App\Models\Metadata;
use App\Models\PetitionEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class FormSubmissionAndAnswersTest extends TestCase
{
    use DatabaseTransactions;

    public function test_candidate_petition_submission_saves_metadata_answers(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $nameField = Metadata::updateOrCreate(['id' => 1], [
            'metadata_title' => 'Persona Name',
            'metadata_mode' => 'text',
            'meta_mode' => 'application',
            'linked_metadata' => '25',
            'metadata_sort_order' => 1,
        ]);
        $heritageField = Metadata::create([
            'metadata_title' => 'Heritage',
            'metadata_mode' => 'text',
            'meta_mode' => 'application',
            'linked_metadata' => '25',
            'metadata_sort_order' => 2,
        ]);

        $response = $this->actingAs($user)->postJson('/form/submit/application', [
            'field_1' => 'Ensign Commander Test',
            'field_'.$heritageField->id => 'Vulcan',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.proposed_persona_name', 'Ensign Commander Test');

        $petition = CandidatePetition::where('user_id', $user->id)->latest('id')->firstOrFail();
        $this->assertDatabaseHas('candidate_petitions', [
            'id' => $petition->id,
            'petition_status' => 'Pending',
        ]);

        $this->assertSame(
            'Ensign Commander Test',
            PetitionEntry::where('candidate_petition_id', $petition->id)
                ->where('metadata_id', $nameField->id)
                ->value('petition_answer')
        );
        $this->assertSame(
            'Vulcan',
            PetitionEntry::where('candidate_petition_id', $petition->id)
                ->where('metadata_id', $heritageField->id)
                ->value('petition_answer')
        );
    }
}
