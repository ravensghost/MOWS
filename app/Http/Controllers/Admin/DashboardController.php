<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Character;
use App\Models\Page;
use App\Models\Position;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        abort_unless(
            auth()->user()?->can('access-admin'),
            403,
            lexicon('msg_AdminDashboard_Unauthorised', 'Unauthorised access to the Command Centre.')
        );

        $systemPage = Page::where('slug', 'admin')
            ->where('is_published', true)
            ->with([
                'layout.blocks',
                'widgets' => function ($query) {
                    $query->orderBy('page_widget.sort_order');
                }
            ])
            ->firstOrFail();

        $positions = Position::where('is_open', true)
            ->withCount(['characters' => function ($query) {
                $query->where('status', 'active');
            }])->get();

        $trueOpenSlots = $positions->sum(function ($position) {
            return max(0, $position->capacity - $position->characters_count);
        });

        // Aggregated monthly writing metrics scoreboard query
        $currentMonthMetrics = DB::table('chapter_user_contributions')
            ->join('chapters', 'chapters.id', '=', 'chapter_user_contributions.chapter_id')
            ->join('users', 'users.id', '=', 'chapter_user_contributions.user_id')
            ->leftJoin('characters', 'characters.id', '=', 'chapters.active_character_id')
            ->whereBetween('chapters.created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->select(
                'users.name as user_name',
                'characters.name as character_name',
                DB::raw('COUNT(DISTINCT chapters.id) as post_count'),
                DB::raw('SUM(chapter_user_contributions.word_count) as total_words')
            )
            ->groupBy('users.id', 'users.name', 'characters.id', 'characters.name')
            ->orderByDesc('total_words')
            ->get();

        return view('admin.dashboard', [
            'systemPage'          => $systemPage,
            'pendingApps'         => Application::where('status', 'Pending')->count(),
            'activeUsers'         => User::count(),
            'totalCharacters'     => Character::where('status', 'active')->count(),
            'openPositions'       => $trueOpenSlots,
            'currentMonthMetrics' => $currentMonthMetrics,
        ]);
    }
}
