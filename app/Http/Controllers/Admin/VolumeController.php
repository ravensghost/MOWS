<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Volume;

class VolumeController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'volume_title'         => 'required|string|max:255',
            'volume_synopsis'      => 'nullable|string',
            'shelf_id'             => 'nullable|exists:shelves,id',
            'publication_state_id' => 'required|exists:publication_states,id',
            'ic_day'               => 'nullable|integer',
            'ic_time'              => 'nullable|string|max:255',
        ]);

        $volume = Volume::create($validated);

        return response()->json([
            'status'  => 'success',
            'data'    => $volume,
            'message' => lexicon('msg_Story_VolumeCreated', 'Volume created successfully.')
        ]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'volume_title'         => 'required|string|max:255',
            'volume_synopsis'      => 'nullable|string',
            'shelf_id'             => 'nullable|exists:shelves,id',
            'publication_state_id' => 'required|exists:publication_states,id',
            'ic_day'               => 'nullable|integer',
            'ic_time'              => 'nullable|string|max:255',
        ]);

        $volume = Volume::findOrFail($id);

        // Assuming completed state ID is 3
        $wasCompleted = (int) $volume->publication_state_id === 3;
        $volume->update($validated);
        $isNowCompleted = (int) $volume->publication_state_id === 3;

        return response()->json([
            'status'         => 'success',
            'data'           => $volume,
            'trigger_review' => (!$wasCompleted && $isNowCompleted),
            'message'        => lexicon('msg_Story_VolumeUpdated', 'Volume updated successfully.')
        ]);
    }

    public function destroy($id)
    {
        $volume = Volume::findOrFail($id);
        $volume->delete();

        return response()->json([
            'status'  => 'success',
            'data'    => null,
            'message' => lexicon('msg_Story_VolumeDeleted', 'Volume deleted successfully.')
        ]);
    }

    public function reorder(Request $request)
    {
        foreach ($request->input('items', []) as $item) {
            Volume::where('id', $item['id'])->update(['volume_sort_order' => $item['sort_order']]);
        }
        return response()->json([
            'status'  => 'success',
            'data'    => null,
            'message' => lexicon('msg_Story_VolumesReordered', 'Volumes reordered successfully.')
        ]);
    }
}
