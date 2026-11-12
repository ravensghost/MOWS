<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Book;
use App\Models\CandidatePetition;
use App\Models\ChronologicalRegister;
use App\Models\Configuration;
use App\Models\CorrespondenceThread;
use App\Models\Division;
use App\Models\Insignia;
use App\Models\InsigniaCatalogue;
use App\Models\Metadata;
use App\Models\Persona;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CandidatePetitionController extends Controller
{
    /**
     * Verify that the current user is authorised to access Candidate Petition & Roster Management.
     */
    protected function authoriseAccess(string $specificPermission = 'manage_applications'): void
    {
        $user = auth()->user();

        if (! $user) {
            abort(403, lexicon('msg_Applications_Unauthorised', 'Unauthorised access to Petition Management.'));
        }

        if (
            $user->role === 'webmaster'
            || (method_exists($user, 'hasRole') && $user->hasRole('webmaster'))
            || $user->can('manage_applications')
            || $user->can($specificPermission)
        ) {
            return;
        }

        abort(403, lexicon('msg_Applications_Unauthorised', 'Unauthorised access to Petition Management.'));
    }

    /**
     * Display the Candidate Petition & Roster Queue inside the Admin Control Panel.
     */
    public function index()
    {
        $this->authoriseAccess('applications_vote');

        $systemPage = Book::where('slug', 'admin/applications')
            ->where('is_published', true)
            ->with(['grid.gridModules', 'module'])
            ->firstOrFail();

        $reviewMode = Configuration::where('configuration_token', 'approval_review_mode')
            ->value('configuration_payload') ?? 'single';

        $metadataById = Metadata::all()->keyBy('id');

        $petitions = CandidatePetition::with(['user', 'answers', 'votes'])
            ->where('petition_status', 'Pending')
            ->orderBy('lodged_at', 'asc')
            ->get();

        foreach ($petitions as $petition) {
            foreach ($petition->answers as $entry) {
                $meta = $metadataById->get($entry->metadata_id);

                $entry->setRelation('metadata', $meta);
                $entry->setRelation('formField', (object) [
                    'id'                  => $meta?->id ?? $entry->metadata_id,
                    'metadata_title'      => $meta?->metadata_title ?? ('Field #' . $entry->metadata_id),
                    'metadata_mode'       => $meta?->metadata_mode ?? 'text',
                    'metadata_sort_order' => $meta?->metadata_sort_order ?? 999,
                ]);
            }
        }

        $activePersonae = Persona::with(['assignment', 'insignia', 'user'])
            ->whereIn('persona_status', ['active', 'loa', 'eloa'])
            ->orderBy('persona_name')
            ->get();

        $divisions = Division::with(['assignments' => function ($query) {
            $query->orderBy('assignment_title');
        }])->get();

        $insigniaCatalogues = InsigniaCatalogue::with(['insignia' => function ($query) {
            $query->orderBy('insignia_sort_order', 'desc');
        }])->get();

        return view('admin.candidate_petitions', [
            'systemPage'         => $systemPage,
            'petitions'          => $petitions,
            'activePersonae'     => $activePersonae,
            'divisions'          => $divisions,
            'insigniaCatalogues' => $insigniaCatalogues,
            'reviewMode'         => $reviewMode,
        ]);
    }

    /**
     * Approve a pending candidate petition, create the Persona, migrate chronological register records
     * and custom metadata attributes, and notify the petitioner.
     */
    public function approve(Request $request, $id)
    {
        $this->authoriseAccess('applications_approve');

        $validated = $request->validate([
            'assignment_id' => 'required|integer',
            'insignia_id'   => 'required|integer',
        ]);

        $petition = CandidatePetition::with(['answers', 'user'])->findOrFail($id);

        if (strtolower($petition->petition_status) !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => lexicon('msg_Applications_NotPending', 'This petition is no longer pending review.'),
            ], 422);
        }

        $metadataById = Metadata::all()->keyBy('id');

        DB::transaction(function () use ($petition, $validated, $metadataById) {
            $persona = Persona::create([
                'user_id'        => $petition->user_id,
                'persona_name'   => $petition->proposed_persona_name,
                'persona_status' => 'active',
                'assignment_id'  => $validated['assignment_id'],
                'insignia_id'    => $validated['insignia_id'],
            ]);

            foreach ($petition->answers as $entry) {
                $meta = $metadataById->get($entry->metadata_id);
                $mode = $meta?->metadata_mode ?? 'text';
                $keyName = $meta?->metadata_title ?? ('field_' . $entry->metadata_id);

                if ($mode === 'service_record' && ! empty($entry->petition_answer)) {
                    $records = json_decode($entry->petition_answer, true);
                    if (is_array($records)) {
                        foreach ($records as $record) {
                            $desc = trim($record['chronicle_text'] ?? '');
                            if ($desc === '') {
                                continue;
                            }
                            $era = trim($record['era'] ?? '');
                            $chronicleText = $era !== '' ? "[{$era}] {$desc}" : $desc;
                            $eventMode = in_array($record['register_event_mode'] ?? '', ChronologicalRegister::allEventTypes(), true)
                                ? $record['register_event_mode']
                                : 'background';

                            ChronologicalRegister::create([
                                'user_id'             => $petition->user_id,
                                'persona_id'          => $persona->id,
                                'register_event_mode' => $eventMode,
                                'chronicle_text'      => $chronicleText,
                                'authorising_user_id' => auth()->id(),
                                'scope_target'        => 'IC',
                            ]);
                        }
                    }
                } else {
                    $persona->attributes()->updateOrCreate(
                        ['attribute_key' => $keyName],
                        ['attribute_value' => (string) $entry->petition_answer]
                    );
                }
            }

            $assignmentTitle = Assignment::where('id', $validated['assignment_id'])->value('assignment_title') ?? 'Assigned Position';
            $insigniaTitle = Insignia::where('id', $validated['insignia_id'])->value('insignia_title') ?? 'Assigned Insignia';

            ChronologicalRegister::create([
                'user_id'             => $petition->user_id,
                'persona_id'          => $persona->id,
                'register_event_mode' => 'assignment',
                'chronicle_text'      => "Petition approved. Integrated into active roster as {$insigniaTitle}, {$assignmentTitle}.",
                'authorising_user_id' => auth()->id(),
                'scope_target'        => 'BOTH',
            ]);

            $petition->update([
                'persona_id'      => $persona->id,
                'petition_status' => 'Approved',
            ]);

            if ($petition->user) {
                $petition->user->setAttr('app_status', 'approved');
            }
        });

        Cache::forget('widget_roster_sections');
        Cache::forget('widget_manifest_sections');

        if ($petition->user && class_exists(CorrespondenceThread::class)) {
            CorrespondenceThread::createSystemMessage(
                auth()->user(),
                [$petition->user],
                "Your candidate petition for **{$petition->proposed_persona_name}** has been approved and integrated into the active roster!",
                'Petition Approved: ' . $petition->proposed_persona_name
            );
        }

        return response()->json([
            'success' => true,
            'message' => lexicon('msg_Applications_ApprovedSuccess', 'Petition approved and persona integrated into the active roster!'),
        ]);
    }

    /**
     * Reject a pending candidate petition and execute the configured rejection protocol.
     */
    public function reject(Request $request, $id)
    {
        $this->authoriseAccess('applications_approve');

        $petition = CandidatePetition::with('user')->findOrFail($id);

        if (strtolower($petition->petition_status) !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => lexicon('msg_Applications_NotPending', 'This petition is no longer pending review.'),
            ], 422);
        }

        $rejectionProtocol = Configuration::where('configuration_token', 'rejection_protocol')
            ->value('configuration_payload') ?? 'soft_lock';

        DB::transaction(function () use ($petition, $rejectionProtocol) {
            $petition->update([
                'petition_status' => 'Rejected',
            ]);

            $applicant = $petition->user;

            if ($applicant) {
                $hasActivePersonae = $applicant->personae()->exists();

                if ($rejectionProtocol === 'hard_purge' && !$hasActivePersonae && $applicant->role !== 'webmaster') {
                    $applicant->delete();
                } else {
                    $applicant->setAttr('app_status', 'rejected');

                    if (class_exists(CorrespondenceThread::class)) {
                        CorrespondenceThread::createSystemMessage(
                            auth()->user(),
                            [$applicant],
                            "Your candidate petition for **{$petition->proposed_persona_name}** was not approved at this time.",
                            'Petition Outcome: ' . $petition->proposed_persona_name
                        );
                    }
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => lexicon('msg_Applications_RejectedSuccess', 'Petition has been rejected.'),
        ]);
    }

    /**
     * Reassign an active persona's assignment and insignia, logging changes to the ChronologicalRegister.
     */
    public function reassign(Request $request, $id)
    {
        $this->authoriseAccess('manage_applications');

        $validated = $request->validate([
            'assignment_id' => 'required|integer',
            'insignia_id'   => 'required|integer',
        ]);

        $persona = Persona::findOrFail($id);

        $oldAssignmentId = $persona->assignment_id;
        $oldInsigniaId = $persona->insignia_id;

        $persona->update([
            'assignment_id' => $validated['assignment_id'],
            'insignia_id'   => $validated['insignia_id'],
        ]);

        if ((int) $oldInsigniaId !== (int)$validated['insignia_id']) {
            $newInsigniaTitle = Insignia::where('id', $validated['insignia_id'])->value('insignia_title') ?? 'New Insignia';
            ChronologicalRegister::create([
                'user_id'             => $persona->user_id,
                'persona_id'          => $persona->id,
                'register_event_mode' => 'promotion',
                'chronicle_text'      => "Insignia updated to {$newInsigniaTitle}.",
                'authorising_user_id' => auth()->id(),
                'scope_target'        => 'IC',
            ]);
        }

        if ((int) $oldAssignmentId !== (int)$validated['assignment_id']) {
            $newAssignmentTitle = Assignment::where('id', $validated['assignment_id'])->value('assignment_title') ?? 'New Assignment';
            ChronologicalRegister::create([
                'user_id'             => $persona->user_id,
                'persona_id'          => $persona->id,
                'register_event_mode' => 'transfer',
                'chronicle_text'      => "Transferred to assignment: {$newAssignmentTitle}.",
                'authorising_user_id' => auth()->id(),
                'scope_target'        => 'IC',
            ]);
        }

        Cache::forget('widget_roster_sections');
        Cache::forget('widget_manifest_sections');

        return response()->json([
            'success' => true,
            'message' => lexicon('msg_Applications_ReassignedSuccess', 'Persona assignment updated successfully.'),
        ]);
    }
}
