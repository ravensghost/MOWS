<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Application;
use App\Models\ApplicationVote;

class ApplicationVoteController extends Controller
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

    public function store(Request $request, $applicationId)
    {
        $this->authorizeVoteAccess();

        $validated = $request->validate([
            'vote'     => 'required|in:accept,reject,abstain',
            'comments' => 'nullable|string|max:1000',
        ]);

        $application = Application::findOrFail($applicationId);

        if (strtolower($application->status) !== 'pending') {
            if ($request->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => 'This application is no longer pending.'], 422);
            }
            return back()->with('error', 'This application is no longer pending.');
        }

        // Upsert the vote for this council member on this application
        ApplicationVote::updateOrCreate(
            [
                'application_id'    => $application->id,
                'council_member_id' => auth()->id(),
            ],
            [
                'target_user_id'    => $application->user_id,
                'vote'              => $validated['vote'],
                'comments'          => $validated['comments'] ?? null,
            ]
        );

        $message = lexicon('msg_Applications_VoteCastSuccess', 'Your council vote has been successfully cast.');

        if ($request->wantsJson()) {
            return response()->json(['status' => 'success', 'success' => true, 'message' => $message]);
        }

        return back()->with('status', $message);
    }
}
