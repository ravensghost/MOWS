<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Character;
use App\Models\Page;
use App\Models\Position;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
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

        // Configurable inactivity threshold in days (defaults to 30 days)
        $inactivityThresholdDays = max(1, (int) (Setting::where('key', 'inactivity_threshold_days')->value('value') ?? 30));

        // Eager-load users with their characters, attributes, and latest chapter post timestamp
        $allUsers = User::with(['characters', 'attributes', 'roles'])
            ->withMax('posts as last_post_at', 'created_at')
            ->orderBy('name')
            ->get();

        $today = now()->startOfDay();
        $loaPlayers = collect();
        $playerActivity = collect();
        $flaggedInactiveCount = 0;

        foreach ($allUsers as $user) {
            $isOnLoa = $user->is_on_loa || $user->characters->whereIn('status', ['loa', 'eloa'])->isNotEmpty();
            $returnDate = $user->loa_return_date ? $user->loa_return_date->copy()->startOfDay() : null;

            $daysRemaining = $returnDate ? (int) $today->diffInDays($returnDate, false) : null;
            $isExpired = $daysRemaining !== null && $daysRemaining < 0;

            // Determine LOA vs ELOA badge classification
            $hasEloaChar = $user->characters->where('status', 'eloa')->isNotEmpty();
            $leaveType = ($hasEloaChar || ($daysRemaining !== null && $daysRemaining > 30)) ? 'ELOA' : 'LOA';

            if ($isOnLoa) {
                $loaPlayers->push((object) [
                    'id'              => $user->id,
                    'name'            => $user->name,
                    'email'           => $user->email,
                    'leave_type'      => $leaveType,
                    'return_date'     => $returnDate ? $returnDate->format('Y-m-d') : null,
                    'return_display'  => $returnDate ? $returnDate->format('d M Y') : lexicon('label_common_NotSpecified', 'Not Specified'),
                    'days_remaining'  => $daysRemaining,
                    'is_expired'      => $isExpired,
                    'character_names' => $user->characters->pluck('name')->implode(', ') ?: lexicon('label_common_None', 'None'),
                ]);
            }

            // Activity & Inactivity Audit Calculation
            $lastLogin = $user->last_login_at ?? $user->updated_at ?? $user->created_at;
            $lastLoginCarbon = $lastLogin ? Carbon::parse($lastLogin) : null;
            $daysSinceLogin = $lastLoginCarbon ? (int) $lastLoginCarbon->copy()->startOfDay()->diffInDays($today) : 999;

            $lastPostCarbon = $user->last_post_at ? Carbon::parse($user->last_post_at) : null;

            // Flag as inactive if threshold exceeded AND player is not covered by an active (non-expired) LOA
            $isInactive = ($daysSinceLogin >= $inactivityThresholdDays) && (! $isOnLoa || $isExpired);
            if ($isInactive) {
                $flaggedInactiveCount++;
            }

            $playerActivity->push((object) [
                'id'               => $user->id,
                'name'             => $user->name,
                'role'             => $user->role,
                'character_names'  => $user->characters->pluck('name')->implode(', ') ?: 'Unassigned',
                'is_on_loa'        => $isOnLoa,
                'leave_type'       => $isOnLoa ? $leaveType : 'ACTIVE',
                'return_date'      => $returnDate ? $returnDate->format('Y-m-d') : '',
                'is_expired_loa'   => $isExpired,
                'last_login_human' => $lastLoginCarbon ? $lastLoginCarbon->diffForHumans() : 'Never',
                'last_login_date'  => $lastLoginCarbon ? $lastLoginCarbon->format('d M Y') : '—',
                'days_since_login' => $daysSinceLogin,
                'last_post_human'  => $lastPostCarbon ? $lastPostCarbon->diffForHumans() : 'No posts yet',
                'is_inactive'      => $isInactive,
            ]);
        }

        // Sort LOA players so expired returns appear first, then soonest return date
        $loaPlayers = $loaPlayers->sortBy(function ($p) {
            return $p->days_remaining ?? 9999;
        })->values();

        // Sort activity list so flagged inactive players appear at the top
        $playerActivity = $playerActivity->sortByDesc(function ($p) {
            return ($p->is_inactive ? 100000 : 0) + $p->days_since_login;
        })->values();

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
            'systemPage'              => $systemPage,
            'pendingApps'             => Application::where('status', 'Pending')->count(),
            'activeUsers'             => $allUsers->count(),
            'totalCharacters'         => Character::where('status', 'active')->count(),
            'openPositions'           => $trueOpenSlots,
            'loaPlayers'              => $loaPlayers,
            'loaCount'                => $loaPlayers->count(),
            'playerActivity'          => $playerActivity,
            'flaggedInactiveCount'    => $flaggedInactiveCount,
            'inactivityThresholdDays' => $inactivityThresholdDays,
            'currentMonthMetrics'     => $currentMonthMetrics,
        ]);
    }
}
