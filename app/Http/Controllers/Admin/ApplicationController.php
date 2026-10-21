<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\Application;
use App\Models\Character;
use App\Models\Position;
use App\Models\Ranks;
use App\Models\Section;
use App\Models\RankSet;
use App\Models\Page;
use App\Models\Setting;
use App\Models\ServiceRecord;

class ApplicationController extends Controller
{
    protected function authorizeApplicationAccess(array $permissions): void
    {
        $user = auth()->user();

        if (!$user) {
            abort(403, lexicon('msg_Applications_Unauthorised', 'Unauthorised access to Application Management.'));
        }

        // Allow webmaster role column or Spatie webmaster role automatically
        if ($user->role === 'webmaster' || (method_exists($user, 'hasRole') && $user->hasRole('webmaster'))) {
            return;
        }

        foreach ($permissions as $permission) {
            if ($user->can($permission)) {
                return;
            }
        }

        abort(403, lexicon('msg_Applications_Unauthorised', 'Unauthorised access to Application Management.'));
    }

    public function index()
    {
        $this->authorizeApplicationAccess(['applications_view', 'manage_applications']);
        $systemPage = Page::where('slug', 'admin/applications')
            ->where('is_published', true)
            ->with([
                'layout.blocks',
                'widgets' => function ($query) {
                    $query->orderBy('page_widget.sort_order');
                }
            ])
            ->firstOrFail();

        $applications = Application::with(['user', 'answers.formField', 'votes.councilMember'])
            ->whereIn('status', ['Pending', 'pending'])
            ->orderBy('submitted_at', 'desc')
            ->get();

        $activeCharacters = Character::with(['position.section', 'rank', 'user'])
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $sections = Section::with(['positions' => function ($q) {
            $q->where('is_open', true);
        }])->orderBy('order')->get();

        $rankSets = RankSet::with(['ranks' => function ($q) {
            $q->orderBy('sort_order');
        }])->get();

        $reviewMode = Setting::where('key', 'approval_review_mode')->value('value') ?? 'single';
        $rejectionProtocol = Setting::where('key', 'rejection_protocol')->value('value') ?? 'soft_lock';

        return view('admin.applications', compact('applications', 'activeCharacters', 'sections', 'rankSets', 'systemPage', 'reviewMode', 'rejectionProtocol'));
    }

    public function reject(Request $request, $id)
    {
        $this->authorizeApplicationAccess(['applications_approve', 'manage_applications']);
        $application = Application::with('user')->findOrFail($id);
        $protocol = Setting::where('key', 'rejection_protocol')->value('value') ?? 'soft_lock';

        if ($protocol === 'hard_purge') {
            DB::transaction(function () use ($application) {
                $user = $application->user;
                $hasActiveChars = $user ? $user->characters()->where('status', 'active')->exists() : false;

                $application->answers()->delete();
                $application->votes()->delete();
                $application->delete();

                if ($user && !$hasActiveChars) {
                    $user->attributes()->delete();
                    $user->delete();
                }
            });
            $message = lexicon('msg_Applications_PurgedSuccess', 'Application hard-purged and unlinked user account removed.');
        } else {
            // Soft Lock protocol: set status to Rejected and user attribute app_status to rejected for resubmission
            $application->update(['status' => 'Rejected']);
            if ($application->user) {
                $application->user->setAttr('app_status', 'rejected');
            }
            $message = lexicon('msg_Applications_RejectedSuccess', 'Application rejected. The applicant may revise and resubmit.');
        }

        if ($request->wantsJson()) {
            return response()->json(['status' => 'success', 'success' => true, 'message' => $message]);
        }

        return redirect('/admin/applications')->with('status', $message);
    }



    public function approve(Request $request, $id)
    {
        $this->authorizeApplicationAccess(['applications_approve', 'manage_applications']);
        $validated = $request->validate([
            'position_id' => 'required|exists:positions,id',
            'rank_id'     => 'required|exists:ranks,id',
        ]);

        $application = Application::with('answers.formField')->findOrFail($id);
        $position = Position::with('section')->findOrFail($validated['position_id']);
        $rank = Ranks::findOrFail($validated['rank_id']);

        $character = Character::create([
            'user_id'        => $application->user_id,
            'position_id'    => $position->id,
            'rank_id'        => $rank->id,
            'character_type' => 'PC',
            'name'           => $application->character_name,
            'status'         => 'active',
        ]);

        $application->update([
            'status'       => 'Approved',
            'character_id' => $character->id,
        ]);

        // Integrate Applicant's Submitted Service Record Timeline Entries
        $groupToEventTypes = ServiceRecord::eventTypeGroups();

        foreach ($application->answers as $answer) {
            if ($answer->formField && $answer->formField->type === 'service_record' && !empty($answer->answer)) {
                $entries = json_decode($answer->answer, true);
                $rawFieldOptions = $answer->formField->options;
                $fieldGroups = is_array($rawFieldOptions)
                    ? $rawFieldOptions
                    : (json_decode((string) $rawFieldOptions, true) ?: []);

                // Build allowed event types for this specific form field based on selected optgroups
                $allowedEvents = [];
                foreach ($fieldGroups as $groupKey) {
                    if (is_string($groupKey) && isset($groupToEventTypes[$groupKey]['types'])) {
                        $allowedEvents = array_merge($allowedEvents, array_keys($groupToEventTypes[$groupKey]['types']));
                    }
                }
                // Fallback if none configured
                if (empty($allowedEvents)) {
                    $allowedEvents = ServiceRecord::allEventTypes();
                }

                if (is_array($entries)) {
                    foreach ($entries as $entry) {
                        $desc = trim($entry['description'] ?? '');
                        if ($desc === '') {
                            continue;
                        }
                        $era = trim($entry['era'] ?? '');
                        $eventType = in_array($entry['event_type'] ?? '', $allowedEvents, true)
                            ? $entry['event_type']
                            : ($allowedEvents[0] ?? 'background');

                        $fullDescription = $era !== '' ? "[{$era}] {$desc}" : $desc;

                        ServiceRecord::create([
                            'user_id'        => $character->user_id,
                            'character_id'   => $character->id,
                            'event_type'     => $eventType,
                            'description'    => $fullDescription,
                            'issued_by'      => auth()->id(),
                            'display_target' => 'IC',
                        ]);
                    }
                }
            }
        }

        $positionLabel = $position->section
            ? "{$position->name} ({$position->section->name})"
            : $position->name;

        // Automated Final Service Record Entry on Approval
        ServiceRecord::create([
            'user_id'        => $character->user_id,
            'character_id'   => $character->id,
            'event_type'     => 'transfer',
            'description'    => "Application approved. Assigned as {$rank->name} — {$positionLabel}.",
            'issued_by'      => auth()->id(),
            'display_target' => 'BOTH',
        ]);

        Cache::forget('widget_roster_sections');

        $message = lexicon('msg_Applications_ApprovedSuccess', 'Application approved! Character linked to position, added to active roster, and logged in service record.');

        if ($request->wantsJson()) {
            return response()->json(['status' => 'success', 'success' => true, 'message' => $message]);
        }

        return redirect('/admin/applications')->with('status', $message);
    }
}
