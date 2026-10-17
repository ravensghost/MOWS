<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class LexiconManagementTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_can_update_lexicon_term(): void
    {
        $admin = User::factory()->create(['role' => 'webmaster']);

        // Submit via single term creation/edit format expected by LexiconTermController@update
        $response = $this->actingAs($admin)->postJson('/admin/lexicon', [
            'code_key'     => 'msg_CustomSystemNotice',
            'translations' => [
                'gb' => 'All systems operating at nominal efficiency.',
            ],
        ]);

        $response->assertStatus(200);

        // Verify against the correct 'terms' table used by the Term model
        $this->assertDatabaseHas('terms', [
            'code_key' => 'msg_CustomSystemNotice',
        ]);
    }
}
