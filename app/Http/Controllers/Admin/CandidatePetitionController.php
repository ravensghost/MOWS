<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
use App\Models\Position;
use App\Models\RankSet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CandidatePetitionController extends Controller
{
    /**
     * Verify that the current user is authorised to access Application & Roster Management.
     */
    protected function authoriseAccess(string $specificPermission = 'manage_applications'): void
    {
        $user = auth()->user();

        if (! $user) {
            abort(403, lexicon('msg_Applications_Unauthorised', 'Unauthorised access to Application Management.'));
        }

        if (
            $user->role === 'webmaster'
            || (method_exists($user, 'hasRole') && $user->hasRole('webmaster'))
            || $user->can('manage_applications')
            || $user->can($specificPermission)
        ) {
            return;
        }

        abort(403, lexicon('msg_Applications_Unauthorised', 'Unauthorised access to Application Management.'));
    }

    /**
     * Display the Application & Roster Queue inside the Admin Control Panel.
     */
    public function index()
    {
        $this->authoriseAccess('applications_vote');

        $systemPage = Book::where(function ($query) {
            $query->where('slug', 'admin/applications');
            if (Schema::hasColumn('books', 'book_url')) {
                $query->orWhere('book_url', 'admin/applications');
            }
            if (Schema::hasColumn('books', 'books_uri')) {
                $query->orWhere('books_uri', 'admin/applications');
            }
        })
            ->where('is_published', true)
            ->with(['grid.gridModules', 'module'])
            ->firstOrFail();

        $reviewMode = Configuration::where('configuration_token', 'approval_review_mode')
            ->value('configuration_payload') ?? 'single';

        // Load all Metadata fields to map onto PetitionEntry answers for the Blade template
        $metadataById = Metadata::all()->keyBy('id');

        $applications = CandidatePetition::with(['user', 'answers', 'votes'])
            ->where('petition_status', 'Pending')
            ->orderBy('lodged_at', 'asc')
            ->get();

        // Hydrate legacy & canonical property bridges expected by candidatepetition.blade.php
        foreach ($applications as $app) {
            foreach ($app->answers as $entry) {
                $meta = $metadataById->get($entry->metadata_id);

                $entry->form_field_id = $entry->metadata_id;
                $entry->answer = $entry->petition_answer;

                $entry->setRelation('formField', (object) [
                    'id'    => $meta?->id ?? $entry->metadata_id,
                    'name'  => $meta?->metadata_title ?? $meta?->name ?? ('Field #' . $entry->metadata_id),
                    'type'  => $meta?->metadata_mode ?? $meta?->type ?? 'text',
                    'order' => $meta?->metadata_sort_order ?? $meta?->order ?? 999,
                ]);
            }

            foreach ($app->votes as $vote) {
                $vote->council_member_id = $vote->evaluating_user_id ?? $vote->council_member_id;
                $vote->vote = $vote->evaluation_decision ?? $vote->vote;
            }
        }

        // Load active characters and normalize legacy/canonical attribute aliases for the view
        $personaStatusCol = Schema::hasColumn('personae', 'persona_status') ? 'persona_status' : 'status';
        $personaNameCol = Schema::hasColumn('personae', 'persona_name') ? 'persona_name' : 'name';

        $activeCharacters = Persona::with(['position', 'rank', 'user'])
            ->whereIn($personaStatusCol, ['active', 'loa', 'eloa'])
            ->orderBy($personaNameCol)
            ->get()
            ->each(function (Persona $char) {
                $char->name = $char->persona_name ?? $char->attributes['name'] ?? 'Unknown';
                $char->status = $char->persona_status ?? $char->attributes['status'] ?? 'active';
                $char->position_id = $char->assignment_id ?? $char->attributes['position_id'] ?? null;
                $char->rank_id = $char->insignia_id ?? $char->attributes['rank_id'] ?? null;
            });

        // Load Sections / Divisions with open/available positions
        $sections = Division::with(['positions' => function ($query) {
            $query->orderBy('name');
        }])->get()->each(function ($sec) {
            $sec->name = $sec->division_title ?? $sec->attributes['name'] ?? 'Division #' . $sec->id;
        });

        // Load Rank Sets / Insignia Catalogues with their child ranks/insignia
        if (class_exists(InsigniaCatalogue::class) && Schema::hasTable('insignia_catalogues')) {
            $rankSets = InsigniaCatalogue::with(['insignia' => function ($query) {
                $query->orderBy('insignia_sort_order', 'desc');
            }])->get()->map(function ($cat) {
                $cat->name = $cat->catalogue_title ?? $cat->name;
                $cat->ranks = $cat->insignia->map(function ($ins) {
                    $ins->name = $ins->insignia_title ?? $ins->name;
                    return $ins;
                });
                return $cat;
            });
        } elseif (Schema::hasTable('rank_sets')) {
            $rankSets = RankSet::all()->map(function ($set) {
                $ranks = Insignia::where('insignia_catalogue_id', $set->id)
                    ->orderBy('insignia_sort_order', 'desc')
                    ->get()
                    ->map(function ($ins) {
                        $ins->name = $ins->insignia_title ?? $ins->name;
                        return $ins;
                    });
                $set->setRelation('ranks', $ranks);
                return $set;
            });
        } else {
            $rankSets = collect();
        }

        return view('admin.candidatepetition', compact(
            'systemPage',
            'applications',
            'activeCharacters',
            'sections',
            'rankSets',
            'reviewMode'
        ));
    }

    /**
     * Approve a pending candidate petition, create the Persona, migrate service records
     * and custom metadata attributes, and notify the applicant.
     */
    public function approve(Request $request, $id)
    {
        $this->authoriseAccess('applications_approve');

        $validated = $request->validate([
            'position_id' => 'required|integer',
            'rank_id'     => 'required|integer',
        ]);

        $petition = CandidatePetition::with(['answers', 'user'])->findOrFail($id);

        if (strtolower($petition->petition_status) !== 'pending') {
            return response()->json([
                'status'  => 'error',
                'success' => false,
                'message' => lexicon('msg_Applications_NotPending', 'This application is no longer pending review.'),
            ], 422);
        }

        $metadataById = Metadata::all()->keyBy('id');

        DB::transaction(function () use ($petition, $validated, $metadataById) {
            $personaData = [
                'user_id' => $petition->user_id,
            ];

            if (Schema::hasColumn('personae', 'persona_name')) {
                $personaData['persona_name'] = $petition->proposed_persona_name;
            }
            if (Schema::hasColumn('personae', 'name')) {
                $personaData['name'] = $petition->proposed_persona_name;
            }

            if (Schema::hasColumn('personae', 'persona_status')) {
                $personaData['persona_status'] = 'active';
            }
            if (Schema::hasColumn('personae', 'status')) {
                $personaData['status'] = 'active';
            }

            if (Schema::hasColumn('personae', 'assignment_id')) {
                $personaData['assignment_id'] = $validated['position_id'];
            }
            if (Schema::hasColumn('personae', 'position_id')) {
                $personaData['position_id'] = $validated['position_id'];
            }

            if (Schema::hasColumn('personae', 'insignia_id')) {
                $personaData['insignia_id'] = $validated['rank_id'];
            }
            if (Schema::hasColumn('personae', 'rank_id')) {
                $personaData['rank_id'] = $validated['rank_id'];
            }

            $persona = Persona::create($personaData);

            // Migrate submitted petition answers into PersonaMetadata and ChronologicalRegister
            foreach ($petition->answers as $entry) {
                $meta = $metadataById->get($entry->metadata_id);
                $mode = $meta?->metadata_mode ?? $meta?->type ?? 'text';
                $keyName = $meta?->metadata_title ?? $meta?->name ?? ('field_' . $entry->metadata_id);

                if ($mode === 'service_record' && ! empty($entry->petition_answer)) {
                    $records = json_decode($entry->petition_answer, true);
                    if (is_array($records)) {
                        foreach ($records as $record) {
                            $desc = trim($record['description'] ?? '');
                            if ($desc === '') {
                                continue;
                            }
                            $era = trim($record['era'] ?? '');
                            $chronicleText = $era !== '' ? "[{$era}] {$desc}" : $desc;
                            $eventType = in_array($record['event_type'] ?? '', ChronologicalRegister::allEventTypes(), true)
                                ? $record['event_type']
                                : 'background';

                            ChronologicalRegister::create([
                                'user_id'             => $petition->user_id,
                                'persona_id'          => $persona->id,
                                'register_event_mode' => $eventType,
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

            // Log commissioning entry in ChronologicalRegister
            $positionName = Position::where('id', $validated['position_id'])->value('name') ?? 'Assigned Position';
            $rankName = Insignia::where('id', $validated['rank_id'])->value('insignia_title') ?? 'Assigned Rank';

            ChronologicalRegister::create([
                'user_id'             => $petition->user_id,
                'persona_id'          => $persona->id,
                'register_event_mode' => 'assignment',
                'chronicle_text'      => "Application approved. Integrated into active roster as {$rankName}, {$positionName}.",
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
                "Your character application for **{$petition->proposed_persona_name}** has been approved and integrated into the active roster!",
                'Application Approved: ' . $petition->proposed_persona_name
            );
        }

        return response()->json([
            'status'  => 'success',
            'success' => true,
            'message' => lexicon('msg_Applications_ApprovedSuccess', 'Application approved and character integrated into the active roster!'),
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
                'status'  => 'error',
                'success' => false,
                'message' => lexicon('msg_Applications_NotPending', 'This application is no longer pending review.'),
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

                if ($rejectionProtocol === 'hard_purge' && ! $hasActivePersonae && $applicant->role !== 'webmaster') {
                    $applicant->delete();
                } else {
                    $applicant->setAttr('app_status', 'rejected');

                    if (class_exists(CorrespondenceThread::class)) {
                        CorrespondenceThread::createSystemMessage(
                            auth()->user(),
                            [$applicant],
                            "Your character application for **{$petition->proposed_persona_name}** was not approved at this time.",
                            'Application Status Update: ' . $petition->proposed_persona_name
                        );
                    }
                }
            }
        });

        return response()->json([
            'status'  => 'success',
            'success' => true,
            'message' => lexicon('msg_Applications_RejectedSuccess', 'Application has been rejected.'),
        ]);
    }

    /**
     * Reassign an active character's position (assignment) and rank (insignia),
     * logging changes to the ChronologicalRegister.
     */
    public function reassign(Request $request, $id)
    {
        $this->authoriseAccess('manage_applications');

        $validated = $request->validate([
            'position_id' => 'required|integer',
            'rank_id'     => 'required|integer',
        ]);

        $persona = Persona::findOrFail($id);

        $oldPosId = $persona->assignment_id ?? $persona->attributes['position_id'] ?? null;
        $oldRankId = $persona->insignia_id ?? $persona->attributes['rank_id'] ?? null;

        $updateData = [];
        if (Schema::hasColumn('personae', 'assignment_id')) {
            $updateData['assignment_id'] = $validated['position_id'];
        }
        if (Schema::hasColumn('personae', 'position_id')) {
            $updateData['position_id'] = $validated['position_id'];
        }
        if (Schema::hasColumn('personae', 'insignia_id')) {
            $updateData['insignia_id'] = $validated['rank_id'];
        }
        if (Schema::hasColumn('personae', 'rank_id')) {
            $updateData['rank_id'] = $validated['rank_id'];
        }

        $persona->update($updateData);

        if ((int) $oldRankId !== (int) $validated['rank_id']) {
            $newRankName = Insignia::where('id', $validated['rank_id'])->value('insignia_title') ?? 'New Rank';
            ChronologicalRegister::create([
                'user_id'             => $persona->user_id,
                'persona_id'          => $persona->id,
                'register_event_mode' => 'promotion',
                'chronicle_text'      => "Rank updated to {$newRankName}.",
                'authorising_user_id' => auth()->id(),
                'scope_target'        => 'IC',
            ]);
        }

        if ((int) $oldPosId !== (int) $validated['position_id']) {
            $newPosName = Position::where('id', $validated['position_id'])->value('name') ?? 'New Position';
            ChronologicalRegister::create([
                'user_id'             => $persona->user_id,
                'persona_id'          => $persona->id,
                'register_event_mode' => 'transfer',
                'chronicle_text'      => "Assigned to position: {$newPosName}.",
                'authorising_user_id' => auth()->id(),
                'scope_target'        => 'IC',
            ]);
        }

        Cache::forget('widget_roster_sections');
        Cache::forget('widget_manifest_sections');

        return response()->json([
            'status'  => 'success',
            'success' => true,
            'message' => lexicon('msg_Applications_ReassignedSuccess', 'Character assignment updated successfully.'),
        ]);
    }
}
