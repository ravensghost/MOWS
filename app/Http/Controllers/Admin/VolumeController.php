<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Volume;
use Illuminate\Http\Request;

class VolumeController extends Controller
{
    public function store(Request $request)
    {
        $validated =$request->validate([
            'volume_title'         => 'required|string|max:255',
            'volume_synopsis'      => 'nullable|string',
            'shelf_id'             => 'nullable|exists:shelves,id',
            'publication_state_id' => 'required|exists:publication_states,id',
            'ic_day'               => 'nullable|integer',
            'ic_time'              => 'nullable|string|max:255',
        ]);

        $maxOrder = Volume::where('shelf_id',$validated['shelf_id'] ?? null)->max('volume_sort_order') ?? 0;
        $validated['volume_sort_order'] =$maxOrder + 1;

        $volume = Volume::create($validated);

        return response()->json([
            'status'   => 'success',
            'message'  => lexicon('msg_Story_VolumeCreated', 'Volume created successfully.'),
            'data'     => $volume,
            'redirect' => null,
        ], 200);
    }

    public function update(Request $request,$id)
    {
        $validated =$request->validate([
            'volume_title'         => 'required|string|max:255',
            'volume_synopsis'      => 'nullable|string',
            'shelf_id'             => 'nullable|exists:shelves,id',
            'publication_state_id' => 'required|exists:publication_states,id',
            'ic_day'               => 'nullable|integer',
            'ic_time'              => 'nullable|string|max:255',
        ]);

        $volume = Volume::findOrFail($id);

        // Assuming completed publication state ID is 3
        $wasCompleted = (int)$volume->publication_state_id === 3;
        $volume->update($validated);
        $isNowCompleted = (int)$volume->publication_state_id === 3;

        return response()->json([
            'status'         => 'success',
            'message'        => lexicon('msg_Story_VolumeUpdated', 'Volume updated successfully.'),
            'data'           => $volume,
            'trigger_review' => (!$wasCompleted &&$isNowCompleted),
            'redirect'       => null,
        ], 200);
    }

    public function destroy($id)
    {
        $volume = Volume::findOrFail($id);$volume->delete();

        return response()->json([
            'status'   => 'success',
            'message'  => lexicon('msg_Story_VolumeDeleted', 'Volume deleted successfully.'),
            'data'     => null,
            'redirect' => null,
        ], 200);
    }

    public function reorder(Request $request)
    {
        foreach ($request->input('items', []) as $index =>$item) {
            if (!isset($item['id'])) {
                continue;
            }

            $sortOrder = $item['volume_sort_order'] ?? ($index + 1);

            Volume::where('id', $item['id'])->update([
                'volume_sort_order' => (int) $sortOrder,
            ]);
        }

        return response()->json([
            'status'   => 'success',
            'message'  => lexicon('msg_Story_VolumesReordered', 'Volumes reordered successfully.'),
            'data'     => null,
            'redirect' => null,
        ], 200);
    }
}