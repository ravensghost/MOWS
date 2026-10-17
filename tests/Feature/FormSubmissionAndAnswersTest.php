<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\FormField;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class FormSubmissionAndAnswersTest extends TestCase
{
    use DatabaseTransactions;

    public function test_application_submission_saves_associated_answers(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        // Ensure target form and fields exist
        FormField::firstOrCreate(
            ['id' => 25],
            ['name' => 'Application', 'type' => 'widget_form', 'form_type' => 'form', 'is_required' => 0]
        );

        $nameField = FormField::firstOrCreate(
            ['id' => 1],
            ['name' => 'Character Name', 'type' => 'text', 'form_type' => 'application', 'form_linked' => '25', 'is_required' => 1]
        );

        $speciesField = FormField::firstOrCreate(
            ['id' => 34],
            ['name' => 'Species / Heritage', 'type' => 'text', 'form_type' => 'application', 'form_linked' => '25', 'is_required' => 1]
        );

        // Submit application via the PublicFormController endpoint
        $response = $this->actingAs($user)->post('/form/submit/25', [
            'field_1'  => 'Ensign Commander Test',
            'field_34' => 'Vulcan',
        ]);

        $response->assertSessionHasNoErrors();

        // Verify the main application record was created
        $this->assertDatabaseHas('applications', [
            'user_id'        => $user->id,
            'character_name' => 'Ensign Commander Test',
            'status'         => 'Pending',
        ]);

        $application = Application::where('user_id', $user->id)->latest('id')->first();

        // Verify individual form field answers were stored in application_answers
        $this->assertDatabaseHas('application_answers', [
            'application_id' => $application->id,
            'form_field_id'  => $nameField->id,
            'answer'         => 'Ensign Commander Test',
        ]);

        $this->assertDatabaseHas('application_answers', [
            'application_id' => $application->id,
            'form_field_id'  => $speciesField->id,
            'answer'         => 'Vulcan',
        ]);
    }
}
