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
            || (method_exists($user, 'hasRole') &&$user->hasRole('webmaster'))
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

        $petitions = CandidatePetition::with(['user', 'persona', 'entries', 'answers', 'evaluations', 'votes'])
            ->withCount('evaluations')
            ->orderBy('lodged_at', 'desc')
            ->get();

        foreach ($petitions as$petition) {
            foreach ($petition->entries as $entry) {$meta = $metadataById->get($entry->metadata_id);

                $entry->setRelation('metadata', $meta);$entry->setRelation('formField', (object) [
                    'id'                  => $meta?->id ?? $entry->metadata_id,
                    'metadata_title'      => $meta?->metadata_title ?? ('Field #' . $entry->metadata_id),
                    'metadata_mode'       => $meta?->metadata_mode ?? 'text',
                    'metadata_sort_order' => $meta?->metadata_sort_order ?? 999,
                ]);
            }

            $petition->setRelation('answers',$petition->entries);
        }

        $activePersonae = Persona::with(['assignment', 'insignia', 'user'])
            ->whereIn('persona_status', ['active', 'loa', 'eloa'])
            ->orderBy('persona_name')
            ->get();

        $divisions = Division::with(['assignments' => function ($query) {$query->orderBy('assignment_title');
        }])->get();

        $insigniaCatalogues = InsigniaCatalogue::with(['insignia' => function ($query) {$query->orderBy('insignia_sort_order', 'desc');
        }])->get();

        $assignments = Assignment::orderBy('assignment_title')->get();$insignias = Insignia::orderBy('insignia_sort_order', 'desc')->get();

        return view('admin.candidate_petitions', [
            'systemPage'         => $systemPage,
            'petitions'          => $petitions,
            'activePersonae'     => $activePersonae,
            'personas'           => $activePersonae,
            'divisions'          => $divisions,
            'insigniaCatalogues' => $insigniaCatalogues,
            'assignments'        => $assignments,
            'insignias'          => $insignias,
            'reviewMode'         => $reviewMode,
        ]);
    }

    /**
     * Store a newly created candidate petition from the Admin Control Panel.
     */
    public function store(Request $request)
    {
        $this->authoriseAccess('manage_applications');

        $validated =$request->validate([
            'proposed_persona_name' => 'required|string|max:255',
            'user_id'               => 'nullable|integer|exists:users,id',
            'persona_id'            => 'nullable|integer|exists:personae,id',
            'petition_status'       => 'nullable|string|max:50',
        ]);

        $model = CandidatePetition::create([
            'user_id'               => $validated['user_id'] ?? auth()->id(),
            'persona_id'            => $validated['persona_id'] ?? null,
            'proposed_persona_name' => $validated['proposed_persona_name'],
            'petition_status'       => $validated['petition_status'] ?? 'Pending',
            'lodged_at'             => now(),
        ]);

        return response()->json(['status' => 'success', 'message' => lexicon('msg_Applications_CreatedSuccess', 'Candidate petition created successfully.'), 'data' => $model, 'redirect' => null], 200);
    }

    /**
     * Update an existing candidate petition record.
     */
    public function update(Request $request,$id)
    {
        $this->authoriseAccess('manage_applications');

        $validated =$request->validate([
            'proposed_persona_name' => 'sometimes|required|string|max:255',
            'persona_id'            => 'nullable|integer|exists:personae,id',
            'petition_status'       => 'sometimes|required|string|max:50',
            'assignment_id'         => 'nullable|integer',
            'insignia_id'           => 'nullable|integer',
        ]);

        $model = CandidatePetition::findOrFail($id);

        $model->update([
            'proposed_persona_name' => $validated['proposed_persona_name'] ?? $model->proposed_persona_name,
            'persona_id'            => array_key_exists('persona_id', $validated) ? $validated['persona_id'] :$model->persona_id,
            'petition_status'       => $validated['petition_status'] ?? $model->petition_status,
        ]);

        if ($model->persona_id && (! empty($validated['assignment_id']) \vert{}\vert{} ! empty($validated['insignia_id']))) {
            $persona = Persona::find($model->persona_id);
            if ($persona) {$persona->update([
                    'persona_name'  => $validated['proposed_persona_name'] ?? $persona->persona_name,
                    'assignment_id' => $validated['assignment_id'] ?? $persona->assignment_id,
                    'insignia_id'   => $validated['insignia_id'] ?? $persona->insignia_id,
                ]);
            }
        }

        Cache::forget('widget_roster_sections');
        Cache::forget('widget_manifest_sections');

        $model->load(['user', 'persona', 'entries', 'evaluations']);

        return response()->json(['status' => 'success', 'message' => lexicon('msg_Applications_UpdatedSuccess', 'Candidate petition updated successfully.'), 'data' => $model, 'redirect' => null], 200);
    }

    /**
     * Delete a candidate petition record.
     */
    public function destroy($id)
    {
        $this->authoriseAccess('manage_applications');

        $model = CandidatePetition::findOrFail($id);$model->delete();

        return response()->json(['status' => 'success', 'message' => lexicon('msg_Applications_DeletedSuccess', 'Candidate petition deleted successfully.'), 'data' => $model, 'redirect' => null], 200);
    }

    /**
     * Approve a pending candidate petition, create the Persona, migrate chronological register records
     * and custom metadata attributes, and notify the petitioner.
     */
    public function approve(Request $request,$id)
    {
        $this->authoriseAccess('applications_approve');

        $validated =$request->validate([
            'assignment_id' => 'required|integer',
            'insignia_id'   => 'required|integer',
        ]);

        $model = CandidatePetition::with(['entries', 'user'])->findOrFail($id);

        if (strtolower($model->petition_status) !== 'pending') {
            return response()->json(['status' => 'error', 'message' => lexicon('msg_Applications_NotPending', 'This petition is no longer pending review.'), 'data' => $model, 'redirect' => null], 422);
        }

        $metadataById = Metadata::all()->keyBy('id');

        DB::transaction(function () use ($model,$validated, $metadataById) {$persona = Persona::create([
                'user_id'        => $model->user_id,
                'persona_name'   => $model->proposed_persona_name,
                'persona_status' => 'active',
                'assignment_id'  => $validated['assignment_id'],
                'insignia_id'    => $validated['insignia_id'],
            ]);

            foreach ($model->entries as $entry) {$meta = $metadataById->get($entry->metadata_id);
                $mode =$meta?->metadata_mode ?? 'text';
                $keyName = $meta?->metadata_title ?? ('field_' . $entry->metadata_id);

                if ($mode === 'service_record' && ! empty($entry->petition_answer)) {
                    $records = json_decode($entry->petition_answer, true);
                    if (is_array($records)) {
                        foreach ($records as$record) {
                            $desc = trim($record['chronicle_text'] ?? '');
                            if ($desc === '') { continue; }$era = trim($record['era'] ?? '');$chronicleText = $era !== '' ? "[{$era}] {$desc}" : $desc;
                            $eventMode = in_array($record['register_event_mode'] ?? '', ChronologicalRegister::allEventTypes(), true)
                                ? $record['register_event_mode']
                                : 'background';

                            ChronologicalRegister::create([
                                'user_id'             => $model->user_id,
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

            $assignmentTitle = Assignment::where('id',$validated['assignment_id'])->value('assignment_title') ?? 'Assigned Position';
            $insigniaTitle = Insignia::where('id',$validated['insignia_id'])->value('insignia_title') ?? 'Assigned Insignia';

            ChronologicalRegister::create([
                'user_id'             => $model->user_id,
                'persona_id'          => $persona->id,
                'register_event_mode' => 'assignment',
                'chronicle_text'      => "Petition approved. Integrated into active roster as {$insigniaTitle}, {$assignmentTitle}.",
                'authorising_user_id' => auth()->id(),
                'scope_target'        => 'BOTH',
            ]);

            $model->update([
                'persona_id'      => $persona->id,
                'petition_status' => 'Approved',
            ]);

            if ($model->user) {$model->user->setAttr('app_status', 'approved');
            }
        });

        Cache::forget('widget_roster_sections');
        Cache::forget('widget_manifest_sections');

        if ($model->user && class_exists(CorrespondenceThread::class)) {
            CorrespondenceThread::createSystemMessage(
                auth()->user(),
                [$model->user],
                "Your candidate petition for **{$model->proposed_persona_name}** has been approved and integrated into the active roster!",
                'Petition Approved: ' . $model->proposed_persona_name
            );
        }

        $model->load(['persona', 'user']);

        return response()->json(['status' => 'success', 'message' => lexicon('msg_Applications_ApprovedSuccess', 'Petition approved and persona integrated into the active roster!'), 'data' => $model, 'redirect' => null], 200);
    }

    /**
     * Reject a pending candidate petition and execute the configured rejection protocol.
     */
    public function reject(Request $request,$id)
    {
        $this->authoriseAccess('applications_approve');

        $model = CandidatePetition::with('user')->findOrFail($id);

        if (strtolower($model->petition_status) !== 'pending') {
            return response()->json(['status' => 'error', 'message' => lexicon('msg_Applications_NotPending', 'This petition is no longer pending review.'), 'data' => $model, 'redirect' => null], 422);
        }

        $rejectionProtocol = Configuration::where('configuration_token', 'rejection_protocol')
            ->value('configuration_payload') ?? 'soft_lock';

        DB::transaction(function () use ($model, $rejectionProtocol) {$model->update([
                'petition_status' => 'Rejected',
            ]);

            $applicant =$model->user;

            if ($applicant) {
                $hasActivePersonae =$applicant->personae()->exists();

                if ($rejectionProtocol === 'hard_purge' && !$hasActivePersonae && $applicant->role !== 'webmaster') {$applicant->delete();
                } else {
                    $applicant->setAttr('app_status', 'rejected');

                    if (class_exists(CorrespondenceThread::class)) {
                        CorrespondenceThread::createSystemMessage(
                            auth()->user(),
                            [$applicant],
                            "Your candidate petition for **{$model->proposed_persona_name}** was not approved at this time.",
                            'Petition Outcome: ' . $model->proposed_persona_name
                        );
                    }
                }
            }
        });

        return response()->json(['status' => 'success', 'message' => lexicon('msg_Applications_RejectedSuccess', 'Petition has been rejected.'), 'data' => $model, 'redirect' => null], 200);
    }

    /**
     * Reassign an active persona's assignment and insignia, logging changes to the ChronologicalRegister.
     */
    public function reassign(Request $request,$id)
    {
        $this->authoriseAccess('manage_applications');

        $validated =$request->validate([
            'assignment_id' => 'required|integer',
            'insignia_id'   => 'required|integer',
        ]);

        $model = Persona::findOrFail($id);

        $oldAssignmentId =$model->assignment_id;
        $oldInsigniaId =$model->insignia_id;

        $model->update([
            'assignment_id' => $validated['assignment_id'],
            'insignia_id'   => $validated['insignia_id'],
        ]);

        if ((int) $oldInsigniaId !== (int)$validated['insignia_id']) {
            $newInsigniaTitle = Insignia::where('id',$validated['insignia_id'])->value('insignia_title') ?? 'New Insignia';
            ChronologicalRegister::create([
                'user_id'             => $model->user_id,
                'persona_id'          => $model->id,
                'register_event_mode' => 'promotion',
                'chronicle_text'      => "Insignia updated to {$newInsigniaTitle}.",
                'authorising_user_id' => auth()->id(),
                'scope_target'        => 'IC',
            ]);
        }

        if ((int) $oldAssignmentId !== (int)$validated['assignment_id']) {
            $newAssignmentTitle = Assignment::where('id',$validated['assignment_id'])->value('assignment_title') ?? 'New Assignment';
            ChronologicalRegister::create([
                'user_id'             => $model->user_id,
                'persona_id'          => $model->id,
                'register_event_mode' => 'transfer',
                'chronicle_text'      => "Transferred to assignment: {$newAssignmentTitle}.",
                'authorising_user_id' => auth()->id(),
                'scope_target'        => 'IC',
            ]);
        }

        Cache::forget('widget_roster_sections');
        Cache::forget('widget_manifest_sections');

        return response()->json(['status' => 'success', 'message' => lexicon('msg_Applications_ReassignedSuccess', 'Persona assignment updated successfully.'), 'data' => $model, 'redirect' => null], 200);
    }
}