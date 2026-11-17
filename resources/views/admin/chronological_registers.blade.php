{{-- resources/views/admin/chronological_registers.blade.php --}}
@extends('layouts.app')

@php
    $recordsList = $chronologicalRegisters ?? $serviceRecords ?? collect();
    $awardsList = $distinctions ?? $awards ?? collect();
    $personaeList = $personae ?? $characters ?? collect();
    $usersList = $users ?? collect();
    $insigniasList = $insignias ?? $ranks ?? collect();
    $assignmentsList = $assignments ?? $positions ?? collect();
    $eventGroups = \App\Models\ChronologicalRegister::eventTypeGroups();
@endphp

@section('title', lexicon('admin_label_ServiceRecords', 'Service Records Hub'))

@section('content')
<x-grid-wrapper :book="$systemBook">
    <x-slot:content>
        <div class="flex flex-col gap-lg"
             x-data="chronologicalRegisterManager(@js($awardsList))"
             @ajax-success.window="refreshDOM()"
             x-ref="chronologicalRegistersWrapper">

            <script type="application/json" x-ref="serviceRecordsSearchData" class="hidden">
                @json($recordsList)
            </script>

            {{-- Page Header & Quick Issue Actions --}}
            <x-page-header :title="lexicon('admin_label_ServiceRecords', 'Service Records Hub')"
                           :subtitle="lexicon('desc_admin_ServiceRecords', 'Issue distinctions, promotions, transfers, duty status updates, and biographical service entries.')"
                           icon="bi-journal-bookmark-fill">
                <x-slot:actions>
                    <x-metadata type="search"
                                name="register_search"
                                :bare="true"
                                xModel="searchQuery"
                                :placeholder="lexicon('placeholder_ServiceRecords_Search', 'Search recipient, event, or citation...')"
                                :tooltip="lexicon('tooltip_ServiceRecords_Search', 'Filter chronological registers by recipient, event mode, or citation')" />

                    @can('manage_awards')
                        <x-button type="button"
                                  variant="primary"
                                  size="sm"
                                  icon="bi-award"
                                  :tooltip="lexicon('tooltip_ServiceRecords_IssueAward', 'Issue an IC or OOC distinction badge')"
                                  @click="openModal('award', 'create')">
                            + {{ lexicon('btn_ServiceRecords_IssueAward', 'Issue Distinction') }}
                        </x-button>
                    @endcan

                    @can('manage_character_ranks')
                        <x-button type="button"
                                  variant="secondary"
                                  size="sm"
                                  icon="bi-chevron-double-up"
                                  :tooltip="lexicon('tooltip_ServiceRecords_Promotion', 'Log a rank promotion or demotion')"
                                  @click="openModal('promotion', 'create')">
                            + {{ lexicon('btn_ServiceRecords_Promotion', 'Promotion') }}
                        </x-button>
                    @endcan

                    @can('manage_character_positions')
                        <x-button type="button"
                                  variant="secondary"
                                  size="sm"
                                  icon="bi-arrow-left-right"
                                  :tooltip="lexicon('tooltip_ServiceRecords_Transfer', 'Transfer a persona to a new duty assignment')"
                                  @click="openModal('transfer', 'create')">
                            + {{ lexicon('btn_ServiceRecords_Transfer', 'Transfer') }}
                        </x-button>
                    @endcan

                    @can('manage_character_status')
                        <x-button type="button"
                                  variant="secondary"
                                  size="sm"
                                  icon="bi-person-check"
                                  :tooltip="lexicon('tooltip_ServiceRecords_StatusChange', 'Change persona duty status (Active, LOA, Memorial)')"
                                  @click="openModal('status_change', 'create')">
                            + {{ lexicon('btn_ServiceRecords_StatusChange', 'Duty Status') }}
                        </x-button>
                    @endcan

                    <x-button type="button"
                              variant="secondary"
                              size="sm"
                              icon="bi-journal-plus"
                              :tooltip="lexicon('tooltip_ServiceRecords_ManualEntry', 'Add a manual career, medical, or biographical entry')"
                              @click="openModal('manual_entry', 'create')">
                        + {{ lexicon('btn_ServiceRecords_ManualEntry', 'Log Entry') }}
                    </x-button>
                </x-slot:actions>
            </x-page-header>

            {{-- Chronological Registers Table --}}
            <x-tab type="accordion"
                   :title="lexicon('heading_ServiceRecords_Ledger', 'Chronological Service Register')"
                   icon="bi-clock-history"
                   :open="true">
                <x-table type="admin"
                         :headers="[
                             lexicon('col_ServiceRecords_Recipient', 'Recipient'),
                             lexicon('col_ServiceRecords_Scope', 'Scope'),
                             lexicon('col_ServiceRecords_EventMode', 'Event Mode'),
                             lexicon('col_ServiceRecords_Distinction', 'Distinction / Details'),
                             lexicon('col_ServiceRecords_Chronicle', 'Chronicle Citation'),
                             lexicon('col_ServiceRecords_Date', 'Logged At'),
                             lexicon('col_common_Actions', 'Actions')
                         ]"
                         :emptyText="lexicon('msg_ServiceRecords_Empty', 'No chronological register entries recorded yet.')">
                    @foreach($recordsList as $record)
                        @php
                            $recipientName = $record->persona?->persona_name
                                ?? $record->user?->name
                                ?? lexicon('label_common_Unknown', 'Unknown');
                            $eventMode = $record->register_event_mode ?? 'manual_entry';
                            $scope = strtoupper($record->scope_target ?? 'IC');
                            $scopeVariant = match($scope) {
                                'IC'   => 'primary',
                                'OOC'  => 'warning',
                                'BOTH' => 'success',
                                default => 'muted',
                            };
                            $awardTitle = $record->distinction?->distinction_title ?? '';
                        @endphp
                        <tr class="border-b-table-row hover-bg-panel-hover transition-colors"
                            data-search-item
                            data-service-record-item
                            data-id="{{ $record->id }}"
                            data-recipient="{{ $recipientName }}"
                            data-register_event_mode="{{ $eventMode }}"
                            data-award_name="{{ $awardTitle }}"
                            data-chronicle_text="{{ $record->chronicle_text }}"
                            x-show="isItemVisible({{ $record->id }})">
                            <td class="p-4 font-bold text-h">
                                <span>{{ $recipientName }}</span>
                                @if($record->persona && $record->user)
                                    <span class="block text-xs text-muted">({{ $record->user->name }})</span>
                                @endif
                            </td>
                            <td class="p-4">
                                <x-badge type="pill" :variant="$scopeVariant">{{ $scope }}</x-badge>
                            </td>
                            <td class="p-4">
                                <x-badge type="pill" variant="info">{{ str_replace('_', ' ', $eventMode) }}</x-badge>
                            </td>
                            <td class="p-4">
                                @if($record->distinction)
                                    <div class="flex flex-row items-center gap-sm">
                                        <x-badge type="distinction"
                                                 :award="$record->distinction"
                                                 :skin="$activeSkin->directory_name ?? 'default'"
                                                 :tooltip="$record->distinction->distinction_title" />
                                        <span class="text-xs font-bold text-p">{{ $record->distinction->distinction_title }}</span>
                                    </div>
                                @else
                                    <span class="text-xs text-muted">—</span>
                                @endif
                            </td>
                            <td class="p-4 text-sm text-p">{{ $record->chronicle_text }}</td>
                            <td class="p-4 text-xs text-muted whitespace-nowrap">
                                {{ $record->created_at?->format('Y-m-d H:i') ?? '—' }}
                            </td>
                            <td class="p-4 text-right">
                                <div class="flex flex-row flex-wrap justify-end items-center gap-xs">
                                    <x-button type="button"
                                              variant="ghost"
                                              size="sm"
                                              icon="bi-pencil"
                                              :tooltip="lexicon('tooltip_ServiceRecords_Edit', 'Edit this register entry')"
                                              @click="openModal('{{ $eventMode }}', 'edit', {{ Js::from([
                                                  'id' => $record->id,
                                                  'persona_id' => $record->persona_id,
                                                  'user_id' => $record->user_id,
                                                  'distinction_id' => $record->distinction_id,
                                                  'scope_target' => $record->scope_target ?? 'IC',
                                                  'register_event_mode' => $eventMode,
                                                  'chronicle_text' => $record->chronicle_text,
                                              ]) }})">
                                        {{ lexicon('btn_common_Edit', 'Edit') }}
                                    </x-button>

                                    <x-button type="button"
                                              variant="danger"
                                              size="sm"
                                              icon="bi-trash"
                                              :tooltip="lexicon('tooltip_ServiceRecords_Delete', 'Remove this register entry')"
                                              x-data="deleteAction('/admin/chronological-registers/{{ $record->id }}/remove', 'POST', '{{ addslashes($recipientName . ' - ' . $eventMode) }}')"
                                              @click="confirmDelete()">
                                        {{ lexicon('btn_common_Delete', 'Delete') }}
                                    </x-button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </x-table>
            </x-tab>

            {{-- UNIFIED MODAL: ISSUE / EDIT CHRONOLOGICAL REGISTER ENTRY --}}
            <x-modal show="modals.register.open" onClose="closeModal()" maxWidth="max-w-lg">
                <x-slot:top>
                    <span x-text="modals.register.mode === 'edit'
                        ? '{{ addslashes(lexicon('modal_ServiceRecords_EditTitle', 'Edit Service Register Entry')) }}'
                        : '{{ addslashes(lexicon('modal_ServiceRecords_CreateTitle', 'Issue Service Register Entry')) }}'"></span>
                </x-slot:top>

                <x-meta type="modal" xAction="modals.register.action" xMethod="modals.register.method">
                    {{-- Mode Switcher Bar (Visible on Create) --}}
                    <div x-show="modals.register.mode === 'create'" class="flex flex-row flex-wrap gap-xs pb-2 border-b-panel">
                        <x-button type="button"
                                  size="sm"
                                  ::variant="actionTab === 'award' ? 'primary' : 'secondary'"
                                  @click="actionTab = 'award'; modals.register.register_event_mode = 'award'">
                            {{ lexicon('tab_ServiceRecords_Award', 'Distinction') }}
                        </x-button>
                        <x-button type="button"
                                  size="sm"
                                  ::variant="actionTab === 'promotion' ? 'primary' : 'secondary'"
                                  @click="actionTab = 'promotion'; modals.register.register_event_mode = 'promotion'">
                            {{ lexicon('tab_ServiceRecords_Promotion', 'Promotion') }}
                        </x-button>
                        <x-button type="button"
                                  size="sm"
                                  ::variant="actionTab === 'transfer' ? 'primary' : 'secondary'"
                                  @click="actionTab = 'transfer'; modals.register.register_event_mode = 'transfer'">
                            {{ lexicon('tab_ServiceRecords_Transfer', 'Transfer') }}
                        </x-button>
                        <x-button type="button"
                                  size="sm"
                                  ::variant="actionTab === 'status_change' ? 'primary' : 'secondary'"
                                  @click="actionTab = 'status_change'; modals.register.register_event_mode = 'status_change'">
                            {{ lexicon('tab_ServiceRecords_Status', 'Status') }}
                        </x-button>
                        <x-button type="button"
                                  size="sm"
                                  ::variant="actionTab === 'manual_entry' ? 'primary' : 'secondary'"
                                  @click="actionTab = 'manual_entry'; modals.register.register_event_mode = 'manual_entry'">
                            {{ lexicon('tab_ServiceRecords_Manual', 'Manual / Bio') }}
                        </x-button>
                    </div>

                    {{-- Recipient Scope Selector (Character IC vs Writer OOC) --}}
                    <div x-show="actionTab === 'award' || actionTab === 'manual_entry' || modals.register.mode === 'edit'"
                         class="grid grid-cols-1 md-grid-cols-2 gap-md">
                        <x-metadata type="select"
                                    name="recipient_type"
                                    xModel="recipientType"
                                    :label="lexicon('label_ServiceRecords_RecipientType', 'Recipient Type')"
                                    :tooltip="lexicon('tooltip_ServiceRecords_RecipientType', 'Target an in-character Persona or an out-of-character Player account')">
                            <option value="character">{{ lexicon('option_ServiceRecords_PersonaIC', 'Persona (In-Character)') }}</option>
                            <option value="user">{{ lexicon('option_ServiceRecords_UserOOC', 'Player Account (OOC)') }}</option>
                        </x-metadata>

                        <x-metadata type="select"
                                    name="scope_target"
                                    xModel="modals.register.scope_target"
                                    :label="lexicon('label_ServiceRecords_ScopeTarget', 'Register Scope')"
                                    :tooltip="lexicon('tooltip_ServiceRecords_ScopeTarget', 'Visibility scope (IC, OOC, or BOTH)')">
                            <option value="IC">IC (In-Character)</option>
                            <option value="OOC">OOC (Out-of-Character)</option>
                            <option value="BOTH">BOTH</option>
                        </x-metadata>
                    </div>

                    {{-- Target Persona Select --}}
                    <div x-show="recipientType === 'character' || ['promotion', 'transfer', 'status_change'].includes(actionTab)">
                        <x-metadata type="select"
                                    name="persona_id"
                                    xModel="modals.register.persona_id"
                                    :label="lexicon('label_ServiceRecords_SelectPersona', 'Target Persona')"
                                    :tooltip="lexicon('tooltip_ServiceRecords_SelectPersona', 'Select the persona receiving this entry')">
                            <option value="">{{ lexicon('option_common_SelectPersona', '— Select Persona —') }}</option>
                            @foreach($personaeList as $pItem)
                                <option value="{{ $pItem->id }}">{{ $pItem->persona_name }}</option>
                            @endforeach
                        </x-metadata>
                    </div>

                    {{-- Target User Select (When OOC User is chosen) --}}
                    <div x-show="recipientType === 'user' && !['promotion', 'transfer', 'status_change'].includes(actionTab)">
                        <x-metadata type="select"
                                    name="user_id"
                                    xModel="modals.register.user_id"
                                    :label="lexicon('label_ServiceRecords_SelectUser', 'Target Player Account')"
                                    :tooltip="lexicon('tooltip_ServiceRecords_SelectUser', 'Select the player receiving this OOC entry')">
                            <option value="">{{ lexicon('option_common_SelectUser', '— Select Player —') }}</option>
                            @foreach($usersList as $uItem)
                                <option value="{{ $uItem->id }}">{{ $uItem->name }}</option>
                            @endforeach
                        </x-metadata>
                    </div>

                    {{-- Distinction Select (Award Tab) --}}
                    <div x-show="actionTab === 'award'">
                        <x-metadata type="select"
                                    name="distinction_id"
                                    xModel="modals.register.distinction_id"
                                    :label="lexicon('label_ServiceRecords_SelectDistinction', 'Distinction / Award')"
                                    :tooltip="lexicon('tooltip_ServiceRecords_SelectDistinction', 'Select the distinction medal or ribbon to issue')">
                            <option value="">{{ lexicon('option_ServiceRecords_SelectAward', '— Select Distinction —') }}</option>
                            <template x-for="award in filteredAwards" :key="award.id">
                                <option :value="award.id" x-text="award.distinction_title + ' (' + (award.scope_target || 'BOTH') + ')'"></option>
                            </template>
                        </x-metadata>
                    </div>

                    {{-- Promotion Rank Select (Promotion Tab) --}}
                    <div x-show="actionTab === 'promotion' && modals.register.mode === 'create'">
                        <x-metadata type="select"
                                    name="insignia_id"
                                    :label="lexicon('label_ServiceRecords_NewInsignia', 'New Rank Insignia')"
                                    :tooltip="lexicon('tooltip_ServiceRecords_NewInsignia', 'Select the new rank insignia for this persona')">
                            <option value="">{{ lexicon('option_common_SelectRank', '— Select Rank Insignia —') }}</option>
                            @foreach($insigniasList as $insItem)
                                <option value="{{ $insItem->id }}">{{ $insItem->insignia_title }} ({{ $insItem->abbreviated_title }})</option>
                            @endforeach
                        </x-metadata>
                    </div>

                    {{-- Transfer Assignment Select (Transfer Tab) --}}
                    <div x-show="actionTab === 'transfer' && modals.register.mode === 'create'">
                        <x-metadata type="select"
                                    name="assignment_id"
                                    :label="lexicon('label_ServiceRecords_NewAssignment', 'New Duty Assignment')"
                                    :tooltip="lexicon('tooltip_ServiceRecords_NewAssignment', 'Select the new duty assignment for this persona')">
                            <option value="">{{ lexicon('option_common_SelectAssignment', '— Select Duty Assignment —') }}</option>
                            @foreach($assignmentsList as $assItem)
                                <option value="{{ $assItem->id }}">{{ $assItem->assignment_title }}</option>
                            @endforeach
                        </x-metadata>
                    </div>

                    {{-- Persona Status Select (Status Change Tab) --}}
                    <div x-show="actionTab === 'status_change' && modals.register.mode === 'create'">
                        <x-metadata type="select"
                                    name="persona_status"
                                    :label="lexicon('label_ServiceRecords_NewStatus', 'New Duty Status')"
                                    :tooltip="lexicon('tooltip_ServiceRecords_NewStatus', 'Select updated duty status for this persona')">
                            <option value="active">{{ lexicon('status_Persona_Active', 'Active Duty') }}</option>
                            <option value="loa">{{ lexicon('status_Persona_Loa', 'Leave of Absence (LOA)') }}</option>
                            <option value="eloa">{{ lexicon('status_Persona_Eloa', 'Extended Leave (ELOA)') }}</option>
                            <option value="retired">{{ lexicon('status_Persona_Retired', 'Retired / Inactive') }}</option>
                            <option value="honoured_dead">{{ lexicon('status_Persona_HonouredDead', 'Honoured Dead (KIA)') }}</option>
                            <option value="memorial">{{ lexicon('status_Persona_Memorial', 'Memorial') }}</option>
                        </x-metadata>
                    </div>

                    {{-- Full 20-Mode Event Type Selector (Manual / Bio / Edit Mode) --}}
                    <div x-show="actionTab === 'manual_entry' || modals.register.mode === 'edit'">
                        <x-metadata type="select"
                                    name="register_event_mode"
                                    xModel="modals.register.register_event_mode"
                                    :label="lexicon('label_ServiceRecords_EventMode', 'Register Event Mode')"
                                    :tooltip="lexicon('tooltip_ServiceRecords_EventMode', 'Select the classification for this register entry')">
                            @foreach($eventGroups as $groupKey => $groupData)
                                <optgroup label="{{ $groupData['label'] }}">
                                    @foreach($groupData['types'] as $modeKey => $modeLabel)
                                        <option value="{{ $modeKey }}">{{ $modeLabel }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </x-metadata>
                    </div>

                    {{-- Chronicle Citation Text --}}
                    <x-metadata type="textarea"
                                name="chronicle_text"
                                xModel="modals.register.chronicle_text"
                                :label="lexicon('label_ServiceRecords_ChronicleText', 'Official Citation / Chronicle Text')"
                                :placeholder="lexicon('placeholder_ServiceRecords_ChronicleText', 'Enter the official service record citation or biographical note...')"
                                :tooltip="lexicon('tooltip_ServiceRecords_ChronicleText', 'Displayed on the persona or player chronological service record')"
                                :required="true" />

                    <x-slot:footer>
                        <x-button type="button"
                                  variant="secondary"
                                  :tooltip="lexicon('tooltip_common_Cancel', 'Cancel and close modal')"
                                  @click="closeModal()">
                            {{ lexicon('btn_common_Cancel', 'Cancel') }}
                        </x-button>

                        <x-button type="submit"
                                  variant="primary"
                                  icon="bi-save"
                                  :tooltip="lexicon('tooltip_ServiceRecords_Save', 'Save chronological register entry')">
                            {{ lexicon('btn_common_Save', 'Save Entry') }}
                        </x-button>
                    </x-slot:footer>
                </x-meta>
            </x-modal>

        </div>
    </x-slot:content>
</x-grid-wrapper>
@endsection