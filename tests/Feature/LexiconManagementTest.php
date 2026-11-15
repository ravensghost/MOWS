<?php

namespace Tests\Feature;

use App\Models\LexiconCodex;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class LexiconManagementTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_can_update_lexicon_term(): void
    {
        $admin = User::factory()->create(['role' => 'webmaster']);

        $response = $this->actingAs($admin)->postJson('/admin/lexicon-codex', [
            'semantic_token' => 'msg_CustomSystemNotice',
            'translation_payloads' => [
                'gb' => 'All systems operating at nominal efficiency.',
            ],
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('lexicon_codex', [
            'semantic_token' => 'msg_CustomSystemNotice',
            'default_lexicon_string' => 'All systems operating at nominal efficiency.',
        ]);

        $entry = LexiconCodex::where('semantic_token', 'msg_CustomSystemNotice')->firstOrFail();
        $this->assertSame(
            'All systems operating at nominal efficiency.',
            $entry->translation_payloads['gb']
        );
    }
}
