<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Grid;
use Illuminate\Http\Request;

class GridController extends Controller
{
    /**
     * Store a newly created grid and synchronise its grid modules.
     */
    public function store(Request $request)
    {
        $request->validate([
            'grid_title'    => 'required|string|max:255',
            'grid_synopsis' => 'nullable|string|max:255',
        ]);

        $grid = Grid::create($request->only(['grid_title', 'grid_synopsis']));
        $this->syncGridModules($grid, $request->input('grid_modules', []));

        return response()->json(['success' => true]);
    }

    /**
     * Update the specified grid and synchronise its grid modules.
     */
    public function update(Request $request, Grid $grid)
    {
        $request->validate([
            'grid_title'    => 'required|string|max:255',
            'grid_synopsis' => 'nullable|string|max:255',
        ]);

        $grid->update($request->only(['grid_title', 'grid_synopsis']));
        $this->syncGridModules($grid, $request->input('grid_modules', []));

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
    private function syncGridModules(Grid $grid, array $gridModules): void
    {
        $keptIds = [];

        foreach ($gridModules as $index => $moduleData) {
            if (!empty($moduleData['grid_module_title'])) {
                $gridModule = $grid->gridModules()->updateOrCreate(
                    ['id' => $moduleData['id'] ?? null],
                    [
                        'grid_module_title'      => $moduleData['grid_module_title'],
                        'column_span'            => $moduleData['column_span'] ?? 12,
                        'row_span'               => $moduleData['row_span'] ?? 1,
                        'grid_module_sort_order' => $index,
                    ]
                );
                $keptIds[] = $gridModule->id;
            }
        }

        $grid->gridModules()->whereNotIn('id', $keptIds)->delete();
    }
}
