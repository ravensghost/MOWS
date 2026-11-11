<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Shelf;
use App\Models\Volume;
use App\Models\Book;
use App\Models\PublicationState;

class PublicationController extends Controller
{
    public function storyManager()
    {
        $systemPage = Book::where('books_uri', 'admin/story')
            ->where('is_published', true)
            ->with(['layout.blocks', 'widgets' => function ($query) {
                $query->orderBy('book_modules.module_sort_order');
            }])
            ->firstOrFail();

        $shelves = Shelf::with([
            'volumes' => function ($q) {
                $q->select('id', 'shelf_id', 'publication_state_id', 'volume_title', 'volume_synopsis', 'ic_day', 'ic_time', 'volume_sort_order')
                    ->orderBy('volume_sort_order')
                    ->orderBy('volume_title');
            },
            'volumes.folios' => function ($q) {
                $q->select('id', 'volume_id', 'folio_mode', 'scene_location', 'folio_status', 'ic_day', 'ic_time', 'content_tier_l', 'content_tier_s', 'content_tier_v', 'folio_sort_order', 'created_at')
                    ->orderBy('folio_sort_order')
                    ->orderBy('created_at', 'desc');
            },
            'volumes.publicationState:id,state_title,book_url'
        ])
            ->select('id', 'shelf_title', 'shelf_sort_order')
            ->orderBy('shelf_sort_order')
            ->orderBy('shelf_title')
            ->get();

        $looseVolumes = Volume::whereNull('shelf_id')
            ->with([
                'folios' => function ($q) {
                    $q->select('id', 'volume_id', 'folio_mode', 'scene_location', 'folio_status', 'ic_day', 'ic_time', 'content_tier_l', 'content_tier_s', 'content_tier_v', 'folio_sort_order', 'created_at')
                        ->orderBy('folio_sort_order')
                        ->orderBy('created_at', 'desc');
                },
                'publicationState:id,state_title,book_url'
            ])
            ->select('id', 'publication_state_id', 'volume_title', 'volume_synopsis', 'ic_day', 'ic_time', 'volume_sort_order')
            ->orderBy('volume_sort_order')
            ->orderBy('volume_title')
            ->get();

        $statuses = PublicationState::select('id', 'state_title', 'book_url')
            ->orderBy('state_sort_order')
            ->get();

        return view('admin.publication', compact('systemPage', 'shelves', 'looseVolumes', 'statuses'));
    }

    public function storeStatus(Request $request)
    {
        $validated = $request->validate([
            'state_title' => 'required|string|max:255',
        ]);

        $maxOrder = PublicationState::max('state_sort_order') ?? 0;

        $status = PublicationState::create([
            'state_title'      => $validated['state_title'],
            'book_url'         => Str::slug($validated['state_title']),
            'state_sort_order' => $maxOrder + 1,
        ]);

        return response()->json([
            'status'  => 'success',
            'data'    => $status,
            'message' => lexicon('msg_Story_StatusCreated', 'Publication state created successfully.')
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'state_title' => 'required|string|max:255',
        ]);

        $status = PublicationState::findOrFail($id);
        $status->update([
            'state_title' => $validated['state_title'],
            'book_url'    => Str::slug($validated['state_title']),
        ]);

        return response()->json([
            'status'  => 'success',
            'data'    => $status,
            'message' => lexicon('msg_Story_StatusUpdated', 'Publication state updated successfully.')
        ]);
    }

    public function destroyStatus($id)
    {
        PublicationState::findOrFail($id)->delete();

        return response()->json([
            'status'  => 'success',
            'data'    => null,
            'message' => lexicon('msg_Story_StatusDeleted', 'Publication state deleted successfully.')
        ]);
    }
}
