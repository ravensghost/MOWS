<?php

namespace Tests\Feature;

use App\Models\Persona;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class LeaveOfAbsenceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_user_can_set_short_term_loa_under_thirty_days(): void
    {
        $admin = User::factory()->create(['role' => 'webmaster']);
        $user = User::factory()->create(['role' => 'user']);
        $persona = Persona::create([
            'user_id' => $user->id,
            'persona_name' => 'LOA Test Persona',
            'persona_status' => 'active',
        ]);
        $returnDate = now()->addDays(10)->toDateString();

        $response = $this->actingAs($admin)->putJson("/admin/users/{$user->id}/loa", [
            'is_on_loa'       => true,
            'loa_return_date' => $returnDate,
        ]);

        $response->assertOk();

        $this->assertTrue($user->fresh()->is_on_loa);
        $this->assertSame($returnDate, $user->fresh()->loa_return_date->toDateString());
        $this->assertSame('loa', $persona->fresh()->persona_status);
    }
}
