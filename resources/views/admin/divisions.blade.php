@extends('layouts.app')

@section('title', lexicon('admin_label_SectionsPositions', 'Sections & Positions'))

@section('content')
<div x-data="sectionManager({
        aiEndpoint: '{{ route('admin.sections.generate-description') }}',
        emptyNameMsg: '{{ addslashes(lexicon('msg_SectionsPositions_EnterNameFirst', 'Please enter a Section Name first.')) }}'
    })"
    @ajax-success.window="handleAjaxSuccess()">

    <x-admin-grid-wrapper :page="$systemPage">
        <x-slot:content>

            {{-- Page Header --}}
            <x-page-header :title="lexicon('admin_label_SectionsPositions', 'Sections & Positions')">
                <x-slot:actions>
                    @can('manage_sections')
                    <div class="flex flex-row flex-wrap items-center gap-sm">
                        {{-- Manage Catalogues Button --}}
                        <x-button type="button"
                            variant="secondary"
                            class="p-btn-sm text-sm flex items-center gap-xs"
                            @click="openCatalogueModal('create')"
                            aria-label="{{ lexicon('tooltip_SectionsPositions_ManageCatalogues', 'Manage division catalogues (ships, stations, or locations)') }}"
                            x-tooltip="'{{ addslashes(lexicon('tooltip_SectionsPositions_ManageCatalogues', 'Manage division catalogues (ships, stations, or locations)')) }}'">
                            <i class="bi bi-folder-check" aria-hidden="true"></i>
                            <span>{{ lexicon('btn_SectionsPositions_ManageCatalogues', 'Manage Catalogues') }}</span>
                        </x-button>

                        <x-button type="button"
                            variant="success"
                            class="p-btn-sm text-sm flex items-center gap-xs"
                            @click="openModal('create', {}, 'division')"
                            aria-label="{{ lexicon('tooltip_SectionsPositions_CreateSection', 'Create a new organisational section') }}"
                            x-tooltip="'{{ addslashes(lexicon('tooltip_SectionsPositions_CreateSection', 'Create a new organisational section')) }}'">
                            <i class="bi bi-plus-lg" aria-hidden="true"></i>
                            <span>{{ lexicon('btn_SectionsPositions_CreateSection', 'Create Section') }}</span>
                        </x-button>
                        <x-button variant="secondary"
                            tag="a"
                            href="{{ route('admin.sections.export') }}"
                            x-tooltip="'{{ addslashes(lexicon('tooltip_SectionsPositions_Export', 'Download sections and positions JSON manifest')) }}'">
                            <i class="bi bi-download"></i>
                            <span>{{ lexicon('btn_common_Export', 'Export Sections & Positions') }}</span>
                        </x-button>

                        <x-button variant="secondary"
                            tag="a"
                            href="{{ route('admin.sections.template') }}"
                            x-tooltip="'{{ addslashes(lexicon('tooltip_SectionsPositions_Template', 'Download blank JSON manifest template')) }}'">
                            <i class="bi bi-file-earmark-code"></i>
                            <span>{{ lexicon('btn_common_DownloadTemplate', 'Download Blank Template') }}</span>
                        </x-button>

                        <x-button variant="secondary"
                            @click="importModalOpen = true"
                            x-tooltip="'{{ addslashes(lexicon('tooltip_SectionsPositions_Import', 'Import sections and positions from JSON manifest')) }}'">
                            <i class="bi bi-upload"></i>
                            <span>{{ lexicon('btn_common_Import', 'Import Sections & Positions') }}</span>
                        </x-button>

                        {{-- Import Modal --}}
                        <x-modal show="importModalOpen" maxWidth="max-w-xl">
                            <x-slot:top>
                                <div class="flex flex-row items-center gap-xs">
                                    <i class="bi bi-upload text-accent"></i>
                                    <span>{{ lexicon('label_SectionsPositions_ImportModal', 'Import Sections & Positions Manifest') }}</span>
                                </div>
                            </x-slot:top>

                            <div class="flex flex-col gap-md" @upload-completed.window="handleImportSuccess($event.detail)">
                                <div class="flex flex-col gap-xs">
                                    <label class="text-sm font-bold text-h">{{ lexicon('label_common_ImportMode', 'Import Mode') }}</label>
                                    <select x-model="importMode" class="lumi-input">
                                        <option value="merge">{{ lexicon('label_common_MergeUpsert', 'Merge / Upsert (Preserves active records)') }}</option>
                                        <option value="replace">{{ lexicon('label_common_ReplaceAll', 'Replace All (Clears existing records)') }}</option>
                                    </select>
                                </div>

                                <div class="flex flex-col gap-xs">
                                    <label class="text-sm font-bold text-h">{{ lexicon('label_common_UploadJson', 'Upload JSON Manifest') }}</label>
                                    <x-dropzone :url="route('admin.sections.import')"
                                        id="sections-import-dropzone"
                                        :maxFiles="1"
                                        acceptedFiles="application/json,.json,.txt"
                                        :title="lexicon('label_SectionsPositions_DropzoneTitle', 'Drop manifest (.json) here')" />
                                </div>
                            </div>

                            <x-slot:bottom>
                                <x-button variant="secondary" @click="importModalOpen = false">
                                    {{ lexicon('btn_common_Cancel', 'Cancel') }}
                                </x-button>
                            </x-slot:bottom>
                        </x-modal>
                    </div>
                    @endcan
                </x-slot:actions>
            </x-page-header>

            {{-- Search / Filter Bar --}}
            <div class="bg-panel border border-panel rounded-panel p-panel shadow-panel mb-4">
                <x-form-input name="filter_divisions"
                    id="filter_divisions"
                    xModel="searchQuery"
                    :label="lexicon('label_SectionsPositions_FilterSections', 'Filter Sections & Positions')"
                    :placeholder="lexicon('label_SectionsPositions_FilterPlaceholder', 'Search by division title, assignment title, or summary...')"
                    :tooltip="lexicon('tooltip_SectionsPositions_FilterInput', 'Instantaneously filter divisions and assignments using fuzzy search')" />
            </div>

            {{-- Divisions Loop --}}
            <div class="flex flex-col gap-lg" id="divisions-container" x-ref="sectionsContainer">
                @forelse($sections as $sec)
                @php
                $assignmentsCollection = $sec->assignments ?? collect();
                $posSearchList = $assignmentsCollection->pluck('assignment_title')->merge($assignmentsCollection->pluck('assignment_description'))->filter()->values()->all();
                @endphp
                <div x-show="matchesSearch('{{ addslashes($sec->division_title) }}', '{{ addslashes($sec->division_description ?? '') }}', @js($posSearchList))">
                    <x-accordion-panel :title="$sec->division_title" :expanded="true" id="division-row-{{ $sec->id }}">
                        <x-slot:top>
                            @if(!$sec->is_visible)
                            <x-badge variant="warning">{{ lexicon('label_common_Hidden', 'Hidden') }}</x-badge>
                            @endif

                            @if($sec->divisionCatalogue)
                            <x-badge variant="secondary"
                                aria-label="{{ lexicon('tooltip_SectionsPositions_LinkedDivisionCatalogue', 'Assigned Division Catalogue') }}"
                                x-tooltip="'{{ addslashes(lexicon('tooltip_SectionsPositions_LinkedDivisionCatalogue', 'Assigned Division Catalogue')) }}'">
                                <i class="bi bi-folder2-open" aria-hidden="true"></i>
                                <span>{{ $sec->divisionCatalogue->catalogue_title }}</span>
                            </x-badge>
                            @endif

                            @if($sec->insigniaCatalogue)
                            <x-badge variant="info"
                                aria-label="{{ lexicon('tooltip_SectionsPositions_LinkedRankSet', 'Insignia catalogue assigned via Ranks Manager') }}"
                                x-tooltip="'{{ addslashes(lexicon('tooltip_SectionsPositions_LinkedRankSet', 'Insignia catalogue assigned via Ranks Manager')) }}'">
                                <i class="bi bi-award" aria-hidden="true"></i>
                                <span>{{ $sec->insigniaCatalogue->catalogue_title }}</span>
                            </x-badge>
                            @endif

                            @can('manage_sections')
                            <x-button type="button"
                                variant="text"
                                class="text-accent hover-text-h text-sm flex items-center gap-xs"
                                @click="openModal('create', { division_id: {{ $sec->id }}, division_title: '{{ addslashes($sec->division_title) }}', division_base_colour: '{{ $sec->division_base_colour }}' }, 'assignment')"
                                aria-label="{{ lexicon('tooltip_SectionsPositions_AddPosition', 'Add a new position to this section') }}"
                                x-tooltip="'{{ addslashes(lexicon('tooltip_SectionsPositions_AddPosition', 'Add a new position to this section')) }}'">
                                <i class="bi bi-plus-circle" aria-hidden="true"></i>
                                <span>{{ lexicon('btn_SectionsPositions_AddPosition', 'Add Position') }}</span>
                            </x-button>

                            <x-button type="button"
                                variant="text"
                                class="text-warning hover-text-h text-sm flex items-center gap-xs"
                                @click="openModal('edit', { id: {{ $sec->id }}, division_title: '{{ addslashes($sec->division_title) }}', division_base_colour: '{{ $sec->division_base_colour }}', division_description: '{{ addslashes($sec->division_description) }}', division_sort_order: {{ $sec->division_sort_order }}, is_visible: {{ $sec->is_visible ? 'true' : 'false' }}, division_catalogue_id: {{ $sec->division_catalogue_id ?? 'null' }} }, 'division')"
                                aria-label="{{ lexicon('tooltip_SectionsPositions_EditSection', 'Edit section details and colour') }}"
                                x-tooltip="'{{ addslashes(lexicon('tooltip_SectionsPositions_EditSection', 'Edit section details and colour')) }}'">
                                <i class="bi bi-pencil-square" aria-hidden="true"></i>
                                <span>{{ lexicon('btn_SectionsPositions_EditSection', 'Edit Section') }}</span>
                            </x-button>

                            <x-button type="button"
                                variant="text"
                                class="text-danger hover-text-h text-sm flex items-center gap-xs"
                                x-data="deleteAction('/admin/sections/{{ $sec->id }}', 'DELETE', '{{ addslashes($sec->division_title) }}')"
                                @click.prevent="confirmDelete()"
                                aria-label="{{ lexicon('tooltip_SectionsPositions_DeleteSection', 'Delete this section and its positions') }}"
                                x-tooltip="'{{ addslashes(lexicon('tooltip_SectionsPositions_DeleteSection', 'Delete this section and its positions')) }}'">
                                <i class="bi bi-trash" aria-hidden="true"></i>
                                <span>{{ lexicon('btn_common_Delete', 'Delete') }}</span>
                            </x-button>
                            @endcan
                        </x-slot:top>

                        @if($sec->division_description)
                        <span class="text-xs text-muted border-b border-panel pb-2 block">{{ $sec->division_description }}</span>
                        @endif

                        {{-- Assignments Grid --}}
                        @if($assignmentsCollection->count() > 0)
                        <div class="grid grid-cols-1 lg-grid-cols-3 gap-md w-full">
                            @foreach($assignmentsCollection as $pos)
                            @php
                            $assignedCount = $pos->characters ? $pos->characters->count() : 0;
                            $slotsAvailable = max(0, $pos->max_assignees - $assignedCount);
                            $isOpen = (bool) ($pos->is_open ?? true);
                            @endphp
                            <div class="bg-panel-sub border border-panel rounded-panel p-3 flex flex-row flex-wrap justify-between items-start gap-sm"
                                x-show="isItemVisible('{{ addslashes($pos->assignment_title) }}', '{{ addslashes($pos->assignment_description ?? '') }}', '{{ addslashes($sec->division_title) }}')">
                                <div class="flex flex-col gap-xs flex-grow">
                                    <div class="flex flex-row items-center gap-xs">
                                        <span class="font-bold text-p block">{{ $pos->assignment_title }}</span>
                                        @if(!$pos->is_visible)
                                        <x-badge variant="warning" class="text-xs py-0 px-1">{{ lexicon('label_common_Hidden', 'Hidden') }}</x-badge>
                                        @endif
                                    </div>
                                    @if($pos->assignment_description)
                                    <span class="text-xs text-muted block">{{ $pos->assignment_description }}</span>
                                    @endif
                                    <div class="flex flex-row flex-wrap items-center gap-xs mt-1">
                                        @if(!$isOpen)
                                        <x-badge variant="warning"
                                            aria-label="{{ lexicon('tooltip_SectionsPositions_PositionClosed', 'This position is currently closed to applications') }}"
                                            x-tooltip="'{{ addslashes(lexicon('tooltip_SectionsPositions_PositionClosed', 'This position is currently closed to applications')) }}'">
                                            <i class="bi bi-lock-fill" aria-hidden="true"></i>
                                            <span>{{ lexicon('label_SectionsPositions_Closed', 'Closed') }}</span>
                                        </x-badge>
                                        @else
                                        <x-badge :variant="$slotsAvailable > 0 ? 'success' : 'danger'"
                                            aria-label="{{ lexicon('tooltip_SectionsPositions_SlotAvailability', 'Current position slot capacity') }}"
                                            x-tooltip="'{{ addslashes(lexicon('tooltip_SectionsPositions_SlotAvailability', 'Current position slot capacity')) }}'">
                                            {{ $slotsAvailable > 0 ? $slotsAvailable . ' ' . lexicon('label_SectionsPositions_SlotsOpen', 'Slot(s) Open') : lexicon('label_SectionsPositions_SlotsFilled', 'Filled') }}
                                        </x-badge>
                                        @endif
                                        <span class="text-xs text-muted"
                                            aria-label="{{ lexicon('tooltip_SectionsPositions_AssignedVsCapacity', 'Active characters assigned versus maximum capacity') }}"
                                            x-tooltip="'{{ addslashes(lexicon('tooltip_SectionsPositions_AssignedVsCapacity', 'Active characters assigned versus maximum capacity')) }}'">
                                            ({{ $assignedCount }}/{{ $pos->max_assignees }})
                                        </span>
                                    </div>
                                </div>
                                @can('manage_sections')
                                <div class="flex flex-col gap-sm items-end text-right">
                                    <x-button type="button"
                                        variant="text"
                                        class="text-xs text-warning hover-text-h p-0 flex items-center gap-xs"
                                        @click="openModal('edit', { id: {{ $pos->id }}, division_id: '{{ $pos->division_id }}', assignment_title: '{{ addslashes($pos->assignment_title) }}', assignment_description: '{{ addslashes($pos->assignment_description) }}', max_assignees: {{ $pos->max_assignees ?? 1 }}, is_open: {{ $isOpen ? 'true' : 'false' }}, is_visible: {{ $pos->is_visible ? 'true' : 'false' }} }, 'assignment')"
                                        aria-label="{{ lexicon('tooltip_SectionsPositions_EditPosition', 'Edit this position') }}"
                                        x-tooltip="'{{ addslashes(lexicon('tooltip_SectionsPositions_EditPosition', 'Edit this position')) }}'">
                                        <i class="bi bi-pencil" aria-hidden="true"></i>
                                        <span>{{ lexicon('btn_common_Edit', 'Edit') }}</span>
                                    </x-button>
                                    <x-button type="button"
                                        variant="text"
                                        class="text-xs text-danger hover-text-h p-0 flex items-center gap-xs"
                                        x-data="deleteAction('/admin/positions/{{ $pos->id }}', 'DELETE', '{{ addslashes($pos->assignment_title) }}')"
                                        @click.prevent="confirmDelete()"
                                        aria-label="{{ lexicon('tooltip_SectionsPositions_DeletePosition', 'Delete this position') }}"
                                        x-tooltip="'{{ addslashes(lexicon('tooltip_SectionsPositions_DeletePosition', 'Delete this position')) }}'">
                                        <i class="bi bi-trash" aria-hidden="true"></i>
                                        <span>{{ lexicon('btn_common_Delete', 'Delete') }}</span>
                                    </x-button>
                                </div>
                                @endcan
                            </div>
                            @endforeach
                        </div>
                        @else
                        <span class="text-sm text-muted italic p-2 block">{{ lexicon('msg_SectionsPositions_NoPositions', 'No positions have been assigned to this section yet.') }}</span>
                        @endif
                    </x-accordion-panel>
                </div>
                @empty
                <div class="bg-panel border border-panel rounded-panel p-6 text-center text-muted italic">
                    <span class="block">{{ lexicon('msg_SectionsPositions_NoSections', 'No sections or positions have been created.') }}</span>
                </div>
                @endforelse
            </div>

            @can('manage_sections')
            {{-- MODAL 1: Create Division --}}
            <x-modal show="modal === 'section-create'" onClose="closeModal()" maxWidth="max-w-4xl">
                <x-slot:top>
                    {{ lexicon('label_SectionsPositions_CreateSection', 'Create Section') }}
                </x-slot:top>

                <form action="/admin/sections" method="POST" class="flex flex-col gap-md" x-data="ajaxForm" @submit.prevent="submit">
                    @csrf
                    <div class="grid grid-cols-1 md-grid-cols-2 gap-md bg-panel-sub border border-panel rounded-panel p-3">
                        <x-form-input name="division_title"
                            id="create_division_title"
                            xModel="section.division_title"
                            :label="lexicon('label_SectionsPositions_SectionName', 'Section Name')"
                            :tooltip="lexicon('tooltip_SectionsPositions_SectionNameInput', 'Enter the display title for this organisational section')"
                            :required="true" />

                        <div class="flex flex-col gap-xs">
                            <x-colour-picker name="division_base_colour"
                                :label="lexicon('label_SectionsPositions_DeptColor', 'Department Colour')"
                                xModel="section.division_base_colour" />
                        </div>
                    </div>

                    <div class="flex flex-col gap-xs bg-panel-sub border border-panel rounded-panel p-3">
                        <label for="create_division_catalogue_id" class="text-xs font-bold text-input-muted">
                            {{ lexicon('label_SectionsPositions_DivisionCatalogue', 'Division Catalogue (Optional)') }}
                        </label>
                        <select id="create_division_catalogue_id"
                            name="division_catalogue_id"
                            x-model="section.division_catalogue_id"
                            class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent">
                            <option value="">{{ lexicon('label_common_None', 'None / Unassigned') }}</option>
                            @foreach($catalogues ?? [] as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->catalogue_title }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex flex-col gap-xs">
                        <div class="flex flex-row flex-wrap items-center justify-between gap-xs">
                            <label for="create_division_desc" class="text-xs font-bold text-input-muted">
                                {{ lexicon('label_SectionsPositions_SectionDesc', 'Description (Optional)') }}
                            </label>
                            <x-button type="button"
                                variant="text"
                                class="text-xs text-accent hover-text-h flex items-center gap-xs"
                                @click="generateAiDescription()"
                                aria-label="{{ lexicon('tooltip_SectionsPositions_GenerateAiDesc', 'Generate a thematic section description based on the section title') }}"
                                x-tooltip="'{{ addslashes(lexicon('tooltip_SectionsPositions_GenerateAiDesc', 'Generate a thematic section description based on the section title')) }}'">
                                <i class="bi bi-magic" aria-hidden="true"></i>
                                <span x-text="isGeneratingAi ? '{{ addslashes(lexicon('msg_SectionsPositions_GeneratingDesc', 'Generating...')) }}' : '{{ addslashes(lexicon('btn_SectionsPositions_GenerateAiDesc', 'Auto-Generate Description')) }}'"></span>
                            </x-button>
                        </div>
                        <textarea id="create_division_desc"
                            x-model="section.division_description"
                            name="division_description"
                            rows="2"
                            aria-label="{{ lexicon('label_SectionsPositions_SectionDesc', 'Description (Optional)') }}"
                            x-tooltip="'{{ addslashes(lexicon('tooltip_SectionsPositions_SectionDescInput', 'Provide a brief overview of this section\'s duties')) }}'"
                            x-init="window.autosize && window.autosize($el)"
                            class="bg-input text-input border-input rounded-input p-input outline-none w-full box-border focus-border-accent focus-shadow-accent"></textarea>
                    </div>

                    <div class="grid grid-cols-1 md-grid-cols-2 gap-md items-center">
                        <div class="w-48">
                            <x-form-input name="division_sort_order"
                                id="create_division_sort_order"
                                type="number"
                                xModel="section.division_sort_order"
                                :label="lexicon('label_common_SortOrder', 'Sort Order')"
                                :tooltip="lexicon('tooltip_SectionsPositions_SectionSortOrder', 'Numeric display sort order on the roster and manifest')" />
                        </div>
                        <div class="flex flex-row items-center gap-sm mt-4">
                            <input type="hidden" name="is_visible" value="0">
                            <input type="checkbox"
                                name="is_visible"
                                id="division_is_visible"
                                value="1"
                                x-model="section.is_visible"
                                class="appearance-none bg-input m-0 w-4 h-4 border-input rounded-input-sm grid place-content-center checkbox-engine cursor-pointer">
                            <label for="division_is_visible" class="text-sm font-bold text-muted cursor-pointer">
                                {{ lexicon('label_common_ShowOnSite', 'Show on Site') }}
                            </label>
                        </div>
                    </div>

                    <div class="border-t-actions pt-6 text-right mt-4 flex flex-row flex-wrap items-center justify-end gap-sm">
                        <x-button type="button"
                            variant="secondary"
                            @click="closeModal()"
                            aria-label="{{ lexicon('tooltip_common_Cancel', 'Cancel and close modal') }}"
                            x-tooltip="'{{ addslashes(lexicon('tooltip_common_Cancel', 'Cancel and close modal')) }}'">
                            {{ lexicon('btn_common_Cancel', 'Cancel') }}
                        </x-button>
                        <x-button type="submit"
                            variant="primary"
                            aria-label="{{ lexicon('tooltip_SectionsPositions_SaveSection', 'Save this new section') }}"
                            x-tooltip="'{{ addslashes(lexicon('tooltip_SectionsPositions_SaveSection', 'Save this new section')) }}'">
                            <span x-text="loading ? '{{ addslashes(lexicon('msg_common_Saving', 'Saving...')) }}' : '{{ addslashes(lexicon('btn_SectionsPositions_SaveSection', 'Save Section')) }}'"></span>
                        </x-button>
                    </div>
                </form>
            </x-modal>

            {{-- MODAL 2: Edit Division --}}
            <x-modal show="modal === 'section-edit'" onClose="closeModal()" maxWidth="max-w-4xl">
                <x-slot:top>
                    {{ lexicon('label_SectionsPositions_EditSection', 'Edit Section') }}
                </x-slot:top>

                <form :action="'/admin/sections/' + section.id" method="POST" class="flex flex-col gap-md" x-data="ajaxForm" @submit.prevent="submit">
                    @csrf
                    @method('PUT')
                    <div class="grid grid-cols-1 md-grid-cols-2 gap-md bg-panel-sub border border-panel rounded-panel p-3">
                        <x-form-input name="division_title"
                            id="edit_division_title"
                            xModel="section.division_title"
                            :label="lexicon('label_SectionsPositions_SectionName', 'Section Name')"
                            :tooltip="lexicon('tooltip_SectionsPositions_SectionNameInput', 'Enter the display title for this organisational section')"
                            :required="true" />

                        <div class="flex flex-col gap-xs">
                            <x-colour-picker name="division_base_colour"
                                :label="lexicon('label_SectionsPositions_DeptColor', 'Department Colour')"
                                xModel="section.division_base_colour" />
                        </div>
                    </div>

                    <div class="flex flex-col gap-xs bg-panel-sub border border-panel rounded-panel p-3">
                        <label for="edit_division_catalogue_id" class="text-xs font-bold text-input-muted">
                            {{ lexicon('label_SectionsPositions_DivisionCatalogue', 'Division Catalogue (Optional)') }}
                        </label>
                        <select id="edit_division_catalogue_id"
                            name="division_catalogue_id"
                            x-model="section.division_catalogue_id"
                            class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent">
                            <option value="">{{ lexicon('label_common_None', 'None / Unassigned') }}</option>
                            @foreach($catalogues ?? [] as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->catalogue_title }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex flex-col gap-xs">
                        <div class="flex flex-row flex-wrap items-center justify-between gap-xs">
                            <label for="edit_division_desc" class="text-xs font-bold text-input-muted">
                                {{ lexicon('label_SectionsPositions_SectionDesc', 'Description (Optional)') }}
                            </label>
                            <x-button type="button"
                                variant="text"
                                class="text-xs text-accent hover-text-h flex items-center gap-xs"
                                @click="generateAiDescription()"
                                aria-label="{{ lexicon('tooltip_SectionsPositions_GenerateAiDesc', 'Generate a thematic section description based on the section title') }}"
                                x-tooltip="'{{ addslashes(lexicon('tooltip_SectionsPositions_GenerateAiDesc', 'Generate a thematic section description based on the section title')) }}'">
                                <i class="bi bi-magic" aria-hidden="true"></i>
                                <span x-text="isGeneratingAi ? '{{ addslashes(lexicon('msg_SectionsPositions_GeneratingDesc', 'Generating...')) }}' : '{{ addslashes(lexicon('btn_SectionsPositions_GenerateAiDesc', 'Auto-Generate Description')) }}'"></span>
                            </x-button>
                        </div>
                        <textarea id="edit_division_desc"
                            x-model="section.division_description"
                            name="division_description"
                            rows="2"
                            aria-label="{{ lexicon('label_SectionsPositions_SectionDesc', 'Description (Optional)') }}"
                            x-tooltip="'{{ addslashes(lexicon('tooltip_SectionsPositions_SectionDescInput', 'Provide a brief overview of this section\'s duties')) }}'"
                            x-init="window.autosize && window.autosize($el)"
                            class="bg-input text-input border-input rounded-input p-input outline-none w-full box-border focus-border-accent focus-shadow-accent"></textarea>
                    </div>

                    <div class="grid grid-cols-1 md-grid-cols-2 gap-md items-center">
                        <div class="w-48">
                            <x-form-input name="division_sort_order"
                                id="edit_division_sort_order"
                                type="number"
                                xModel="section.division_sort_order"
                                :label="lexicon('label_common_SortOrder', 'Sort Order')"
                                :tooltip="lexicon('tooltip_SectionsPositions_SectionSortOrder', 'Numeric display sort order on the roster and manifest')"
                                :required="true" />
                        </div>
                        <div class="flex flex-row items-center gap-sm mt-4">
                            <input type="hidden" name="is_visible" value="0">
                            <input type="checkbox"
                                name="is_visible"
                                id="edit_division_is_visible"
                                value="1"
                                x-model="section.is_visible"
                                class="appearance-none bg-input m-0 w-4 h-4 border-input rounded-input-sm grid place-content-center checkbox-engine cursor-pointer">
                            <label for="edit_division_is_visible" class="text-sm font-bold text-muted cursor-pointer">
                                {{ lexicon('label_common_ShowOnSite', 'Show on Site') }}
                            </label>
                        </div>
                    </div>

                    <div class="border-t-actions pt-6 text-right mt-4 flex flex-row flex-wrap items-center justify-end gap-sm">
                        <x-button type="button"
                            variant="secondary"
                            @click="closeModal()"
                            aria-label="{{ lexicon('tooltip_common_Cancel', 'Cancel and close modal') }}"
                            x-tooltip="'{{ addslashes(lexicon('tooltip_common_Cancel', 'Cancel and close modal')) }}'">
                            {{ lexicon('btn_common_Cancel', 'Cancel') }}
                        </x-button>
                        <x-button type="submit"
                            variant="primary"
                            aria-label="{{ lexicon('tooltip_SectionsPositions_UpdateSection', 'Save changes to this section') }}"
                            x-tooltip="'{{ addslashes(lexicon('tooltip_SectionsPositions_UpdateSection', 'Save changes to this section')) }}'">
                            <span x-text="loading ? '{{ addslashes(lexicon('msg_common_Saving', 'Saving...')) }}' : '{{ addslashes(lexicon('btn_SectionsPositions_UpdateSection', 'Update Section')) }}'"></span>
                        </x-button>
                    </div>
                </form>
            </x-modal>

            {{-- MODAL 3: Add Assignment --}}
            <x-modal show="modal === 'position-create'" onClose="closeModal()" maxWidth="max-w-4xl">
                <x-slot:top>
                    <div class="flex flex-row flex-wrap items-center gap-sm">
                        <span>{{ lexicon('label_SectionsPositions_CreatePosition', 'Add Position') }}</span>
                        <span class="text-accent" x-text="'- ' + position.division_title"></span>
                    </div>
                </x-slot:top>

                <form :action="'/admin/sections/' + position.division_id + '/positions'" method="POST" class="flex flex-col gap-md" x-data="ajaxForm" @submit.prevent="submit">
                    @csrf
                    <div class="grid grid-cols-1 md-grid-cols-2 gap-md bg-panel-sub border border-panel rounded-panel p-3">
                        <x-form-input name="assignment_title"
                            id="create_assignment_title"
                            xModel="position.assignment_title"
                            :label="lexicon('label_SectionsPositions_PositionName', 'Position Name')"
                            :tooltip="lexicon('tooltip_SectionsPositions_PositionNameInput', 'Enter the role or duty title')"
                            :required="true" />

                        <x-form-input name="max_assignees"
                            id="create_assignment_capacity"
                            type="number"
                            min="1"
                            max="999"
                            xModel="position.max_assignees"
                            class="text-center"
                            :label="lexicon('label_SectionsPositions_Capacity', 'Max Capacity')"
                            :tooltip="lexicon('tooltip_SectionsPositions_CapacityInput', 'Maximum number of active characters that can hold this position')"
                            :required="true" />
                    </div>

                    <div class="grid grid-cols-1 md-grid-cols-2 gap-md bg-panel-sub border border-panel rounded-panel p-3 items-center">
                        <div class="flex flex-row flex-wrap items-center gap-sm">
                            <input type="hidden" name="is_open" value="0">
                            <input type="checkbox"
                                id="create_assignment_is_open"
                                name="is_open"
                                value="1"
                                x-model="position.is_open"
                                aria-label="{{ lexicon('label_SectionsPositions_IsOpen', 'Open for Applications') }}"
                                x-tooltip="'{{ addslashes(lexicon('tooltip_SectionsPositions_IsOpenToggle', 'Allow applicants to apply for vacant slots in this position')) }}'"
                                class="appearance-none bg-input m-0 w-4 h-4 border-input rounded-input-sm grid place-content-center checkbox-engine cursor-pointer">
                            <label for="create_assignment_is_open" class="text-xs font-bold text-input-muted cursor-pointer">
                                {{ lexicon('label_SectionsPositions_IsOpen', 'Open for Applications') }}
                            </label>
                        </div>
                        <div class="flex flex-row flex-wrap items-center gap-sm">
                            <input type="hidden" name="is_visible" value="0">
                            <input type="checkbox"
                                id="create_assignment_is_visible"
                                name="is_visible"
                                value="1"
                                x-model="position.is_visible"
                                class="appearance-none bg-input m-0 w-4 h-4 border-input rounded-input-sm grid place-content-center checkbox-engine cursor-pointer">
                            <label for="create_assignment_is_visible" class="text-xs font-bold text-input-muted cursor-pointer">
                                {{ lexicon('label_common_ShowOnSite', 'Show on Site') }}
                            </label>
                        </div>
                    </div>

                    <div class="flex flex-col gap-xs">
                        <label for="create_assignment_desc" class="text-xs font-bold text-input-muted">
                            {{ lexicon('label_SectionsPositions_PositionDesc', 'Description (Optional)') }}
                        </label>
                        <textarea id="create_assignment_desc"
                            x-model="position.assignment_description"
                            name="assignment_description"
                            rows="2"
                            aria-label="{{ lexicon('label_SectionsPositions_PositionDesc', 'Description (Optional)') }}"
                            x-tooltip="'{{ addslashes(lexicon('tooltip_SectionsPositions_PositionDescInput', 'Describe the responsibilities and expectations for this role')) }}'"
                            x-init="window.autosize && window.autosize($el)"
                            class="bg-input text-input border-input rounded-input p-input outline-none w-full box-border focus-border-accent focus-shadow-accent"></textarea>
                    </div>

                    <div class="border-t-actions pt-6 text-right mt-4 flex flex-row flex-wrap items-center justify-end gap-sm">
                        <x-button type="button"
                            variant="secondary"
                            @click="closeModal()"
                            aria-label="{{ lexicon('tooltip_common_Cancel', 'Cancel and close modal') }}"
                            x-tooltip="'{{ addslashes(lexicon('tooltip_common_Cancel', 'Cancel and close modal')) }}'">
                            {{ lexicon('btn_common_Cancel', 'Cancel') }}
                        </x-button>
                        <x-button type="submit"
                            variant="primary"
                            aria-label="{{ lexicon('tooltip_SectionsPositions_SavePosition', 'Save this new position') }}"
                            x-tooltip="'{{ addslashes(lexicon('tooltip_SectionsPositions_SavePosition', 'Save this new position')) }}'">
                            <span x-text="loading ? '{{ addslashes(lexicon('msg_common_Saving', 'Saving...')) }}' : '{{ addslashes(lexicon('btn_SectionsPositions_SavePosition', 'Save Position')) }}'"></span>
                        </x-button>
                    </div>
                </form>
            </x-modal>

            {{-- MODAL 4: Edit Assignment --}}
            <x-modal show="modal === 'position-edit'" onClose="closeModal()" maxWidth="max-w-4xl">
                <x-slot:top>
                    {{ lexicon('label_SectionsPositions_EditPosition', 'Edit Position') }}
                </x-slot:top>

                <form :action="'/admin/positions/' + position.id" method="POST" class="flex flex-col gap-md" x-data="ajaxForm" @submit.prevent="submit">
                    @csrf
                    @method('PUT')

                    <div class="flex flex-col gap-xs bg-panel-sub border border-panel rounded-panel p-3">
                        <label for="edit_position_division_id" class="text-xs font-bold text-input-muted">
                            {{ lexicon('label_SectionsPositions_SectionAssignment', 'Section Assignment') }}
                        </label>
                        <select id="edit_position_division_id"
                            x-model="position.division_id"
                            name="division_id"
                            aria-label="{{ lexicon('label_SectionsPositions_SectionAssignment', 'Section Assignment') }}"
                            x-tooltip="'{{ addslashes(lexicon('tooltip_SectionsPositions_SectionAssignmentSelect', 'Reassign this position to a different organisational section')) }}'"
                            class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent">
                            @foreach($sections as $sec)
                            <option value="{{ $sec->id }}">{{ $sec->division_title }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-1 md-grid-cols-2 gap-md bg-panel-sub border border-panel rounded-panel p-3">
                        <x-form-input name="assignment_title"
                            id="edit_assignment_title"
                            xModel="position.assignment_title"
                            :label="lexicon('label_SectionsPositions_PositionName', 'Position Name')"
                            :tooltip="lexicon('tooltip_SectionsPositions_PositionNameInput', 'Enter the role or duty title')"
                            :required="true" />

                        <x-form-input name="max_assignees"
                            id="edit_assignment_capacity"
                            type="number"
                            min="1"
                            max="999"
                            xModel="position.max_assignees"
                            class="text-center"
                            :label="lexicon('label_SectionsPositions_Capacity', 'Max Capacity')"
                            :tooltip="lexicon('tooltip_SectionsPositions_CapacityInput', 'Maximum number of active characters that can hold this position')"
                            :required="true" />
                    </div>

                    <div class="grid grid-cols-1 md-grid-cols-2 gap-md bg-panel-sub border border-panel rounded-panel p-3 items-center">
                        <div class="flex flex-row flex-wrap items-center gap-sm">
                            <input type="hidden" name="is_open" value="0">
                            <input type="checkbox"
                                id="edit_assignment_is_open"
                                name="is_open"
                                value="1"
                                x-model="position.is_open"
                                aria-label="{{ lexicon('label_SectionsPositions_IsOpen', 'Open for Applications') }}"
                                x-tooltip="'{{ addslashes(lexicon('tooltip_SectionsPositions_IsOpenToggle', 'Allow applicants to apply for vacant slots in this position')) }}'"
                                class="appearance-none bg-input m-0 w-4 h-4 border-input rounded-input-sm grid place-content-center checkbox-engine cursor-pointer">
                            <label for="edit_assignment_is_open" class="text-xs font-bold text-input-muted cursor-pointer">
                                {{ lexicon('label_SectionsPositions_IsOpen', 'Open for Applications') }}
                            </label>
                        </div>
                        <div class="flex flex-row flex-wrap items-center gap-sm">
                            <input type="hidden" name="is_visible" value="0">
                            <input type="checkbox"
                                id="edit_assignment_is_visible"
                                name="is_visible"
                                value="1"
                                x-model="position.is_visible"
                                class="appearance-none bg-input m-0 w-4 h-4 border-input rounded-input-sm grid place-content-center checkbox-engine cursor-pointer">
                            <label for="edit_assignment_is_visible" class="text-xs font-bold text-input-muted cursor-pointer">
                                {{ lexicon('label_common_ShowOnSite', 'Show on Site') }}
                            </label>
                        </div>
                    </div>

                    <div class="flex flex-col gap-xs">
                        <label for="edit_assignment_desc" class="text-xs font-bold text-input-muted">
                            {{ lexicon('label_SectionsPositions_PositionDesc', 'Description (Optional)') }}
                        </label>
                        <textarea id="edit_assignment_desc"
                            x-model="position.assignment_description"
                            name="assignment_description"
                            rows="2"
                            aria-label="{{ lexicon('label_SectionsPositions_PositionDesc', 'Description (Optional)') }}"
                            x-tooltip="'{{ addslashes(lexicon('tooltip_SectionsPositions_PositionDescInput', 'Describe the responsibilities and expectations for this role')) }}'"
                            x-init="window.autosize && window.autosize($el)"
                            class="bg-input text-input border-input rounded-input p-input outline-none w-full box-border focus-border-accent focus-shadow-accent"></textarea>
                    </div>

                    <div class="border-t-actions pt-6 text-right mt-4 flex flex-row flex-wrap items-center justify-end gap-sm">
                        <x-button type="button"
                            variant="secondary"
                            @click="closeModal()"
                            aria-label="{{ lexicon('tooltip_common_Cancel', 'Cancel and close modal') }}"
                            x-tooltip="'{{ addslashes(lexicon('tooltip_common_Cancel', 'Cancel and close modal')) }}'">
                            {{ lexicon('btn_common_Cancel', 'Cancel') }}
                        </x-button>
                        <x-button type="submit"
                            variant="primary"
                            aria-label="{{ lexicon('tooltip_SectionsPositions_UpdatePosition', 'Save changes to this position') }}"
                            x-tooltip="'{{ addslashes(lexicon('tooltip_SectionsPositions_UpdatePosition', 'Save changes to this position')) }}'">
                            <span x-text="loading ? '{{ addslashes(lexicon('msg_common_Saving', 'Saving...')) }}' : '{{ addslashes(lexicon('btn_SectionsPositions_UpdatePosition', 'Update Position')) }}'"></span>
                        </x-button>
                    </div>
                </form>
            </x-modal>

            {{-- ========================================================= --}}
            {{-- DIVISION CATALOGUE MODALS                                 --}}
            {{-- ========================================================= --}}

            {{-- Catalogue Management Modal --}}
            <x-modal show="catalogueModalOpen" onClose="catalogueModalOpen = false" maxWidth="max-w-2xl">
                <x-slot:top>
                    {{ lexicon('label_SectionsPositions_ManageCatalogues', 'Manage Division Catalogues') }}
                </x-slot:top>

                <div class="flex flex-col gap-md">
                    <form @submit.prevent="submitCatalogue()" class="bg-panel-sub border border-panel rounded-panel p-3 flex flex-col gap-sm">
                        <x-form-input name="catalogue_title"
                            id="catalogue_title"
                            xModel="catalogueTitle"
                            :label="lexicon('label_SectionsPositions_CatalogueTitle', 'Catalogue Name')"
                            :placeholder="lexicon('label_SectionsPositions_CataloguePlaceholder', 'e.g. USS Enterprise, Deep Space 9...')"
                            :tooltip="lexicon('tooltip_SectionsPositions_CatalogueTitleInput', 'Enter the display title for this division catalogue')"
                            :required="true" />

                        <div class="flex flex-col gap-xs">
                            <label for="catalogue_description" class="text-xs font-bold text-input-muted">
                                {{ lexicon('label_SectionsPositions_CatalogueDesc', 'Catalogue Description (Optional)') }}
                            </label>
                            <textarea id="catalogue_description"
                                x-model="catalogueDesc"
                                name="catalogue_description"
                                rows="2"
                                aria-label="{{ lexicon('label_SectionsPositions_CatalogueDesc', 'Catalogue Description (Optional)') }}"
                                x-tooltip="'{{ addslashes(lexicon('tooltip_SectionsPositions_CatalogueDescInput', 'Provide a brief overview of this ship, station, or group')) }}'"
                                class="bg-input text-input border-input rounded-input p-input outline-none w-full box-border focus-border-accent focus-shadow-accent"></textarea>
                        </div>

                        <div class="flex justify-end gap-xs">
                            <x-button type="submit" variant="primary">
                                <span x-text="catalogueMode === 'edit' ? '{{ addslashes(lexicon('btn_common_Update', 'Update')) }}' : '{{ addslashes(lexicon('btn_common_Save', 'Save')) }}'"></span>
                            </x-button>
                        </div>
                    </form>

                    <div class="flex flex-col gap-xs max-h-64 overflow-y-auto">
                        @forelse($catalogues ?? [] as $cat)
                        <div class="flex items-center justify-between bg-panel-sub border border-panel rounded-panel p-3">
                            <div>
                                <span class="font-bold text-h">{{ $cat->catalogue_title }}</span>
                                <span class="text-xs text-muted ml-2">({{ $cat->divisions->count() }} divisions)</span>
                            </div>
                            <div class="flex items-center gap-xs">
                                <x-button type="button" variant="secondary" class="p-btn-sm text-xs" @click="openAssignCatalogueModal({{ $cat->id }}, @js($cat->divisions->pluck('id')))">
                                    <i class="bi bi-diagram-3 mr-1"></i> Assign
                                </x-button>
                                <x-button type="button" variant="danger" class="p-btn-sm text-xs" @click="deleteCatalogue({{ $cat->id }})">
                                    <i class="bi bi-trash"></i>
                                </x-button>
                            </div>
                        </div>
                        @empty
                        <p class="text-sm text-muted text-center py-4 italic">{{ lexicon('msg_SectionsPositions_NoCatalogues', 'No division catalogues defined.') }}</p>
                        @endforelse
                    </div>
                </div>

                <x-slot:bottom>
                    <x-button type="button" variant="secondary" @click="catalogueModalOpen = false">
                        {{ lexicon('btn_common_Cancel', 'Cancel') }}
                    </x-button>
                </x-slot:bottom>
            </x-modal>

            {{-- Assign Divisions to Catalogue Modal --}}
            <x-modal show="assignCatalogueModalOpen" onClose="assignCatalogueModalOpen = false" maxWidth="max-w-md">
                <x-slot:top>
                    Assign Divisions to Catalogue
                </x-slot:top>

                <form @submit.prevent="submitCatalogueAssignment()" class="flex flex-col gap-md">
                    <div class="flex flex-col gap-xs max-h-64 overflow-y-auto bg-panel-sub border border-panel rounded-panel p-3">
                        @foreach($sections as $sec)
                        <label class="flex items-center gap-sm cursor-pointer p-1">
                            <input type="checkbox" value="{{ $sec->id }}" x-model="assignedSectionIds" class="appearance-none bg-input m-0 w-4 h-4 border-input rounded-input-sm grid place-content-center checkbox-engine cursor-pointer">
                            <span class="text-sm text-p">{{ $sec->division_title }}</span>
                        </label>
                        @endforeach
                    </div>

                    <div class="flex justify-end gap-xs">
                        <x-button type="button" variant="secondary" @click="assignCatalogueModalOpen = false">
                            {{ lexicon('btn_common_Cancel', 'Cancel') }}
                        </x-button>
                        <x-button type="submit" variant="primary">
                            {{ lexicon('btn_common_Save', 'Save Changes') }}
                        </x-button>
                    </div>
                </form>
            </x-modal>
            @endcan

        </x-slot:content>
    </x-admin-grid-wrapper>
</div>
@endsection