<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Grid;
use Illuminate\Http\Request;

class GridController extends Controller
{
    /**
     * Store a newly created grid and synchronise its modules.
     */
    public function store(Request $request)
    {
        $request->validate([
            'grid_title'    => 'required|string|max:255',
            'grid_synopsis' => 'nullable|string|max:255',
        ]);

        $grid = Grid::create($request->only(['grid_title', 'grid_synopsis']));
        $this->syncBlocks($grid, $request->input('blocks', []));

        return response()->json(['success' => true]);
    }

    /**
     * Update the specified grid and synchronise its modules.
     */
    public function update(Request $request, Grid $grid)
    {
        $request->validate([
            'grid_title'    => 'required|string|max:255',
            'grid_synopsis' => 'nullable|string|max:255',
        ]);

        $grid->update($request->only(['grid_title', 'grid_synopsis']));
        $this->syncBlocks($grid, $request->input('blocks', []));

        return response()->json(['success' => true]);
    }

    /**
     * Remove the specified grid from storage.
     */
    public function destroy(Grid $grid)
    {
        $grid->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Synchronise the grid modules belonging to the grid.
     */
    private function syncBlocks(Grid $grid, array $blocks): void
    {
        $keptIds = [];

        foreach ($blocks as $index => $blockData) {
            if (!empty($blockData['grid_module_title'])) {
                $block = $grid->blocks()->updateOrCreate(
                    ['id' => $blockData['id'] ?? null],
                    [
                        'grid_module_title'      => $blockData['grid_module_title'],
                        'column_span'            => $blockData['column_span'] ?? 12,
                        'row_span'               => $blockData['row_span'] ?? 1,
                        'grid_module_sort_order' => $index,
                    ]
                );
                $keptIds[] = $block->id;
            }
        }

        $grid->blocks()->whereNotIn('id', $keptIds)->delete();
    }
}
