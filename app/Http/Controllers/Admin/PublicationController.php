<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Shelf;
use App\Models\Volume;
use App\Models\Book;
use App\Models\Folio;
use App\Models\FolioRevision;
use App\Models\PublicationState;
use App\Models\AuthorLedger;

class PublicationController extends Controller
{
    public function storyManager()
    {
        $systemPage = Book::where('slug', 'admin/story')
            ->where('is_published', true)
            ->with(['grid.gridModules', 'widgets' => function ($query) {
                $query->orderBy('page_widget.sort_order');
            }])
            ->firstOrFail();

        $shelves = Shelf::with([
            'volumes' => function ($q) {
                $q->select('id', 'shelf_id', 'publication_state_id', 'volume_title', 'volume_synopsis', 'ic_day', 'ic_time', 'volume_sort_order')
                    ->orderBy('volume_sort_order')
                    ->orderBy('volume_title');
            },
            'volumes.folios' => function ($q) {
                $q->select('id', 'volume_id', 'folio_type', 'ic_day', 'ic_time', 'content_tier_l', 'content_tier_s', 'content_tier_v', 'folio_sort_order', 'created_at')
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
                    $q->select('id', 'volume_id', 'folio_type', 'ic_day', 'ic_time', 'content_tier_l', 'content_tier_s', 'content_tier_v', 'folio_sort_order', 'created_at')
                        ->orderBy('folio_sort_order')
                        ->orderBy('created_at', 'desc');
                },
                'publicationState:id,state_title,book_url'
            ])
            ->select('id', 'publication_state_id', 'volume_title', 'volume_synopsis', 'ic_day', 'ic_time', 'volume_sort_order')
            ->orderBy('volume_sort_order')
            ->orderBy('volume_title')
            ->get();

        $statuses = PublicationState::select('id', 'state_title', 'book_url')->orderBy('state_sort_order')->get();

        return view('admin.story', compact('systemPage', 'shelves', 'looseVolumes', 'statuses'));
    }

    public function storeStatus(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $maxOrder = PublicationState::max('state_sort_order') ?? 0;

        $status = PublicationState::create([
            'state_title'      => $validated['name'],
            'book_url'         => \Illuminate\Support\Str::slug($validated['name']),
            'state_sort_order' => $maxOrder + 1,
        ]);

        return response()->json([
            'status'  => 'success',
            'data'    => $status,
            'message' => lexicon('msg_Story_StatusCreated', 'Book status created successfully.')
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $status = PublicationState::findOrFail($id);
        $status->update([
            'state_title' => $validated['name'],
            'book_url'    => \Illuminate\Support\Str::slug($validated['name']),
        ]);

        return response()->json([
            'status'  => 'success',
            'data'    => $status,
            'message' => lexicon('msg_Story_StatusUpdated', 'Book status updated successfully.')
        ]);
    }

    public function destroyStatus($id)
    {
        $status = PublicationState::findOrFail($id);
        $status->delete();

        return response()->json([
            'status'  => 'success',
            'data'    => null,
            'message' => lexicon('msg_Story_StatusDeleted', 'Book status deleted successfully.')
        ]);
    }
}
