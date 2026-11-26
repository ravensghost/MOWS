<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\PublicationState;
use App\Models\Shelf;
use App\Models\Volume;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PublicationController extends Controller
{
    private function authorisePublicationAccess(): void
    {
        $user = auth()->user();

        abort_unless(
            $user && ($user->can('manage_stories') || $user->can('access-admin')),
            403,
            lexicon('msg_Story_Unauthorised', 'Unauthorised access to Publication Management.')
        );
    }

    public function storyManager(): View
    {
        $this->authorisePublicationAccess();

        $systemBook = Book::whereIn('book_url', ['admin/volumes', 'admin/story'])
            ->where('is_published', true)
            ->with(['grid.gridModules', 'modules'])
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
            'volumes.publicationState:id,state_title,book_url',
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
                'publicationState:id,state_title,book_url',
            ])
            ->select('id', 'publication_state_id', 'volume_title', 'volume_synopsis', 'ic_day', 'ic_time', 'volume_sort_order')
            ->orderBy('volume_sort_order')
            ->orderBy('volume_title')
            ->get();

        $statuses = PublicationState::select('id', 'state_title', 'book_url')
            ->orderBy('state_sort_order')
            ->get();

        return view('admin.publication', compact('systemBook', 'shelves', 'looseVolumes', 'statuses'));
    }

    public function storeStatus(Request $request): JsonResponse
    {
        $this->authorisePublicationAccess();

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
            'status'   => 'success',
            'message'  => lexicon('msg_Story_StatusCreated', 'Publication state created successfully.'),
            'data'     => $status,
            'redirect' => null,
        ], 200);
    }

    public function updateStatus(Request $request, int|string $id): JsonResponse
    {
        $this->authorisePublicationAccess();

        $validated = $request->validate([
            'state_title' => 'required|string|max:255',
        ]);

        $status = PublicationState::findOrFail($id);
        $status->update([
            'state_title' => $validated['state_title'],
            'book_url'    => Str::slug($validated['state_title']),
        ]);

        return response()->json([
            'status'   => 'success',
            'message'  => lexicon('msg_Story_StatusUpdated', 'Publication state updated successfully.'),
            'data'     => $status,
            'redirect' => null,
        ], 200);
    }

    public function destroyStatus(int|string $id): JsonResponse
    {
        $this->authorisePublicationAccess();

        PublicationState::findOrFail($id)->delete();

        return response()->json([
            'status'   => 'success',
            'message'  => lexicon('msg_Story_StatusDeleted', 'Publication state deleted successfully.'),
            'data'     => null,
            'redirect' => null,
        ], 200);
    }
}