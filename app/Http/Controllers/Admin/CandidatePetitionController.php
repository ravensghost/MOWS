<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\CandidatePetition;
use App\Models\Configuration;
use App\Models\Persona;
use App\Models\Position;
use App\Models\Insignia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CandidatePetitionController extends Controller
{
    /**
     * Display the administrative candidate petitions management queue.
     */
    public function index()
    {
        abort_unless(
            auth()->user()?->can('access-admin'),
            403,
            lexicon('msg_AdminPetitions_Unauthorised', 'Unauthorised access to candidate petitions.')
        );

        $systemPage = Book::where('slug', 'admin-applications')
            ->orWhere('slug', 'admin')
            ->where('is_published', true)
            ->with(['layout.blocks'])
            ->first();

        $petitions = CandidatePetition::with([
            'user',
            'persona',
            'answers.metadata',
            'votes.user',
        ])
            ->orderByRaw("CASE WHEN petition_status = 'Pending' THEN 0 ELSE 1 END")
            ->orderByDesc('lodged_at')
            ->get();

        $approvalReviewMode = Configuration::where('configuration_token', 'approval_review_mode')
            ->value('configuration_payload') ?? 'single_admin';

        $characterLimitEnforced = filter_var(
            Configuration::where('configuration_token', 'character_limit_enforced')->value('configuration_payload') ?? false,
            FILTER_VALIDATE_BOOLEAN
        );

        $insignias = Insignia::orderBy('sort_order')->get();
        $positions = Position::where('is_open', true)->orderBy('name')->get();

        return view('admin.petitions.index', compact(
            'systemPage',
            'petitions',
            'approvalReviewMode',
            'characterLimitEnforced',
            'insignias',
            'positions'
        ));
    }

    /**
     * Retrieve a single candidate petition payload via AJAX for modal inspection.
     */
    public function show(CandidatePetition $petition)
    {
        abort_unless(auth()->user()?->can('access-admin'), 403);

        $petition->load(['user', 'persona', 'answers.metadata', 'votes.user']);

        return response()->json([
            'status'   => 'success',
            'success'  => true,
            'petition' => $petition,
        ]);
    }

    /**
     * Record a council evaluation vote on a pending candidate petition.
     */
    public function vote(Request $request, CandidatePetition $petition)
    {
        abort_unless(auth()->user()?->can('access-admin'), 403);

        $validated = $request->validate([
            'vote_decision' => 'required|string|in:Approve,Reject,Abstain',
            'vote_comment'  => 'nullable|string|max:2000',
        ]);

        $evaluation = $petition->votes()->updateOrCreate(
            ['user_id' => auth()->id()],
            [
                'vote_decision' => $validated['vote_decision'],
                'vote_comment'  => $validated['vote_comment'] ?? null,
            ]
        );

        return response()->json([
            'status'     => 'success',
            'success'    => true,
            'message'    => lexicon('msg_AdminPetitions_VoteRecorded', 'Your council evaluation has been recorded.'),
            'evaluation' => $evaluation->load('user'),
        ]);
    }

    /**
     * Approve a candidate petition and provision or link the active Persona.
     */
    public function approve(Request $request, CandidatePetition $petition)
    {
        abort_unless(auth()->user()?->can('access-admin'), 403);

        if ($petition->petition_status === 'Approved') {
            return response()->json([
                'status'  => 'error',
                'success' => false,
                'message' => lexicon('msg_AdminPetitions_AlreadyApproved', 'This petition has already been approved.'),
            ], 422);
        }

        // Enforce character limit configuration token if enabled
        $limitEnforcedRaw = Configuration::where('configuration_token', 'character_limit_enforced')
            ->value('configuration_payload');

        $isLimitEnforced = filter_var($limitEnforcedRaw ?? false, FILTER_VALIDATE_BOOLEAN)
            || in_array((string) $limitEnforcedRaw, ['1', 'true', 'yes', 'enabled'], true);

        if ($isLimitEnforced) {
            $maxCharacters = max(1, (int) (Configuration::where('configuration_token', 'max_active_characters')
                ->value('configuration_payload') ?? 3));

            $activeCount = Persona::where('user_id', $petition->user_id)
                ->whereIn('status', ['active', 'loa', 'eloa'])
                ->count();

            if ($activeCount >= $maxCharacters) {
                return response()->json([
                    'status'  => 'error',
                    'success' => false,
                    'message' => lexicon('msg_AdminPetitions_UserLimitExceeded', 'Cannot approve: applicant has reached the maximum active persona limit.'),
                ], 422);
            }
        }

        // Enforce approval_review_mode quorum if configured for council consensus
        $reviewMode = Configuration::where('configuration_token', 'approval_review_mode')
            ->value('configuration_payload') ?? 'single_admin';

        if ($reviewMode === 'council_quorum') {
            $requiredVotes = max(1, (int) (Configuration::where('configuration_token', 'council_quorum_count')
                ->value('configuration_payload') ?? 2));

            $approvalVotes = $petition->votes()
                ->whereIn('vote_decision', ['Approve', 'approve', 'Approved'])
                ->count();

            if ($approvalVotes < $requiredVotes && !$request->boolean('force_override')) {
                return response()->json([
                    'status'  => 'error',
                    'success' => false,
                    'message' => lexicon('msg_AdminPetitions_QuorumNotMet', 'Council quorum has not yet been met for this petition.'),
                ], 422);
            }
        }

        DB::transaction(function () use ($request, $petition) {
            $persona = $petition->persona;

            if (!$persona) {
                $persona = Persona::create([
                    'user_id'     => $petition->user_id,
                    'name'        => $request->input('proposed_persona_name', $petition->proposed_persona_name),
                    'insignia_id' => $request->input('insignia_id', $request->input('rank_id')),
                    'position_id' => $request->input('position_id'),
                    'status'      => 'active',
                ]);
            } else {
                $persona->update([
                    'name'        => $request->input('proposed_persona_name', $persona->name),
                    'insignia_id' => $request->input('insignia_id', $request->input('rank_id', $persona->insignia_id ?? $persona->rank_id)),
                    'position_id' => $request->input('position_id', $persona->position_id),
                    'status'      => 'active',
                ]);
            }

            $petition->update([
                'persona_id'      => $persona->id,
                'petition_status' => 'Approved',
            ]);
        });

        return response()->json([
            'status'          => 'success',
            'success'         => true,
            'petition_status' => 'Approved',
            'message'         => lexicon('msg_AdminPetitions_ApprovedSuccess', 'Candidate petition approved and persona activated.'),
        ]);
    }

    /**
     * Reject a candidate petition or execute an atomic hard_purge transaction.
     */
    public function reject(Request $request, CandidatePetition $petition)
    {
        abort_unless(auth()->user()?->can('access-admin'), 403);

        $isHardPurge = $request->boolean('hard_purge') || $request->input('mode') === 'hard_purge';

        if ($isHardPurge) {
            DB::transaction(function () use ($petition) {
                $petition->answers()->delete();
                $petition->votes()->delete();
                $petition->delete();
            });

            return response()->json([
                'status'  => 'success',
                'success' => true,
                'purged'  => true,
                'message' => lexicon('msg_AdminPetitions_HardPurged', 'Candidate petition and associated records have been permanently purged.'),
            ]);
        }

        $petition->update([
            'petition_status' => 'Rejected',
        ]);

        return response()->json([
            'status'          => 'success',
            'success'         => true,
            'purged'          => false,
            'petition_status' => 'Rejected',
            'message'         => lexicon('msg_AdminPetitions_RejectedSuccess', 'Candidate petition has been marked as rejected.'),
        ]);
    }
}
