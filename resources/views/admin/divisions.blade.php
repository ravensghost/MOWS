{{-- resources/views/admin/divisions.blade.php --}}
@extends('layouts.app')

@php
    $divisionsList = $divisions ?? $sections ?? collect();
    $rankCatalogues = $insigniaCatalogues ?? $rankSets ?? collect();
    $divisionCatalogues = $divisionCatalogues ?? $catalogues ?? collect();
@endphp

@section('title', lexicon('admin_label_SectionsPositions', 'Divisions & Duty Assignments'))

@section('content')
<x-grid-wrapper :book="$systemBook">
    <x-slot:content>
        <div x-data="divisionManager({
                 aiEndpoint: '{{ route('admin.divisions.generate_description') }}',
                 emptyNameMsg: '{{ addslashes(lexicon('msg_divisions_EnterTitleFirst', 'Please enter a Division Title first.')) }}'
             })"
             @ajax-success.window="refreshDOM()"
             x-ref="divisionsContainer"
             class="flex flex-col gap-lg">

            {{-- Page Header --}}
            <x-page-header :title="lexicon('admin_label_SectionsPositions', 'Divisions & Duty Assignments')"
                           :subtitle="lexicon('desc_admin_Divisions', 'Manage organisational divisions, colour bindings, linked rank catalogues, and duty assignment slots.')"
                           icon="bi-diagram-3">
                <x-slot:actions>
                    <x-metadata type="search"
                                name="division_search"
                                :bare="true"
                                xModel="searchQuery"
                                :placeholder="lexicon('placeholder_divisions_Search', 'Search divisions or assignments...')"
                                :tooltip="lexicon('tooltip_divisions_Search', 'Filter divisions and assignments by title or description')" />

                    @can('manage_sections')
                        <x-button tag="a"
                                  href="{{ route('admin.divisions.export') }}"
                                  variant="secondary"
                                  size="sm"
                                  icon="bi-download"
                                  :tooltip="lexicon('tooltip_divisions_Export', 'Download divisions and assignments JSON manifest')">
                            {{ lexicon('btn_common_Export', 'Export') }}
                        </x-button>

                        <x-button tag="a"
                                  href="{{ route('admin.divisions.template') }}"
                                  variant="secondary"
                                  size="sm"
                                  icon="bi-file-earmark-code"
                                  :tooltip="lexicon('tooltip_divisions_Template', 'Download blank divisions JSON template')">
                            {{ lexicon('btn_common_DownloadTemplate', 'Template') }}
                        </x-button>

                        <x-button type="button"
                                  variant="secondary"
                                  size="sm"
                                  icon="bi-upload"
                                  :tooltip="lexicon('tooltip_divisions_Import', 'Import divisions and assignments from JSON manifest')"
                                  @click="openModal('create', {}, 'import')">
                            {{ lexicon('btn_common_Import', 'Import') }}
                        </x-button>

                        <x-button type="button"
                                  variant="secondary"
                                  size="sm"
                                  icon="bi-person-workspace"
                                  :tooltip="lexicon('tooltip_assignments_Create', 'Create a new duty assignment')"
                                  @click="openModal('create', {}, 'assignment')">
                            + {{ lexicon('btn_assignments_New', 'New Assignment') }}
                        </x-button>

                        <x-button type="button"
                                  variant="primary"
                                  size="sm"
                                  icon="bi-plus-lg"
                                  :tooltip="lexicon('tooltip_divisions_Create', 'Create a new organisational division')"
                                  @click="openModal('create', {}, 'division')">
                            + {{ lexicon('btn_divisions_New', 'New Division') }}
                        </x-button>
                    @endcan
                </x-slot:actions>
            </x-page-header>

            {{-- Divisions & Assignments List --}}
            <div x-ref="sectionsContainer" class="flex flex-col gap-lg">
                @forelse($divisionsList as $division)
                    @php
                        $assignTitles = $division->assignments?->pluck('assignment_title')->values()->all() ?? [];
                    @endphp
                    <div data-search-item
                         data-division-item
                         data-id="{{ $division->id }}"
                         data-division_title="{{ $division->division_title }}"
                         data-division_description="{{ $division->division_description }}"
                         x-show="matchesSearch('{{ addslashes($division->division_title) }}', '{{ addslashes($division->division_description ?? '') }}', {{ Js::from($assignTitles) }})">

                        <x-tab type="accordion"
                               :title="$division->division_title"
                               icon="bi-building"
                               :open="true">
                            <x-slot:top>
                                <div class="flex flex-row flex-wrap items-center gap-xs" @click.stop>
                                    @if($division->insigniaCatalogue)
                                        <x-badge type="pill" variant="info">
                                            {{ $division->insigniaCatalogue->catalogue_title }}
                                        </x-badge>
                                    @endif

                                    <x-badge type="pill" :variant="$division->is_visible ? 'success' : 'muted'">
                                        {{ $division->is_visible ? lexicon('status_common_Visible', 'Visible') : lexicon('status_common_Hidden', 'Hidden') }}
                                    </x-badge>

                                    @can('manage_sections')
                                        <x-button type="button"
                                                  variant="secondary"
                                                  size="sm"
                                                  icon="bi-plus-lg"
                                                  :tooltip="lexicon('tooltip_assignments_AddInDivision', 'Add a duty assignment to this division')"
                                                  @click="openModal('create', {
                                                      division_id: {{ $division->id }},
                                                      division_title: '{{ addslashes($division->division_title) }}',
                                                      division_base_colour: '{{ $division->division_base_colour ?? '#374151FF' }}'
                                                  }, 'assignment')">
                                            + {{ lexicon('btn_assignments_Add', 'Add Assignment') }}
                                        </x-button>

                                        <x-button type="button"
                                                  variant="ghost"
                                                  size="sm"
                                                  icon="bi-pencil"
                                                  :tooltip="lexicon('tooltip_divisions_Edit', 'Edit this division')"
                                                  @click="openModal('edit', {{ Js::from([
                                                      'id' => $division->id,
                                                      'division_title' => $division->division_title,
                                                      'division_description' => $division->division_description ?? '',
                                                      'division_base_colour' => $division->division_base_colour ?? '#374151FF',
                                                      'division_sort_order' => $division->division_sort_order ?? 0,
                                                      'division_catalogue_id' => $division->division_catalogue_id ?? '',
                                                      'insignia_catalogue_id' => $division->insignia_catalogue_id ?? '',
                                                      'is_visible' => (bool) $division->is_visible,
                                                  ]) }}, 'division')">
                                            {{ lexicon('btn_common_Edit', 'Edit') }}
                                        </x-button>

                                        <x-button type="button"
                                                  variant="danger"
                                                  size="sm"
                                                  icon="bi-trash"
                                                  :tooltip="lexicon('tooltip_divisions_Delete', 'Delete this division and its assignments')"
                                                  x-data="deleteAction('{{ route('admin.divisions.destroy', $division) }}', 'DELETE', '{{ addslashes($division->division_title) }}')"
                                                  @click="confirmDelete()">
                                            {{ lexicon('btn_common_Delete', 'Delete') }}
                                        </x-button>
                                    @endcan
                                </div>
                            </x-slot:top>

                            @if($division->division_description)
                                <p class="text-sm text-muted m-0">{{ $division->division_description }}</p>
                            @endif

                            <x-table type="admin"
                                     :headers="[
                                         lexicon('col_assignments_Title', 'Assignment Title'),
                                         lexicon('col_assignments_Description', 'Description'),
                                         lexicon('col_assignments_Capacity', 'Max Occupants'),
                                         lexicon('col_assignments_Recruitment', 'Recruitment'),
                                         lexicon('col_common_Actions', 'Actions')
                                     ]"
                                     :emptyText="lexicon('msg_assignments_NoneInDivision', 'No duty assignments configured for this division.')">
                                @foreach($division->assignments ?? [] as $assignment)
                                    <tr class="border-b-table-row hover-bg-panel-hover transition-colors"
                                        x-show="isItemVisible('{{ addslashes($assignment->assignment_title) }}', '{{ addslashes($assignment->assignment_description ?? '') }}', '{{ addslashes($division->division_title) }}')">
                                        <td class="p-4 font-bold text-h">{{ $assignment->assignment_title }}</td>
                                        <td class="p-4 text-sm text-muted">{{ $assignment->assignment_description ?: '—' }}</td>
                                        <td class="p-4 font-mono text-xs text-p">
                                            {{ $assignment->personae?->count() ?? 0 }} / {{ $assignment->maximum_occupants }}
                                        </td>
                                        <td class="p-4">
                                            <x-badge type="pill" :variant="$assignment->is_recruitment_open ? 'success' : 'warning'">
                                                {{ $assignment->is_recruitment_open
                                                    ? lexicon('status_assignments_Open', 'Open')
                                                    : lexicon('status_assignments_Closed', 'Closed') }}
                                            </x-badge>
                                        </td>
                                        <td class="p-4 text-right">
                                            @can('manage_sections')
                                                <div class="flex flex-row flex-wrap justify-end items-center gap-xs">
                                                    <x-button type="button"
                                                              variant="ghost"
                                                              size="sm"
                                                              icon="bi-pencil"
                                                              :tooltip="lexicon('tooltip_assignments_Edit', 'Edit this duty assignment')"
                                                              @click="openModal('edit', {{ Js::from([
                                                                  'id' => $assignment->id,
                                                                  'division_id' => $assignment->division_id,
                                                                  'assignment_title' => $assignment->assignment_title,
                                                                  'assignment_description' => $assignment->assignment_description ?? '',
                                                                  'maximum_occupants' => $assignment->maximum_occupants ?? 1,
                                                                  'is_recruitment_open' => (bool) $assignment->is_recruitment_open,
                                                                  'is_visible' => (bool) $assignment->is_visible,
                                                              ]) }}, 'assignment')">
                                                        {{ lexicon('btn_common_Edit', 'Edit') }}
                                                    </x-button>

                                                    <x-button type="button"
                                                              variant="danger"
                                                              size="sm"
                                                              icon="bi-trash"
                                                              :tooltip="lexicon('tooltip_assignments_Delete', 'Delete this duty assignment')"
                                                              x-data="deleteAction('{{ route('admin.assignments.destroy', $assignment) }}', 'DELETE', '{{ addslashes($assignment->assignment_title) }}')"
                                                              @click="confirmDelete()">
                                                        {{ lexicon('btn_common_Delete', 'Delete') }}
                                                    </x-button>
                                                </div>
                                            @endcan
                                        </td>
                                    </tr>
                                @endforeach
                            </x-table>
                        </x-tab>
                    </div>
                @empty
                    <x-tab type="panel">
                        <p class="text-center text-muted font-italic m-0">
                            {{ lexicon('msg_divisions_Empty', 'No organisational divisions have been created yet.') }}
                        </p>
                    </x-tab>
                @endforelse
            </div>

            {{-- MODAL 1: CREATE / EDIT DIVISION --}}
            <x-modal show="modals.division.open" onClose="closeModal('division')" maxWidth="max-w-lg">
                <x-slot:top>
                    <span x-text="modals.division.mode === 'edit'
                        ? '{{ addslashes(lexicon('modal_divisions_Edit', 'Edit Division')) }}'
                        : '{{ addslashes(lexicon('modal_divisions_Create', 'Create Division')) }}'"></span>
                </x-slot:top>

                <x-meta type="modal" xAction="modals.division.action" xMethod="modals.division.method">
                    <x-metadata type="text"
                                name="division_title"
                                xModel="modals.division.division_title"
                                :label="lexicon('label_divisions_Title', 'Division Title')"
                                :placeholder="lexicon('placeholder_divisions_Title', 'e.g. Command, Engineering, Operations')"
                                :tooltip="lexicon('tooltip_divisions_Title', 'Name of the organisational division')"
                                :required="true" />

                    <x-colour-picker name="division_base_colour"
                                     xModel="modals.division.division_base_colour"
                                     :label="lexicon('label_divisions_Colour', 'Division Accent Colour (8-Digit Hex #RRGGBBAA)')"
                                     :tooltip="lexicon('tooltip_divisions_Colour', 'Department colour applied to roster headers and rank insignia')" />

                    <div class="flex flex-col gap-xs">
                        <div class="flex flex-row justify-between items-center">
                            <span class="text-xs font-bold text-input-muted uppercase text-wider">
                                {{ lexicon('label_common_Description', 'Division Description') }}
                            </span>
                            <x-button type="button"
                                      variant="ghost"
                                      size="sm"
                                      icon="bi-stars"
                                      :tooltip="lexicon('tooltip_divisions_AiGenerate', 'Generate lore-aware division description using AI')"
                                      @click="generateAiDescription()">
                                <span x-text="isGeneratingAi
                                    ? '{{ addslashes(lexicon('msg_common_Generating', 'Generating...')) }}'
                                    : '{{ addslashes(lexicon('btn_divisions_AiGenerate', 'AI Generate')) }}'"></span>
                            </x-button>
                        </div>

                        <x-metadata type="textarea"
                                    name="division_description"
                                    xModel="modals.division.division_description"
                                    :tooltip="lexicon('tooltip_divisions_Description', 'Describe the responsibilities of this division')" />
                    </div>

                    <div class="grid grid-cols-1 md-grid-cols-2 gap-md">
                        <x-metadata type="select"
                                    name="insignia_catalogue_id"
                                    xModel="modals.division.insignia_catalogue_id"
                                    :label="lexicon('label_divisions_RankSet', 'Linked Insignia Catalogue')"
                                    :tooltip="lexicon('tooltip_divisions_RankSet', 'Select which rank insignia set applies to this division')">
                            <option value="">{{ lexicon('option_common_None', '— None —') }}</option>
                            @foreach($rankCatalogues as $rc)
                                <option value="{{ $rc->id }}">{{ $rc->catalogue_title }}</option>
                            @endforeach
                        </x-metadata>

                        <x-metadata type="number"
                                    name="division_sort_order"
                                    xModel="modals.division.division_sort_order"
                                    :label="lexicon('label_common_SortOrder', 'Sort Order')"
                                    :tooltip="lexicon('tooltip_common_SortOrder', 'Numeric display order (lower numbers appear first)')" />
                    </div>

                    <x-metadata type="checkbox"
                                name="is_visible"
                                value="1"
                                xModel="modals.division.is_visible"
                                :label="lexicon('label_common_ShowOnSite', 'Show on Site (Visible)')"
                                :tooltip="lexicon('tooltip_divisions_Visible', 'Toggle whether this division appears on public rosters')" />

                    <x-slot:footer>
                        <x-button type="button"
                                  variant="secondary"
                                  :tooltip="lexicon('tooltip_common_Cancel', 'Cancel and close modal')"
                                  @click="closeModal('division')">
                            {{ lexicon('btn_common_Cancel', 'Cancel') }}
                        </x-button>

                        <x-button type="submit"
                                  variant="primary"
                                  icon="bi-save"
                                  :tooltip="lexicon('tooltip_common_Save', 'Save division changes')">
                            {{ lexicon('btn_common_Save', 'Save Division') }}
                        </x-button>
                    </x-slot:footer>
                </x-meta>
            </x-modal>

            {{-- MODAL 2: CREATE / EDIT DUTY ASSIGNMENT --}}
            <x-modal show="modals.assignment.open" onClose="closeModal('assignment')" maxWidth="max-w-lg">
                <x-slot:top>
                    <span x-text="modals.assignment.mode === 'edit'
                        ? '{{ addslashes(lexicon('modal_assignments_Edit', 'Edit Duty Assignment')) }}'
                        : '{{ addslashes(lexicon('modal_assignments_Create', 'Create Duty Assignment')) }}'"></span>
                </x-slot:top>

                <x-meta type="modal" xAction="modals.assignment.action" xMethod="modals.assignment.method">
                    <x-metadata type="select"
                                id="edit_assignment_division_id"
                                name="division_id"
                                xModel="modals.assignment.division_id"
                                :label="lexicon('label_assignments_Division', 'Parent Division')"
                                :tooltip="lexicon('tooltip_assignments_Division', 'Select which division this duty assignment belongs to')"
                                :required="true">
                        <option value="">{{ lexicon('option_divisions_Select', '— Select Division —') }}</option>
                        @foreach($divisionsList as $divOpt)
                            <option value="{{ $divOpt->id }}">{{ $divOpt->division_title }}</option>
                        @endforeach
                    </x-metadata>

                    <x-metadata type="text"
                                name="assignment_title"
                                xModel="modals.assignment.assignment_title"
                                :label="lexicon('label_assignments_Title', 'Assignment Title')"
                                :placeholder="lexicon('placeholder_assignments_Title', 'e.g. Chief Engineer, Commanding Officer')"
                                :tooltip="lexicon('tooltip_assignments_Title', 'Official title of this duty position')"
                                :required="true" />

                    <x-metadata type="textarea"
                                name="assignment_description"
                                xModel="modals.assignment.assignment_description"
                                :label="lexicon('label_common_Description', 'Assignment Description')"
                                :tooltip="lexicon('tooltip_assignments_Description', 'Brief summary of duties and billet expectations')" />

                    <x-metadata type="number"
                                name="maximum_occupants"
                                xModel="modals.assignment.maximum_occupants"
                                :label="lexicon('label_assignments_MaxOccupants', 'Maximum Occupants')"
                                :tooltip="lexicon('tooltip_assignments_MaxOccupants', 'Maximum number of personae who can hold this billet simultaneously')"
                                :required="true" />

                    <div class="flex flex-col gap-sm pt-2">
                        <x-metadata type="checkbox"
                                    name="is_recruitment_open"
                                    value="1"
                                    xModel="modals.assignment.is_recruitment_open"
                                    :label="lexicon('label_assignments_RecruitmentOpen', 'Open for Candidate Recruitment')"
                                    :tooltip="lexicon('tooltip_assignments_RecruitmentOpen', 'Allow applicants to petition for this assignment')" />

                        <x-metadata type="checkbox"
                                    name="is_visible"
                                    value="1"
                                    xModel="modals.assignment.is_visible"
                                    :label="lexicon('label_common_ShowOnSite', 'Show on Site (Visible)')"
                                    :tooltip="lexicon('tooltip_assignments_Visible', 'Display this assignment on the public manifest')" />
                    </div>

                    <x-slot:footer>
                        <x-button type="button"
                                  variant="secondary"
                                  :tooltip="lexicon('tooltip_common_Cancel', 'Cancel and close modal')"
                                  @click="closeModal('assignment')">
                            {{ lexicon('btn_common_Cancel', 'Cancel') }}
                        </x-button>

                        <x-button type="submit"
                                  variant="primary"
                                  icon="bi-save"
                                  :tooltip="lexicon('tooltip_common_Save', 'Save duty assignment')">
                            {{ lexicon('btn_common_Save', 'Save Assignment') }}
                        </x-button>
                    </x-slot:footer>
                </x-meta>
            </x-modal>

            {{-- MODAL 3: IMPORT DIVISIONS & ASSIGNMENTS MANIFEST --}}
            <x-modal show="modals.import.open" onClose="closeModal('import')" maxWidth="max-w-lg">
                <x-slot:top>
                    <div class="flex flex-row items-center gap-xs">
                        <i class="bi bi-upload text-accent"></i>
                        <span>{{ lexicon('modal_divisions_ImportTitle', 'Import Divisions & Assignments Manifest') }}</span>
                    </div>
                </x-slot:top>

                <div class="flex flex-col gap-md" @upload-completed.window="handleImportSuccess($event.detail)">
                    <x-metadata type="select"
                                name="import_mode"
                                xModel="importMode"
                                :label="lexicon('label_common_ImportMode', 'Import Mode')"
                                :tooltip="lexicon('tooltip_common_ImportMode', 'Choose whether to merge with or replace existing divisions')">
                        <option value="merge">{{ lexicon('label_common_MergeUpsert', 'Merge / Upsert (Preserves active records)') }}</option>
                        <option value="replace">{{ lexicon('label_common_ReplaceAll', 'Replace All (Clears existing divisions)') }}</option>
                    </x-metadata>

                    <x-dropzone :url="route('admin.divisions.import')"
                                id="divisions-import-dropzone"
                                :maxFiles="1"
                                acceptedFiles="application/json,.json,.txt"
                                :title="lexicon('label_divisions_DropzoneTitle', 'Drop divisions manifest (.json) here')" />
                </div>

                <x-slot:bottom>
                    <x-button type="button"
                              variant="secondary"
                              @click="closeModal('import')">
                        {{ lexicon('btn_common_Cancel', 'Cancel') }}
                    </x-button>
                </x-slot:bottom>
            </x-modal>

        </div>
    </x-slot:content>
</x-grid-wrapper>
@endsection