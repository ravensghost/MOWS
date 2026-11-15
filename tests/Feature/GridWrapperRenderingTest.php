<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class GridWrapperRenderingTest extends TestCase
{
    public function test_named_slot_renders_in_its_assigned_grid_area(): void
    {
        $gridModule = (object) [
            'id' => 27,
            'grid_module_title' => 'ProfileInformation',
            'column_span' => 6,
            'row_span' => 2,
        ];
        $module = (object) [
            'module_token' => 'profile',
            'module_default_settings' => [],
            'pivot' => (object) [
                'is_visible' => true,
                'grid_module_id' => 27,
                'module_sort_order' => 0,
                'access_clearance' => null,
                'book_config' => [],
            ],
        ];
        $book = (object) [
            'grid' => (object) ['gridModules' => collect([$gridModule])],
            'modules' => collect([$module]),
        ];
        $html = Blade::render(
            '<x-grid-wrapper :book="$book"><x-slot:profileinformation><strong>Profile block</strong></x-slot:profileinformation></x-grid-wrapper>',
            ['book' => $book],
        );

        $this->assertStringContainsString('col-span-6 row-span-2', $html);
        $this->assertStringContainsString('<strong>Profile block</strong>', $html);
    }

    public function test_named_slot_is_not_rendered_without_a_matching_grid_module_title(): void
    {
        $gridModule = (object) [
            'id' => 27,
            'grid_module_title' => 'Content',
            'column_span' => 6,
            'row_span' => 2,
        ];
        $book = (object) [
            'grid' => (object) ['gridModules' => collect([$gridModule])],
            'modules' => collect(),
        ];
        $html = Blade::render(
            '<x-grid-wrapper :book="$book"><x-slot:profile><strong>Unassigned block</strong></x-slot:profile></x-grid-wrapper>',
            ['book' => $book],
        );

        $this->assertStringNotContainsString('Unassigned block', $html);
    }

    public function test_named_slot_renders_without_a_book_module_assignment(): void
    {
        $gridModule = (object) [
            'id' => 27,
            'grid_module_title' => 'Content',
            'column_span' => 12,
            'row_span' => 1,
        ];
        $book = (object) [
            'grid' => (object) ['gridModules' => collect([$gridModule])],
            'modules' => collect(),
        ];
        $html = Blade::render(
            '<x-grid-wrapper :book="$book"><x-slot:content><strong>Hard-coded page content</strong></x-slot:content></x-grid-wrapper>',
            ['book' => $book],
        );

        $this->assertStringContainsString('<strong>Hard-coded page content</strong>', $html);
    }
}
