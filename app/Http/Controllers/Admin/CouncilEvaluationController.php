<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CandidatePetition;
use App\Models\CouncilEvaluation;
use Illuminate\Http\Request;

class CouncilEvaluationController extends Controller
{
    /**
     * Verify that the current user is authorised to cast a council evaluation vote.
     */
    protected function authoriseVoteAccess(): void
    {
        $user = auth()->user();

        if (! $user) {
            abort(403, lexicon('msg_Applications_Unauthorised', 'Unauthorised access to Petition Management.'));
        }

        if (
            $user->role === 'webmaster'
            || (method_exists($user, 'hasRole') && $user->hasRole('webmaster'))
            || $user->can('applications_vote')
            || $user->can('manage_applications')
        ) {
            return;
        }

        abort(403, lexicon('msg_Applications_Unauthorised', 'Unauthorised access to Petition Management.'));
    }

    /**
     * Store or update a council evaluation for a candidate petition.
     */
    public function store(Request $request, $petitionId)
    {
        $this->authoriseVoteAccess();

        $validated = $request->validate([
            'evaluation_decision'   => 'required|in:accept,reject,abstain',
            'evaluation_commentary' => 'nullable|string|max:1000',
        ]);

        $petition = CandidatePetition::findOrFail($petitionId);

        if (strtolower($petition->petition_status) !== 'pending') {
            return response()->json(['status' => 'error', 'message' => lexicon('msg_Applications_NoLongerPending', 'This candidate petition is no longer pending.'), 'data' => $petition, 'redirect' => null], 422);
        }

        $model = CouncilEvaluation::updateOrCreate(
            [
                'candidate_petition_id' => $petition->id,
                'evaluating_user_id'    => auth()->id(),
            ],
            [
                'subject_user_id'       => $petition->user_id,
                'evaluation_decision'   => $validated['evaluation_decision'],
                'evaluation_commentary' => $validated['evaluation_commentary'] ?? null,
            ]
        );

        return response()->json(['status' => 'success', 'message' => lexicon('msg_Applications_VoteCastSuccess', 'Your council evaluation has been successfully cast.'), 'data' => $model, 'redirect' => null], 200);
    }
}