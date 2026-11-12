<x-app-layout>
    <div class="p-6 flex flex-col gap-md"
         x-data="candidatePetitionManager({
             routes: {
                 index: '{{ route('admin.candidate_petitions.index') }}',
                 store: '{{ route('admin.candidate_petitions.store') }}',
                 baseUrl: '{{ url('/admin/candidate-petitions') }}',
                 evaluationsUrl: '{{ url('/admin/council-evaluations') }}'
             },
             initialPetitions: {{ Js::from($petitions ?? []) }},
             insignias: {{ Js::from($insignias ?? []) }},
             assignments: {{ Js::from($assignments ?? []) }},
             personas: {{ Js::from($personas ?? []) }}
         })">

        {{-- Page Header & Filter Toolbar --}}
        <div class="bg-panel border-panel rounded-panel shadow-panel p-panel flex flex-col gap-md">
            <div class="flex flex-row flex-wrap justify-between items-center gap-md border-b-panel pb-4">
                <div class="flex flex-col gap-xs">
                    <h1 class="text-2xl font-bold text-accent flex flex-row items-center gap-sm">
                        <i class="bi bi-file-earmark-person"></i>
                        <span>{{ lexicon('title_Admin_CandidatePetitions', 'Candidate Petitions & Council Evaluations') }}</span>
                    </h1>
                    <p class="text-sm text-input-muted">
                        {{ lexicon('desc_Admin_CandidatePetitions', 'Review incoming candidate petitions, record council evaluations, and approve or reassign personas.') }}
                    </p>
                </div>

                <div class="flex flex-row flex-wrap items-center gap-sm">
                    <x-button type="button"
                              size="sm"
                              class="bg-secondary text-btn-secondary border-secondary hover-bg-border"
                              @click="refreshPetitions()"
                              x-tooltip="'{{ addslashes(lexicon('tooltip_Petitions_Refresh', 'Refresh candidate petitions list')) }}'"
                              aria-label="{{ lexicon('tooltip_Petitions_Refresh', 'Refresh candidate petitions list') }}">
                        <i class="bi bi-arrow-clockwise mr-1"></i>
                        {{ lexicon('btn_common_Refresh', 'Refresh') }}
                    </x-button>
                </div>
            </div>

            {{-- Status Tabs & Search Bar --}}
            <div class="flex flex-row flex-wrap justify-between items-center gap-md">
                <div class="flex flex-row flex-wrap gap-xs">
                    <x-button type="button"
                              size="sm"
                              ::class="statusFilter === 'all' ? 'bg-primary text-input border-transparent shadow-btn' : 'bg-secondary text-btn-secondary border-secondary hover-bg-border'"
                              @click="statusFilter = 'all'">
                        {{ lexicon('filter_Petitions_All', 'All Petitions') }}
                    </x-button>
                    <x-button type="button"
                              size="sm"
                              ::class="statusFilter === 'pending' ? 'bg-primary text-input border-transparent shadow-btn' : 'bg-secondary text-btn-secondary border-secondary hover-bg-border'"
                              @click="statusFilter = 'pending'">
                        {{ lexicon('filter_Petitions_Pending', 'Pending Review') }}
                    </x-button>
                    <x-button type="button"
                              size="sm"
                              ::class="statusFilter === 'approved' ? 'bg-primary text-input border-transparent shadow-btn' : 'bg-secondary text-btn-secondary border-secondary hover-bg-border'"
                              @click="statusFilter = 'approved'">
                        {{ lexicon('filter_Petitions_Approved', 'Approved') }}
                    </x-button>
                    <x-button type="button"
                              size="sm"
                              ::class="statusFilter === 'rejected' ? 'bg-primary text-input border-transparent shadow-btn' : 'bg-secondary text-btn-secondary border-secondary hover-bg-border'"
                              @click="statusFilter = 'rejected'">
                        {{ lexicon('filter_Petitions_Rejected', 'Rejected') }}
                    </x-button>
                </div>

                <div class="w-full md-w-64">
                    <input type="text"
                           x-model="searchQuery"
                           placeholder="{{ lexicon('placeholder_Petitions_Search', 'Search persona or applicant...') }}"
                           class="w-full bg-input text-input border-input rounded-input p-2 text-sm focus-ring-primary" />
                </div>
            </div>
        </div>

        {{-- Candidate Petitions Table --}}
        <div class="bg-panel border-panel rounded-panel shadow-panel overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-panel-header text-h border-b-panel text-xs uppercase tracking-wider font-bold">
                            <th class="p-4">{{ lexicon('col_Petitions_ID', 'Petition ID') }}</th>
                            <th class="p-4">{{ lexicon('col_Petitions_PersonaName', 'Proposed Persona') }}</th>
                            <th class="p-4">{{ lexicon('col_Petitions_Applicant', 'Applicant') }}</th>
                            <th class="p-4">{{ lexicon('col_Petitions_Status', 'Status') }}</th>
                            <th class="p-4">{{ lexicon('col_Petitions_Evaluations', 'Council Evaluations') }}</th>
                            <th class="p-4">{{ lexicon('col_Petitions_Submitted', 'Submitted') }}</th>
                            <th class="p-4 text-right">{{ lexicon('col_common_Actions', 'Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody id="candidate-petitions-tbody" class="divide-y border-panel text-sm text-p">
                        @forelse($petitions ?? [] as $petition)
                            @php
                                $petitionId =$petition->id;
                                $personaId =$petition->persona_id;
                                $personaName = $petition->persona?->persona_name ?? $petition->proposed_persona_name ?? lexicon('label_common_NotSpecified', 'Not Specified');
                                $status = strtolower($petition->petition_status ?? $petition->status ?? 'pending');
                            @endphp
                            <tr id="petition-row-{{ $petitionId }}"
                                x-show="(statusFilter === 'all' || statusFilter === '{{ $status }}') && (!searchQuery \vert{}\vert{} '{{ strtolower(addslashes($personaName)) }}'.includes(searchQuery.toLowerCase()))"
                                class="hover-bg-panel-sub transition-colors">
                                <td class="p-4 font-mono text-xs text-input-muted">#{{ $petitionId }}</td>
                                <td class="p-4 font-bold text-accent">{{ $personaName }}</td>
                                <td class="p-4">{{ $petition->user?->name ?? $petition->applicant_name ?? lexicon('label_common_Guest', 'Applicant') }}</td>
                                <td class="p-4">
                                    <span class="px-2 py-1 rounded-panel text-xs font-bold uppercase
                                        {{ $status === 'approved' ? 'bg-success text-white' : ($status === 'rejected' ? 'bg-danger text-white' : 'bg-warning text-dark') }}">
                                        {{ $status }}
                                    </span>
                                </td>
                                <td class="p-4">
                                    <span class="text-xs font-bold">
                                        {{ $petition->evaluations_count ?? ($petition->relationLoaded('evaluations') ?$petition->evaluations->count() : 0) }}
                                        {{ lexicon('label_Petitions_VotesRecorded', 'Evaluations') }}
                                    </span>
                                </td>
                                <td class="p-4 text-xs text-input-muted">
                                    {{ $petition->created_at?->format('Y-m-d H:i') ?? '—' }}
                                </td>
                                <td class="p-4 text-right">
                                    <div class="flex flex-row justify-end items-center gap-xs">
                                        <x-button type="button"
                                                  size="sm"
                                                  class="bg-primary text-input border-transparent shadow-btn hover-brightness-110"
                                                  @click="openModal('edit', {{ Js::from([
                                                      'petitionId' => $petitionId,
                                                      'personaId' => $personaId,
                                                      'personaName' => $personaName,
                                                      'status' => $status,
                                                      'entries' => $petition->entries ?? $petition->answers ?? [],
                                                      'evaluations' => $petition->evaluations ?? []
                                                  ]) }}, 'review')"
                                                  x-tooltip="'{{ addslashes(lexicon('tooltip_Petitions_Review', 'Review petition entries and council evaluations')) }}'"
                                                  aria-label="{{ lexicon('tooltip_Petitions_Review', 'Review petition entries and council evaluations') }}">
                                            <i class="bi bi-eye mr-1"></i>
                                            {{ lexicon('btn_Petitions_Review', 'Review') }}
                                        </x-button>

                                        <x-button type="button"
                                                  size="sm"
                                                  class="bg-secondary text-btn-secondary border-secondary hover-bg-border"
                                                  @click="openModal('edit', {{ Js::from([
                                                      'petitionId' => $petitionId,
                                                      'personaId' => $personaId,
                                                      'personaName' => $personaName,
                                                      'insignia_id' => $petition->persona?->insignia_id ?? null,
                                                      'assignment_id' => $petition->persona?->assignment_id ?? null
                                                  ]) }}, 'reassign')"
                                                  x-tooltip="'{{ addslashes(lexicon('tooltip_Petitions_Reassign', 'Reassign persona linkage or duty assignment')) }}'"
                                                  aria-label="{{ lexicon('tooltip_Petitions_Reassign', 'Reassign persona linkage or duty assignment') }}">
                                            <i class="bi bi-arrow-left-right mr-1"></i>
                                            {{ lexicon('btn_Petitions_Reassign', 'Reassign') }}
                                        </x-button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="p-8 text-center text-input-muted">
                                    {{ lexicon('msg_Petitions_Empty', 'No candidate petitions found matching the current filter.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Unified Review & Reassign Modal --}}
        <div x-cloak
             x-show="isModalOpen"
             class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-60 p-4"
             @keydown.escape.window="closeModal()">
            <div class="bg-panel border-panel rounded-panel shadow-panel w-full max-w-4xl max-h-screen overflow-y-auto flex flex-col"
                 @click.outside="closeModal()">

                {{-- Modal Header --}}
                <div class="bg-panel-header text-h border-b-panel p-panel-header flex flex-row justify-between items-center">
                    <div class="flex flex-col gap-xs">
                        <span class="text-xl font-bold text-accent"
                              x-text="modalView === 'reassign'
                                  ? '{{ addslashes(lexicon('modal_Petitions_ReassignTitle', 'Reassign Candidate Petition')) }}'
                                  : '{{ addslashes(lexicon('modal_Petitions_ReviewTitle', 'Review Candidate Petition')) }}'"></span>
                        <span class="text-xs text-input-muted"
                              x-text="activePetition.personaName ? ('#' + activePetition.petitionId + ' — ' + activePetition.personaName) : ''"></span>
                    </div>
                    <x-button type="button"
                              size="sm"
                              class="bg-secondary text-btn-secondary border-secondary"
                              @click="closeModal()"
                              aria-label="{{ lexicon('btn_common_Close', 'Close') }}">
                        <i class="bi bi-x-lg"></i>
                    </x-button>
                </div>

                {{-- Modal Body: Review Mode --}}
                <div x-show="modalView === 'review'" class="p-panel flex flex-col gap-md">
                    {{-- Submitted Petition Entries --}}
                    <div class="flex flex-col gap-sm">
                        <h2 class="text-sm font-bold text-input-muted uppercase tracking-wider">
                            {{ lexicon('heading_Petitions_SubmittedEntries', 'Submitted Petition Entries') }}
                        </h2>
                        <div class="flex flex-col gap-sm max-h-96 overflow-y-auto pr-1">
                            <template x-for="(entry, idx) in (activePetition.entries || [])" :key="idx">
                                <div class="bg-panel-sub border-panel rounded-panel p-3 flex flex-col gap-xs">
                                    <span class="text-xs font-bold text-accent uppercase tracking-wider"
                                          x-text="entry.metadata?.metadata_title || entry.metadata?.metadata_label || entry.field_label || ('Entry #' + (idx + 1))"></span>
                                    <div class="text-sm text-p whitespace-pre-line"
                                         x-text="entry.petition_answer || '—'"></div>
                                </div>
                            </template>
                            <div x-show="!activePetition.entries || activePetition.entries.length === 0"
                                 class="p-4 bg-panel-sub border-panel rounded-panel text-sm text-input-muted">
                                {{ lexicon('msg_Petitions_NoEntries', 'No petition entries recorded for this submission.') }}
                            </div>
                        </div>
                    </div>

                    {{-- Council Evaluations & Decision Notes --}}
                    <div class="flex flex-col gap-sm border-t-panel pt-4">
                        <label class="text-xs font-bold text-input-muted uppercase tracking-wider">
                            {{ lexicon('label_Petitions_CouncilNotes', 'Council Evaluation & Decision Notes') }}
                        </label>
                        <textarea x-model="evaluationNotes"
                                  rows="3"
                                  placeholder="{{ lexicon('placeholder_Petitions_CouncilNotes', 'Enter evaluation commentary or reason for approval/rejection...') }}"
                                  class="w-full bg-input text-input border-input rounded-input p-3 text-sm focus-ring-primary"></textarea>
                    </div>

                    {{-- Review Action Buttons --}}
                    <div class="flex flex-row flex-wrap justify-end items-center gap-sm border-t-panel pt-4">
                        <x-button type="button"
                                  size="sm"
                                  class="bg-secondary text-btn-secondary border-secondary hover-bg-border"
                                  @click="closeModal()">
                            {{ lexicon('btn_common_Cancel', 'Cancel') }}
                        </x-button>

                        <x-button type="button"
                                  size="sm"
                                  class="bg-danger text-white border-transparent shadow-btn hover-brightness-110"
                                  @click="rejectPetition(activePetition.petitionId)">
                            <i class="bi bi-x-circle mr-1"></i>
                            {{ lexicon('btn_Petitions_Reject', 'Reject Petition') }}
                        </x-button>

                        <x-button type="button"
                                  size="sm"
                                  class="bg-success text-white border-transparent shadow-btn hover-brightness-110"
                                  @click="approvePetition(activePetition.petitionId)">
                            <i class="bi bi-check-circle mr-1"></i>
                            {{ lexicon('btn_Petitions_Approve', 'Approve Petition') }}
                        </x-button>
                    </div>
                </div>

                {{-- Modal Body: Reassign Mode --}}
                <div x-show="modalView === 'reassign'" class="p-panel flex flex-col gap-md">
                    <div class="grid grid-cols-1 md-grid-cols-2 gap-md">
                        <div class="flex flex-col gap-xs">
                            <label class="text-xs font-bold text-input-muted uppercase tracking-wider">
                                {{ lexicon('label_Petitions_TargetPersona', 'Linked Persona') }}
                            </label>
                            <select x-model="activePetition.personaId"
                                    class="w-full bg-input text-input border-input rounded-input p-2 text-sm">
                                <option value="">{{ lexicon('option_common_SelectPersona', '— Select Persona —') }}</option>
                                <template x-for="persona in personas" :key="persona.id">
                                    <option :value="persona.id" x-text="persona.persona_name"></option>
                                </template>
                            </select>
                        </div>

                        <div class="flex flex-col gap-xs">
                            <label class="text-xs font-bold text-input-muted uppercase tracking-wider">
                                {{ lexicon('label_Petitions_PersonaNameOverride', 'Persona Name') }}
                            </label>
                            <input type="text"
                                   x-model="activePetition.personaName"
                                   class="w-full bg-input text-input border-input rounded-input p-2 text-sm" />
                        </div>

                        <div class="flex flex-col gap-xs">
                            <label class="text-xs font-bold text-input-muted uppercase tracking-wider">
                                {{ lexicon('label_Demographics_Rank', 'Insignia') }}
                            </label>
                            <select x-model="activePetition.insignia_id"
                                    class="w-full bg-input text-input border-input rounded-input p-2 text-sm">
                                <option value="">{{ lexicon('label_Demographics_Unranked', 'Unranked') }}</option>
                                <template x-for="insignia in insignias" :key="insignia.id">
                                    <option :value="insignia.id" x-text="insignia.insignia_title"></option>
                                </template>
                            </select>
                        </div>

                        <div class="flex flex-col gap-xs">
                            <label class="text-xs font-bold text-input-muted uppercase tracking-wider">
                                {{ lexicon('label_Demographics_Position', 'Assignment') }}
                            </label>
                            <select x-model="activePetition.assignment_id"
                                    class="w-full bg-input text-input border-input rounded-input p-2 text-sm">
                                <option value="">{{ lexicon('label_Demographics_Unassigned', 'Unassigned') }}</option>
                                <template x-for="assignment in assignments" :key="assignment.id">
                                    <option :value="assignment.id" x-text="assignment.assignment_title"></option>
                                </template>
                            </select>
                        </div>
                    </div>

                    <div class="flex flex-row justify-end items-center gap-sm border-t-panel pt-4">
                        <x-button type="button"
                                  size="sm"
                                  class="bg-secondary text-btn-secondary border-secondary hover-bg-border"
                                  @click="closeModal()">
                            {{ lexicon('btn_common_Cancel', 'Cancel') }}
                        </x-button>

                        <x-button type="button"
                                  size="sm"
                                  class="bg-primary text-input border-transparent shadow-btn hover-brightness-110"
                                  @click="submitPetition(activePetition.petitionId)">
                            <i class="bi bi-save mr-1"></i>
                            {{ lexicon('btn_Petitions_SaveReassignment', 'Save Reassignment') }}
                        </x-button>
                    </div>
                </div>

            </div>
        </div>

    </div>
</x-app-layout>