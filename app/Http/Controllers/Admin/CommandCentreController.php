<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Book;
use App\Models\CandidatePetition;
use App\Models\Configuration;
use App\Models\Persona;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CommandCentreController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        abort_unless(
            auth()->user()?->can('access-admin'),
            403,
            lexicon('msg_CommandCentre_Unauthorised', 'Unauthorised access to the Command Centre.')
        );

        $systemBook = Book::forUrl('/admin/command-centre')->publishedWithLayout()->firstOrFail();

        $assignments = Assignment::where('is_recruitment_open', true)
            ->withCount(['personae' => function ($query) {
                $query->where('persona_status', 'active');
            }])
            ->get();

        $trueOpenSlots = $assignments->sum(function ($assignment) {
            return max(0, (int) $assignment->maximum_occupants - (int) $assignment->personae_count);
        });

        // Configurable inactivity threshold in days (defaults to 30 days)
        $inactivityThresholdDays = max(
            1,
            (int) (Configuration::where('configuration_token', 'inactivity_threshold_days')->value('configuration_payload') ?? 30)
        );

        // Eager-load users with their personae, attributes, and latest folio timestamp
        $allUsers = User::with(['personae', 'attributes', 'roles'])
            ->withMax('folios as last_post_at', 'created_at')
            ->orderBy('name')
            ->get();

        $today = now()->startOfDay();
        $loaPlayers = collect();
        $playerActivity = collect();
        $flaggedInactiveCount = 0;

        foreach ($allUsers as $user) {
            $isOnLoa = (bool) $user->is_on_loa || $user->personae->whereIn('persona_status', ['loa', 'eloa'])->isNotEmpty();
            $returnDate = $user->loa_return_date ? Carbon::parse($user->loa_return_date)->copy()->startOfDay() : null;

            $daysRemaining = $returnDate ? (int) $today->diffInDays($returnDate, false) : null;
            $isExpired = $daysRemaining !== null && $daysRemaining < 0;

            // Determine LOA vs ELOA badge classification
            $hasEloaPersona = $user->personae->where('persona_status', 'eloa')->isNotEmpty();
            $leaveType = ($hasEloaPersona || ($daysRemaining !== null && $daysRemaining > 30)) ? 'ELOA' : 'LOA';

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
                    'persona_names'   => $user->personae->pluck('persona_name')->implode(', ') ?: lexicon('label_common_None', 'None'),
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
                'persona_names'    => $user->personae->pluck('persona_name')->implode(', ') ?: 'Unassigned',
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
        $loaPlayers = $loaPlayers->sortBy(function ($player) {
            return $player->days_remaining ?? 9999;
        })->values();

        // Sort activity list so flagged inactive players appear at the top
        $playerActivity = $playerActivity->sortByDesc(function ($player) {
            return ($player->is_inactive ? 100000 : 0) + $player->days_since_login;
        })->values();

        // Aggregated monthly writing metrics scoreboard query (author_ledgers & folios)
        $currentMonthMetrics = DB::table('author_ledgers')
            ->join('folios', 'folios.id', '=', 'author_ledgers.folio_id')
            ->join('users', 'users.id', '=', 'author_ledgers.user_id')
            ->leftJoin('personae', 'personae.id', '=', 'folios.active_persona_id')
            ->whereBetween('folios.created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->select(
                'users.name as user_name',
                'personae.persona_name as persona_name',
                DB::raw('COUNT(DISTINCT folios.id) as post_count'),
                DB::raw('SUM(author_ledgers.contribution_word_count) as total_words')
            )
            ->groupBy('users.id', 'users.name', 'personae.id', 'personae.persona_name')
            ->orderByDesc('total_words')
            ->get();

        $payload = [
            'systemBook'              => $systemBook,
            'pendingApps'             => CandidatePetition::where('petition_status', 'Pending')->count(),
            'activeUsers'             => $allUsers->count(),
            'totalCharacters'         => Persona::where('persona_status', 'active')->count(),
            'openPositions'           => $trueOpenSlots,
            'loaPlayers'              => $loaPlayers,
            'loaCount'                => $loaPlayers->count(),
            'playerActivity'          => $playerActivity,
            'flaggedInactiveCount'    => $flaggedInactiveCount,
            'inactivityThresholdDays' => $inactivityThresholdDays,
            'currentMonthMetrics'     => $currentMonthMetrics,
        ];

        if ($request->wantsJson()) {
            return response()->json([
                'status'   => 'success',
                'message'  => lexicon('msg_CommandCentre_Loaded', 'Command Centre data retrieved successfully.'),
                'data'     => $payload,
                'redirect' => null,
            ], 200);
        }

        return view('admin.dashboard', $payload);
    }
}