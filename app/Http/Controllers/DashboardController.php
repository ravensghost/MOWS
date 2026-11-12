<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\CorrespondenceEntry;
use App\Models\CorrespondenceParticipant;
use App\Models\Folio;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $userId = auth()->id();

        $draftChapters = Folio::where('author_user_id', $userId)
            ->where('folio_status', 'draft')
            ->orderByDesc('updated_at')
            ->get();

        $threadIds = CorrespondenceParticipant::where('user_id', $userId)->pluck('correspondence_thread_id');

        $unreadMessages = CorrespondenceEntry::whereIn('correspondence_thread_id', $threadIds)
            ->where('user_id', '!=', $userId)
            ->with('thread')
            ->orderByDesc('created_at')
            ->take(10)
            ->get();

        $systemPage = Book::where('book_url', 'dashboard')
            ->where('is_published', true)
            ->with([
                'grid.gridModules',
                'modules' => function ($q) {
                    $q->orderBy('book_modules.module_sort_order');
                }
            ])
            ->first();

        $user = auth()->user();

        return view('dashboard', compact('draftChapters', 'unreadMessages', 'systemPage', 'user'));
    }

    public function updateLoa(Request $request)
    {
        $validated = $request->validate([
            'is_on_loa'       => 'required|boolean',
            'loa_return_date' => 'nullable|required_if:is_on_loa,1,true|date|after_or_equal:today',
            'reason'          => 'nullable|string|max:255',
        ]);

        $user = auth()->user();
        $isOnLoa = (bool) $validated['is_on_loa'];

        $statusLabel = $user->syncLoaStatus(
            $isOnLoa,
            $validated['loa_return_date'] ?? null,
            $validated['reason'] ?? null,
            $user->id
        );

        $message = $isOnLoa
            ? lexicon('msg_Dashboard_LoaActive', "Leave of Absence active ({$statusLabel}). Your characters and chronological registers have been updated.")
            : lexicon('msg_Dashboard_LoaReturned', 'Welcome back! Your account and characters have been returned to Active Duty.');

        if ($request->wantsJson()) {
            return response()->json([
                'status'  => 'success',
                'message' => $message
            ]);
        }

        return back()->with('status', $message);
    }
}
