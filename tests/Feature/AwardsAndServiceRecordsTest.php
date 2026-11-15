<?php

namespace Tests\Feature;

use App\Models\Distinction;
use App\Models\Persona;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AwardsAndServiceRecordsTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_can_issue_a_distinction_to_a_persona(): void
    {
        $admin = User::factory()->create(['role' => 'webmaster']);
        $personaOwner = User::factory()->create(['role' => 'user']);
        $persona = Persona::create([
            'user_id' => $personaOwner->id,
            'persona_name' => 'Distinction Recipient',
            'persona_status' => 'active',
        ]);
        $distinctionResponse = $this->actingAs($admin)->postJson('/admin/distinctions', [
            'distinction_title' => 'Medal of Valor',
            'distinction_description' => 'For outstanding bravery.',
            'scope_target' => 'IC',
            'distinction_base_colour' => '#1a1a1a',
        ]);
        $distinctionResponse->assertOk();
        $distinction = Distinction::findOrFail($distinctionResponse->json('data.id'));

        $response = $this->actingAs($admin)->postJson('/admin/chronological-registers/award', [
            'persona_id' => $persona->id,
            'distinction_id' => $distinction->id,
            'chronicle_text' => 'For outstanding bravery in the line of duty.',
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('chronological_registers', [
            'persona_id' => $persona->id,
            'distinction_id' => $distinction->id,
            'register_event_mode' => 'award',
            'chronicle_text' => 'For outstanding bravery in the line of duty.',
        ]);
    }
}
