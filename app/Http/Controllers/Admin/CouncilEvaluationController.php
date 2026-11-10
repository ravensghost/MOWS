<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CandidatePetition;
use App\Models\CouncilEvaluation;

class CouncilEvaluationController extends Controller
{
    protected function authorizeVoteAccess(): void
    {
        $user = auth()->user();

        if (!$user) {
            abort(403, lexicon('msg_Applications_Unauthorised', 'Unauthorised access to Application Management.'));
        }

        if ($user->role === 'webmaster' || (method_exists($user, 'hasRole') && $user->hasRole('webmaster')) || $user->can('applications_vote') || $user->can('manage_applications')) {
            return;
        }

        abort(403, lexicon('msg_Applications_Unauthorised', 'Unauthorised access to Application Management.'));
    }

    public function store(Request $request, $petitionId)
    {
        $this->authorizeVoteAccess();

        $validated = $request->validate([
            'evaluation_decision'   => 'required|in:accept,reject,abstain',
            'evaluation_commentary' => 'nullable|string|max:1000',
        ]);

        $petition = CandidatePetition::findOrFail($petitionId);

        if (strtolower($petition->status) !== 'pending') {
            if ($request->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => 'This candidate petition is no longer pending.'], 422);
            }
            return back()->with('error', 'This candidate petition is no longer pending.');
        }

        // Upsert the evaluation for this evaluating user on this petition
        CouncilEvaluation::updateOrCreate(
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

        $message = lexicon('msg_Applications_VoteCastSuccess', 'Your council evaluation has been successfully cast.');

        if ($request->wantsJson()) {
            return response()->json(['status' => 'success', 'success' => true, 'message' => $message]);
        }

        return back()->with('status', $message);
    }
}
