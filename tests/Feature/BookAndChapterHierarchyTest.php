<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Bookcase;
use App\Models\BookStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class BookAndChapterHierarchyTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_can_create_book_within_bookcase(): void
    {
        $admin = User::factory()->create(['role' => 'webmaster']);

        $bookcase = Bookcase::create([
            'name' => 'Season One: Frontier',
        ]);

        $status = BookStatus::firstOrCreate(
            ['slug' => 'active'],
            ['name' => 'Active', 'sort_order' => 1]
        );

        $response = $this->actingAs($admin)->postJson('/admin/books', [
            'bookcase_id' => $bookcase->id,
            'title'       => 'Operation Vanguard',
            'status_id'   => $status->id,
            'sort_order'  => 1,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('books', [
            'bookcase_id' => $bookcase->id,
            'title'       => 'Operation Vanguard',
        ]);
    }
}
