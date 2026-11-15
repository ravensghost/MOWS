<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class BookAndChapterHierarchyTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_can_create_a_book_with_a_public_url(): void
    {
        $admin = User::factory()->create(['role' => 'webmaster']);

        $response = $this->actingAs($admin)->postJson('/admin/books', [
            'book_title' => 'Operation Vanguard',
            'book_url' => 'operations/vanguard',
            'is_published' => true,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.book_url', 'operations/vanguard');

        $this->assertDatabaseHas('books', [
            'book_title' => 'Operation Vanguard',
            'book_url' => 'operations/vanguard',
        ]);
    }
}
