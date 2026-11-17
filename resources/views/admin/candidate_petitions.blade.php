{{-- resources/views/admin/candidate_petitions.blade.php --}}
@extends('layouts.app')

@section('title', lexicon('title_Admin_CandidatePetitions', 'Candidate Petitions & Council Evaluations'))

@section('content')
<x-grid-wrapper :book="$systemBook">
    <x-slot:content>
        <div class="flex flex-col gap-md"
             x-data="candidatePetitionManager({
                 rejectConfirm: '{{ addslashes(lexicon('confirm_Petitions_Reject', 'Are you sure you want to reject this candidate petition?')) }}',
                 confirmRejectBtn: '{{ addslashes(lexicon('btn_Petitions_ConfirmReject', 'Yes, Reject')) }}',
                 rejectFailed: '{{ addslashes(lexicon('msg_Petitions_RejectFailed', 'Failed to reject candidate petition.')) }}'
             })"
             @ajax-success.window="refreshDOM()">

            {{-- Page Header --}}
            <x-page-header :title="lexicon('title_Admin_CandidatePetitions', 'Candidate Petitions & Council Evaluations')"
                           :subtitle="lexicon('desc_Admin_CandidatePetitions', 'Review incoming candidate petitions, record council evaluations, and approve or reassign personae.')"
                           icon="bi-file-earmark-person">
                <x-slot:actions>
                    <x-button type="button"
                              variant="secondary"
                              size="sm"
                              icon="bi-arrow-clockwise"
                              :tooltip="lexicon('tooltip_Petitions_Refresh', 'Refresh candidate petitions list')"
                              @click="refreshDOM()">
                        {{ lexicon('btn_common_Refresh', 'Refresh') }}
                    </x-button>
                </x-slot:actions>
            </x-page-header>

            {{-- Filter Toolbar & Fuse.js Search Bar --}}
            <x-tab type="panel">
                <div class="flex flex-row flex-wrap justify-between items-center gap-md">
                    <div class="flex flex-row flex-wrap items-center gap-xs">
                        <x-button type="button"
                                  size="sm"
                                  ::variant="activeTab === 'all' ? 'primary' : 'secondary'"
                                  :tooltip="lexicon('tooltip_Petitions_FilterAll', 'Show all candidate petitions')"
                                  @click="activeTab = 'all'">
                            {{ lexicon('filter_Petitions_All', 'All Petitions') }}
                        </x-button>

                        <x-button type="button"
                                  size="sm"
                                  ::variant="activeTab === 'pending' ? 'primary' : 'secondary'"
                                  :tooltip="lexicon('tooltip_Petitions_FilterPending', 'Show petitions awaiting review')"
                                  @click="activeTab = 'pending'">
                            {{ lexicon('filter_Petitions_Pending', 'Pending Review') }}
                        </x-button>

                        <x-button type="button"
                                  size="sm"
                                  ::variant="activeTab === 'approved' ? 'primary' : 'secondary'"
                                  :tooltip="lexicon('tooltip_Petitions_FilterApproved', 'Show approved petitions')"
                                  @click="activeTab = 'approved'">
                            {{ lexicon('filter_Petitions_Approved', 'Approved') }}
                        </x-button>

                        <x-button type="button"
                                  size="sm"
                                  ::variant="activeTab === 'rejected' ? 'primary' : 'secondary'"
                                  :tooltip="lexicon('tooltip_Petitions_FilterRejected', 'Show rejected petitions')"
                                  @click="activeTab = 'rejected'">
                            {{ lexicon('filter_Petitions_Rejected', 'Rejected') }}
                        </x-button>
                    </div>

                    <div class="w-full md-w-64">
                        <x-metadata type="search"
                                    name="petition_search"
                                    :bare="true"
                                    xModel="searchQuery"
                                    :placeholder="lexicon('placeholder_Petitions_Search', 'Search persona or applicant...')"
                                    :tooltip="lexicon('tooltip_Petitions_Search', 'Filter candidate petitions by persona or applicant name')" />
                    </div>
                </div>
            </x-tab>

            {{-- Candidate Petitions Table --}}
            <div x-ref="petitionsTableContent">
                <x-table type="admin"
                         :headers="[
                             lexicon('col_Petitions_ID', 'Petition ID'),
                             lexicon('col_Petitions_PersonaName', 'Proposed Persona'),
                             lexicon('col_Petitions_Applicant', 'Applicant'),
                             lexicon('col_Petitions_Status', 'Petition Status'),
                             lexicon('col_Petitions_Evaluations', 'Council Evaluations'),
                             lexicon('col_Petitions_LodgedAt', 'Lodged At'),
                             lexicon('col_common_Actions', 'Actions')
                         ]"
                         :emptyText="lexicon('msg_Petitions_Empty', 'No candidate petitions found matching the current filter.')">
                    @foreach($petitions ?? [] as $petition)
                        @php
                            $petitionId = $petition->id;
                            $personaId = $petition->persona_id;
                            $personaName = $petition->persona?->persona_name
                                ?? $petition->proposed_persona_name
                                ?? lexicon('label_common_NotSpecified', 'Not Specified');
                            $applicantName = $petition->user?->name ?? lexicon('label_common_Guest', 'Applicant');
                            $petitionStatus = strtolower($petition->petition_status ?? 'pending');
                            $statusVariant = match($petitionStatus) {
                                'approved' => 'success',
                                'rejected' => 'danger',
                                default    => 'warning',
                            };
                            $lodgedFormatted = $petition->lodged_at?->format('Y-m-d H:i')
                                ?? $petition->created_at?->format('Y-m-d H:i')
                                ?? '—';
                            $mappedAnswers = collect($petition->entries ?? [])->map(fn($entry) => [
                                'label'  => $entry->formField?->metadata_title ?? ('Field #' . $entry->metadata_id),
                                'answer' => $entry->petition_answer ?? '—',
                            ])->values()->all();
                        @endphp
                        <tr id="petition-row-{{ $petitionId }}"
                            class="border-b-table-row hover-bg-panel-hover transition-colors"
                            data-search-item
                            data-petition-item
                            data-id="{{ $petitionId }}"
                            data-proposed_persona_name="{{ $personaName }}"
                            data-persona_name="{{ $personaName }}"
                            data-applicant="{{ $applicantName }}"
                            data-petition_status="{{ $petitionStatus }}"
                            x-show="(activeTab === 'all' || activeTab === '{{ $petitionStatus }}') && isItemVisible({{ $petitionId }})">
                            <td class="p-4 font-mono text-xs text-muted">#{{ $petitionId }}</td>
                            <td class="p-4 font-bold text-accent">{{ $personaName }}</td>
                            <td class="p-4 text-p">{{ $applicantName }}</td>
                            <td class="p-4">
                                <x-badge type="pill" :variant="$statusVariant">
                                    {{ $petitionStatus }}
                                </x-badge>
                            </td>
                            <td class="p-4">
                                <x-badge type="pill" variant="info">
                                    {{ $petition->evaluations_count ?? ($petition->relationLoaded('evaluations') ? $petition->evaluations->count() : 0) }}
                                    {{ lexicon('label_Petitions_VotesRecorded', 'Evaluations') }}
                                </x-badge>
                            </td>
                            <td class="p-4 text-xs text-muted">{{ $lodgedFormatted }}</td>
                            <td class="p-4 text-right">
                                <div class="flex flex-row flex-wrap justify-end items-center gap-xs">
                                    <x-button type="button"
                                              variant="primary"
                                              size="sm"
                                              icon="bi-eye"
                                              :tooltip="lexicon('tooltip_Petitions_Review', 'Review petition entries and council evaluations')"
                                              @click="openModal('edit', {{ Js::from([
                                                  'id' => $petitionId,
                                                  'petitionId' => $petitionId,
                                                  'personaId' => $personaId,
                                                  'personaName' => $personaName,
                                                  'applicantName' => $applicantName,
                                                  'submittedAt' => $lodgedFormatted,
                                                  'answers' => $mappedAnswers,
                                              ]) }}, 'review')">
                                        {{ lexicon('btn_Petitions_Review', 'Review') }}
                                    </x-button>

                                    @if($personaId)
                                        <x-button type="button"
                                                  variant="secondary"
                                                  size="sm"
                                                  icon="bi-arrow-left-right"
                                                  :tooltip="lexicon('tooltip_Petitions_Reassign', 'Reassign persona rank insignia or duty assignment')"
                                                  @click="openModal('edit', {{ Js::from([
                                                      'id' => $personaId,
                                                      'personaId' => $personaId,
                                                      'personaName' => $personaName,
                                                      'insignia_id' => $petition->persona?->insignia_id ?? '',
                                                      'assignment_id' => $petition->persona?->assignment_id ?? '',
                                                  ]) }}, 'reassign')">
                                            {{ lexicon('btn_Petitions_Reassign', 'Reassign') }}
                                        </x-button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </x-table>
            </div>

            {{-- MODAL 1: REVIEW & APPROVE / REJECT CANDIDATE PETITION --}}
            <x-modal show="modals.review.open" onClose="closeModal('review')" maxWidth="4xl">
                <x-slot:top>
                    <div class="flex flex-col gap-xs">
                        <span class="text-lg font-bold text-h">{{ lexicon('modal_Petitions_ReviewTitle', 'Review Candidate Petition') }}</span>
                        <span class="text-xs text-muted"
                              x-text="modals.review.personaName ? ('#' + modals.review.petitionId + ' — ' + modals.review.personaName + ' (' + modals.review.applicantName + ')') : ''"></span>
                    </div>
                </x-slot:top>

                <x-meta type="modal"
                        xAction="modals.review.action"
                        xMethod="modals.review.method"
                        :loadingTitle="lexicon('msg_Petitions_Approving', 'Approving Candidate Petition...')">

                    {{-- Submitted Petition Answers --}}
                    <div class="flex flex-col gap-sm">
                        <span class="text-xs font-bold text-input-muted uppercase text-wider">
                            {{ lexicon('heading_Petitions_SubmittedEntries', 'Submitted Petition Entries') }}
                        </span>

                        <div class="flex flex-col gap-sm max-h-96 overflow-y-auto pr-1">
                            <template x-for="(entry, idx) in (modals.review.answers || [])" :key="idx">
                                <x-tab type="sub">
                                    <span class="text-xs font-bold text-accent uppercase text-wider"
                                          x-text="entry.label || ('Field #' + (idx + 1))"></span>
                                    <div class="text-sm text-p whitespace-pre-line"
                                         x-text="entry.answer || '—'"></div>
                                </x-tab>
                            </template>

                            <div x-show="!modals.review.answers || modals.review.answers.length === 0"
                                 class="p-4 bg-panel-sub border-panel border-rounded text-sm text-muted font-italic">
                                {{ lexicon('msg_Petitions_NoEntries', 'No petition entries recorded for this submission.') }}
                            </div>
                        </div>
                    </div>

                    {{-- Initial Insignia & Assignment Selection on Approval --}}
                    <div class="grid grid-cols-1 md-grid-cols-2 gap-md border-t-panel pt-4">
                        <x-metadata type="select"
                                    name="insignia_id"
                                    :label="lexicon('label_Demographics_Rank', 'Initial Rank Insignia')"
                                    :tooltip="lexicon('tooltip_Petitions_SelectInsignia', 'Assign an initial rank insignia upon approval')">
                            <option value="">{{ lexicon('label_Demographics_Unranked', 'Default / Unranked') }}</option>
                            @foreach($insignias ?? [] as $insignia)
                                <option value="{{ $insignia->id }}">{{ $insignia->insignia_title }}</option>
                            @endforeach
                        </x-metadata>

                        <x-metadata type="select"
                                    name="assignment_id"
                                    :label="lexicon('label_Demographics_Position', 'Initial Duty Assignment')"
                                    :tooltip="lexicon('tooltip_Petitions_SelectAssignment', 'Assign an initial duty position upon approval')">
                            <option value="">{{ lexicon('label_Demographics_Unassigned', 'Default / Unassigned') }}</option>
                            @foreach($assignments ?? [] as $assignment)
                                <option value="{{ $assignment->id }}">{{ $assignment->assignment_title }}</option>
                            @endforeach
                        </x-metadata>
                    </div>

                    {{-- Council Evaluation Commentary --}}
                    <x-metadata type="textarea"
                                name="evaluation_commentary"
                                :label="lexicon('label_Petitions_CouncilNotes', 'Council Evaluation & Decision Notes')"
                                :placeholder="lexicon('placeholder_Petitions_CouncilNotes', 'Enter evaluation commentary or reason for approval/rejection...')"
                                :tooltip="lexicon('tooltip_Petitions_CouncilNotes', 'Recorded in the chronological register and council evaluation log')" />

                    <x-slot:footer>
                        <x-button type="button"
                                  variant="secondary"
                                  :tooltip="lexicon('tooltip_common_Cancel', 'Cancel and close modal')"
                                  @click="closeModal('review')">
                            {{ lexicon('btn_common_Cancel', 'Cancel') }}
                        </x-button>

                        <x-button type="button"
                                  variant="danger"
                                  icon="bi-x-circle"
                                  :tooltip="lexicon('tooltip_Petitions_Reject', 'Reject this candidate petition')"
                                  @click="rejectPetition(modals.review.petitionId)">
                            {{ lexicon('btn_Petitions_Reject', 'Reject Petition') }}
                        </x-button>

                        <x-button type="submit"
                                  variant="success"
                                  icon="bi-check-circle"
                                  :tooltip="lexicon('tooltip_Petitions_Approve', 'Approve petition and activate persona')">
                            {{ lexicon('btn_Petitions_Approve', 'Approve Petition') }}
                        </x-button>
                    </x-slot:footer>
                </x-meta>
            </x-modal>

            {{-- MODAL 2: REASSIGN PERSONA INSIGNIA & DUTY ASSIGNMENT --}}
            <x-modal show="modals.reassign.open" onClose="closeModal('reassign')" maxWidth="max-w-lg">
                <x-slot:top>
                    <div class="flex flex-col gap-xs">
                        <span class="text-lg font-bold text-h">{{ lexicon('modal_Petitions_ReassignTitle', 'Reassign Persona') }}</span>
                        <span class="text-xs text-muted" x-text="modals.reassign.personaName"></span>
                    </div>
                </x-slot:top>

                <x-meta type="modal"
                        xAction="modals.reassign.action"
                        xMethod="modals.reassign.method"
                        :loadingTitle="lexicon('msg_Petitions_Reassigning', 'Updating Persona Assignment...')">

                    <x-metadata type="select"
                                name="insignia_id"
                                xModel="modals.reassign.insignia_id"
                                :label="lexicon('label_Demographics_Rank', 'Rank Insignia')"
                                :tooltip="lexicon('tooltip_Petitions_ReassignRank', 'Select new rank insignia for this persona')">
                        <option value="">{{ lexicon('label_Demographics_Unranked', 'Unranked') }}</option>
                        @foreach($insignias ?? [] as $insignia)
                            <option value="{{ $insignia->id }}">{{ $insignia->insignia_title }}</option>
                        @endforeach
                    </x-metadata>

                    <x-metadata type="select"
                                name="assignment_id"
                                xModel="modals.reassign.assignment_id"
                                :label="lexicon('label_Demographics_Position', 'Duty Assignment')"
                                :tooltip="lexicon('tooltip_Petitions_ReassignPosition', 'Select new duty assignment for this persona')">
                        <option value="">{{ lexicon('label_Demographics_Unassigned', 'Unassigned') }}</option>
                        @foreach($assignments ?? [] as $assignment)
                            <option value="{{ $assignment->id }}">{{ $assignment->assignment_title }}</option>
                        @endforeach
                    </x-metadata>

                    <x-slot:footer>
                        <x-button type="button"
                                  variant="secondary"
                                  :tooltip="lexicon('tooltip_common_Cancel', 'Cancel and close modal')"
                                  @click="closeModal('reassign')">
                            {{ lexicon('btn_common_Cancel', 'Cancel') }}
                        </x-button>

                        <x-button type="submit"
                                  variant="primary"
                                  icon="bi-save"
                                  :tooltip="lexicon('tooltip_Petitions_SaveReassignment', 'Save persona reassignment')">
                            {{ lexicon('btn_Petitions_SaveReassignment', 'Save Reassignment') }}
                        </x-button>
                    </x-slot:footer>
                </x-meta>
            </x-modal>

        </div>
    </x-slot:content>
</x-grid-wrapper>
@endsection