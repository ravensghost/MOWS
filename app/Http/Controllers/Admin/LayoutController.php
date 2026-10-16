<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Layout;

class LayoutController extends Controller
{
    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string']);
        $layout = Layout::create($request->only(['name', 'description']));
        $this->syncBlocks($layout, $request->input('blocks', []));

        return response()->json(['success' => true]);
    }

    public function update(Request $request, Layout $layout)
    {
        $request->validate(['name' => 'required|string']);
        $layout->update($request->only(['name', 'description']));
        $this->syncBlocks($layout, $request->input('blocks', []));

        return response()->json(['success' => true]);
    }

    public function destroy(Layout $layout)
    {
        $layout->delete();
        return response()->json(['success' => true]);
    }

    private function syncBlocks(Layout $layout, $blocks)
    {
        $keptIds = [];
        foreach ($blocks as $index => $blockData) {
            if (!empty($blockData['name'])) {
                $block = $layout->blocks()->updateOrCreate(
                    ['id' => $blockData['id'] ?? null],
                    [
                        'name' => $blockData['name'],
                        'col_span' => $blockData['col_span'] ?? 12,
                        'row_span' => $blockData['row_span'] ?? 1,
                        'sort_order' => $index,
                    ]
                );
                $keptIds[] = $block->id;
            }
        }
        $layout->blocks()->whereNotIn('id', $keptIds)->delete();
    }
}
