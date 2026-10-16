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
                        <x-button type="button"
                                  variant="success"
                                  class="p-btn-sm text-sm flex items-center gap-xs"
                                  @click="openCreateSection()"
                                  aria-label="{{ lexicon('tooltip_SectionsPositions_CreateSection', 'Create a new organisational section') }}"
                                  x-tooltip="'{{ addslashes(lexicon('tooltip_SectionsPositions_CreateSection', 'Create a new organisational section')) }}'">
                            <i class="bi bi-plus-lg" aria-hidden="true"></i>
                            <span>{{ lexicon('btn_SectionsPositions_CreateSection', 'Create Section') }}</span>
                        </x-button>
                    @endcan
                </x-slot:actions>
            </x-page-header>

            {{-- Search / Filter Bar --}}
            <div class="bg-panel border border-panel rounded-panel p-panel shadow-panel mb-4">
                <x-form-input name="filter_sections"
                              id="filter_sections"
                              xModel="searchQuery"
                              :label="lexicon('label_SectionsPositions_FilterSections', 'Filter Sections & Positions')"
                              :placeholder="lexicon('label_SectionsPositions_FilterPlaceholder', 'Search by section name, position title, or description...')"
                              :tooltip="lexicon('tooltip_SectionsPositions_FilterInput', 'Instantaneously filter sections and positions using fuzzy search')" />
            </div>

            {{-- Sections Loop --}}
            <div class="flex flex-col gap-lg" id="sections-container" x-ref="sectionsContainer">
                @forelse($sections as $sec)
                    @php
                        $posSearchList = $sec->positions ? $sec->positions->pluck('name')->merge($sec->positions->pluck('description'))->filter()->values()->all() : [];
                    @endphp
                    <div x-show="matchesSearch('{{ addslashes($sec->name) }}', '{{ addslashes($sec->description ?? '') }}', @js($posSearchList))">
                        <x-accordion-panel :title="$sec->name" :expanded="true" id="section-row-{{ $sec->id }}">
                            <x-slot:top>
                                @if($sec->rankSet)
                                    <x-badge variant="info"
                                             aria-label="{{ lexicon('tooltip_SectionsPositions_LinkedRankSet', 'Rank set assigned via Ranks Manager') }}"
                                             x-tooltip="'{{ addslashes(lexicon('tooltip_SectionsPositions_LinkedRankSet', 'Rank set assigned via Ranks Manager')) }}'">
                                        <i class="bi bi-award" aria-hidden="true"></i>
                                        <span>{{ $sec->rankSet->name }}</span>
                                    </x-badge>
                                @endif

                                @can('manage_sections')
                                    <x-button type="button"
                                              variant="text"
                                              class="text-accent hover-text-h text-sm flex items-center gap-xs"
                                              @click="openCreatePosition({ section_id: {{ $sec->id }}, section_name: '{{ addslashes($sec->name) }}', section_color: '{{ $sec->color }}' })"
                                              aria-label="{{ lexicon('tooltip_SectionsPositions_AddPosition', 'Add a new position to this section') }}"
                                              x-tooltip="'{{ addslashes(lexicon('tooltip_SectionsPositions_AddPosition', 'Add a new position to this section')) }}'">
                                        <i class="bi bi-plus-circle" aria-hidden="true"></i>
                                        <span>{{ lexicon('btn_SectionsPositions_AddPosition', 'Add Position') }}</span>
                                    </x-button>

                                    <x-button type="button"
                                              variant="text"
                                              class="text-warning hover-text-h text-sm flex items-center gap-xs"
                                              @click="openEditSection({ id: {{ $sec->id }}, name: '{{ addslashes($sec->name) }}', color: '{{ $sec->color }}', description: '{{ addslashes($sec->description) }}', order: {{ $sec->order }} })"
                                              aria-label="{{ lexicon('tooltip_SectionsPositions_EditSection', 'Edit section details and colour') }}"
                                              x-tooltip="'{{ addslashes(lexicon('tooltip_SectionsPositions_EditSection', 'Edit section details and colour')) }}'">
                                        <i class="bi bi-pencil-square" aria-hidden="true"></i>
                                        <span>{{ lexicon('btn_SectionsPositions_EditSection', 'Edit Section') }}</span>
                                    </x-button>

                                    <x-button type="button"
                                              variant="text"
                                              class="text-danger hover-text-h text-sm flex items-center gap-xs"
                                              x-data="deleteAction('/admin/sections/{{ $sec->id }}', 'DELETE', '{{ addslashes($sec->name) }}')"
                                              @click.prevent="confirmDelete()"
                                              aria-label="{{ lexicon('tooltip_SectionsPositions_DeleteSection', 'Delete this section and its positions') }}"
                                              x-tooltip="'{{ addslashes(lexicon('tooltip_SectionsPositions_DeleteSection', 'Delete this section and its positions')) }}'">
                                        <i class="bi bi-trash" aria-hidden="true"></i>
                                        <span>{{ lexicon('btn_common_Delete', 'Delete') }}</span>
                                    </x-button>
                                @endcan
                            </x-slot:top>

                            @if($sec->description)
                                <span class="text-xs text-muted border-b border-panel pb-2 block">{{ $sec->description }}</span>
                            @endif

                            {{-- Positions Grid --}}
                            @if($sec->positions && $sec->positions->count() > 0)
                                <div class="grid grid-cols-1 lg-grid-cols-3 gap-md w-full">
                                    @foreach($sec->positions as $pos)
                                        @php
                                            $assignedCount = $pos->characters ? $pos->characters->count() : 0;
                                            $slotsAvailable = max(0, $pos->capacity - $assignedCount);
                                            $isOpen = (bool) ($pos->is_open ?? true);
                                        @endphp
                                        <div class="bg-panel-sub border border-panel rounded-panel p-3 flex flex-row flex-wrap justify-between items-start gap-sm"
                                             x-show="matchesPosition('{{ addslashes($pos->name) }}', '{{ addslashes($pos->description ?? '') }}', '{{ addslashes($sec->name) }}')">
                                            <div class="flex flex-col gap-xs flex-grow">
                                                <span class="font-bold text-p block">{{ $pos->name }}</span>
                                                @if($pos->description)
                                                    <span class="text-xs text-muted block">{{ $pos->description }}</span>
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
                                                        ({{ $assignedCount }}/{{ $pos->capacity }})
                                                    </span>
                                                </div>
                                            </div>
                                            @can('manage_sections')
                                                <div class="flex flex-col gap-sm items-end text-right">
                                                    <x-button type="button"
                                                              variant="text"
                                                              class="text-xs text-warning hover-text-h p-0 flex items-center gap-xs"
                                                              @click="openEditPosition({ id: {{ $pos->id }}, section_id: '{{ $pos->section_id }}', name: '{{ addslashes($pos->name) }}', description: '{{ addslashes($pos->description) }}', capacity: {{ $pos->capacity ?? 1 }}, is_open: {{ $isOpen ? 'true' : 'false' }} })"
                                                              aria-label="{{ lexicon('tooltip_SectionsPositions_EditPosition', 'Edit this position') }}"
                                                              x-tooltip="'{{ addslashes(lexicon('tooltip_SectionsPositions_EditPosition', 'Edit this position')) }}'">
                                                        <i class="bi bi-pencil" aria-hidden="true"></i>
                                                        <span>{{ lexicon('btn_common_Edit', 'Edit') }}</span>
                                                    </x-button>
                                                    <x-button type="button"
                                                              variant="text"
                                                              class="text-xs text-danger hover-text-h p-0 flex items-center gap-xs"
                                                              x-data="deleteAction('/admin/positions/{{ $pos->id }}', 'DELETE', '{{ addslashes($pos->name) }}')"
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
                {{-- MODAL 1: Create Section --}}
                <x-modal show="modal === 'section-create'" onClose="closeModal()" maxWidth="max-w-4xl">
                    <x-slot:top>
                        {{ lexicon('label_SectionsPositions_CreateSection', 'Create Section') }}
                    </x-slot:top>
                    
                    <form action="/admin/sections" method="POST" class="flex flex-col gap-md" x-data="ajaxForm" @submit.prevent="submit">
                        @csrf
                        <div class="grid grid-cols-1 md-grid-cols-2 gap-md bg-panel-sub border border-panel rounded-panel p-3">
                            <x-form-input name="name"
                                          id="create_section_name"
                                          xModel="section.name"
                                          :label="lexicon('label_SectionsPositions_SectionName', 'Section Name')"
                                          :tooltip="lexicon('tooltip_SectionsPositions_SectionNameInput', 'Enter the display title for this organisational section')"
                                          :required="true" />

                            <div class="flex flex-col gap-xs">
                                <x-colour-picker name="color"
                                                 :label="lexicon('label_SectionsPositions_DeptColor', 'Department Colour')"
                                                 xModel="section.color" />
                            </div>
                        </div>

                        <div class="flex flex-col gap-xs">
                            <div class="flex flex-row flex-wrap items-center justify-between gap-xs">
                                <label for="create_section_desc" class="text-xs font-bold text-input-muted">
                                    {{ lexicon('label_SectionsPositions_SectionDesc', 'Description (Optional)') }}
                                </label>
                                <x-button type="button"
                                          variant="text"
                                          class="text-xs text-accent hover-text-h flex items-center gap-xs"
                                          @click="generateAiDescription()"
                                          aria-label="{{ lexicon('tooltip_SectionsPositions_GenerateAiDesc', 'Generate a thematic section description based on the section name') }}"
                                          x-tooltip="'{{ addslashes(lexicon('tooltip_SectionsPositions_GenerateAiDesc', 'Generate a thematic section description based on the section name')) }}'">
                                    <i class="bi bi-magic" aria-hidden="true"></i>
                                    <span x-text="isGeneratingAi ? '{{ addslashes(lexicon('msg_SectionsPositions_GeneratingDesc', 'Generating...')) }}' : '{{ addslashes(lexicon('btn_SectionsPositions_GenerateAiDesc', 'Auto-Generate Description')) }}'"></span>
                                </x-button>
                            </div>
                            <textarea id="create_section_desc"
                                      x-model="section.description" 
                                      name="description" 
                                      rows="2" 
                                      aria-label="{{ lexicon('label_SectionsPositions_SectionDesc', 'Description (Optional)') }}"
                                      x-tooltip="'{{ addslashes(lexicon('tooltip_SectionsPositions_SectionDescInput', 'Provide a brief overview of this section\'s duties')) }}'"
                                      x-init="window.autosize && window.autosize($el)"
                                      class="bg-input text-input border-input rounded-input p-input outline-none w-full box-border focus-border-accent focus-shadow-accent"></textarea>
                        </div>

                        <div class="w-48">
                            <x-form-input name="order"
                                          id="create_section_order"
                                          type="number"
                                          xModel="section.order"
                                          :label="lexicon('label_common_SortOrder', 'Sort Order')"
                                          :tooltip="lexicon('tooltip_SectionsPositions_SectionSortOrder', 'Numeric display order on the roster and manifest')" />
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

                {{-- MODAL 2: Edit Section --}}
                <x-modal show="modal === 'section-edit'" onClose="closeModal()" maxWidth="max-w-4xl">
                    <x-slot:top>
                        {{ lexicon('label_SectionsPositions_EditSection', 'Edit Section') }}
                    </x-slot:top>
                    
                    <form :action="'/admin/sections/' + section.id" method="POST" class="flex flex-col gap-md" x-data="ajaxForm" @submit.prevent="submit">
                        @csrf
                        @method('PUT')
                        <div class="grid grid-cols-1 md-grid-cols-2 gap-md bg-panel-sub border border-panel rounded-panel p-3">
                            <x-form-input name="name"
                                          id="edit_section_name"
                                          xModel="section.name"
                                          :label="lexicon('label_SectionsPositions_SectionName', 'Section Name')"
                                          :tooltip="lexicon('tooltip_SectionsPositions_SectionNameInput', 'Enter the display title for this organisational section')"
                                          :required="true" />

                            <div class="flex flex-col gap-xs">
                                <x-colour-picker name="color"
                                                 :label="lexicon('label_SectionsPositions_DeptColor', 'Department Colour')"
                                                 xModel="section.color" />
                            </div>
                        </div>

                        <div class="flex flex-col gap-xs">
                            <div class="flex flex-row flex-wrap items-center justify-between gap-xs">
                                <label for="edit_section_desc" class="text-xs font-bold text-input-muted">
                                    {{ lexicon('label_SectionsPositions_SectionDesc', 'Description (Optional)') }}
                                </label>
                                <x-button type="button"
                                          variant="text"
                                          class="text-xs text-accent hover-text-h flex items-center gap-xs"
                                          @click="generateAiDescription()"
                                          aria-label="{{ lexicon('tooltip_SectionsPositions_GenerateAiDesc', 'Generate a thematic section description based on the section name') }}"
                                          x-tooltip="'{{ addslashes(lexicon('tooltip_SectionsPositions_GenerateAiDesc', 'Generate a thematic section description based on the section name')) }}'">
                                    <i class="bi bi-magic" aria-hidden="true"></i>
                                    <span x-text="isGeneratingAi ? '{{ addslashes(lexicon('msg_SectionsPositions_GeneratingDesc', 'Generating...')) }}' : '{{ addslashes(lexicon('btn_SectionsPositions_GenerateAiDesc', 'Auto-Generate Description')) }}'"></span>
                                </x-button>
                            </div>
                            <textarea id="edit_section_desc"
                                      x-model="section.description" 
                                      name="description" 
                                      rows="2" 
                                      aria-label="{{ lexicon('label_SectionsPositions_SectionDesc', 'Description (Optional)') }}"
                                      x-tooltip="'{{ addslashes(lexicon('tooltip_SectionsPositions_SectionDescInput', 'Provide a brief overview of this section\'s duties')) }}'"
                                      x-init="window.autosize && window.autosize($el)"
                                      class="bg-input text-input border-input rounded-input p-input outline-none w-full box-border focus-border-accent focus-shadow-accent"></textarea>
                        </div>

                        <div class="w-48">
                            <x-form-input name="order"
                                          id="edit_section_order"
                                          type="number"
                                          xModel="section.order"
                                          :label="lexicon('label_common_SortOrder', 'Sort Order')"
                                          :tooltip="lexicon('tooltip_SectionsPositions_SectionSortOrder', 'Numeric display order on the roster and manifest')"
                                          :required="true" />
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

                {{-- MODAL 3: Add Position --}}
                <x-modal show="modal === 'position-create'" onClose="closeModal()" maxWidth="max-w-4xl">
                    <x-slot:top>
                        <div class="flex flex-row flex-wrap items-center gap-sm">
                            <span>{{ lexicon('label_SectionsPositions_CreatePosition', 'Add Position') }}</span>
                            <span class="text-accent" x-text="'- ' + position.section_name"></span>
                        </div>
                    </x-slot:top>
                    
                    <form :action="'/admin/sections/' + position.section_id + '/positions'" method="POST" class="flex flex-col gap-md" x-data="ajaxForm" @submit.prevent="submit">
                        @csrf
                        <div class="grid grid-cols-1 md-grid-cols-2 gap-md bg-panel-sub border border-panel rounded-panel p-3">
                            <x-form-input name="name"
                                          id="create_position_name"
                                          xModel="position.name"
                                          :label="lexicon('label_SectionsPositions_PositionName', 'Position Name')"
                                          :tooltip="lexicon('tooltip_SectionsPositions_PositionNameInput', 'Enter the role or duty title')"
                                          :required="true" />

                            <x-form-input name="capacity"
                                          id="create_position_capacity"
                                          type="number"
                                          min="1"
                                          max="999"
                                          xModel="position.capacity"
                                          class="text-center"
                                          :label="lexicon('label_SectionsPositions_Capacity', 'Max Capacity')"
                                          :tooltip="lexicon('tooltip_SectionsPositions_CapacityInput', 'Maximum number of active characters that can hold this position')"
                                          :required="true" />
                        </div>

                        <div class="flex flex-row flex-wrap items-center gap-sm bg-panel-sub border border-panel rounded-panel p-3">
                            <input type="hidden" name="is_open" value="0">
                            <input type="checkbox"
                                   id="create_position_is_open"
                                   name="is_open"
                                   value="1"
                                   x-model="position.is_open"
                                   aria-label="{{ lexicon('label_SectionsPositions_IsOpen', 'Open for Applications') }}"
                                   x-tooltip="'{{ addslashes(lexicon('tooltip_SectionsPositions_IsOpenToggle', 'Allow applicants to apply for vacant slots in this position')) }}'"
                                   class="appearance-none bg-input m-0 w-4 h-4 border-input rounded-input-sm grid place-content-center checkbox-engine cursor-pointer">
                            <label for="create_position_is_open" class="text-xs font-bold text-input-muted cursor-pointer">
                                {{ lexicon('label_SectionsPositions_IsOpen', 'Open for Applications') }}
                            </label>
                        </div>

                        <div class="flex flex-col gap-xs">
                            <label for="create_position_desc" class="text-xs font-bold text-input-muted">
                                {{ lexicon('label_SectionsPositions_PositionDesc', 'Description (Optional)') }}
                            </label>
                            <textarea id="create_position_desc"
                                      x-model="position.description" 
                                      name="description" 
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

                {{-- MODAL 4: Edit Position --}}
                <x-modal show="modal === 'position-edit'" onClose="closeModal()" maxWidth="max-w-4xl">
                    <x-slot:top>
                        {{ lexicon('label_SectionsPositions_EditPosition', 'Edit Position') }}
                    </x-slot:top>
                    
                    <form :action="'/admin/positions/' + position.id" method="POST" class="flex flex-col gap-md" x-data="ajaxForm" @submit.prevent="submit">
                        @csrf
                        @method('PUT')
                        
                        <div class="flex flex-col gap-xs bg-panel-sub border border-panel rounded-panel p-3">
                            <label for="edit_position_section_id" class="text-xs font-bold text-input-muted">
                                {{ lexicon('label_SectionsPositions_SectionAssignment', 'Section Assignment') }}
                            </label>
                            <select id="edit_position_section_id"
                                    x-model="position.section_id" 
                                    name="section_id" 
                                    aria-label="{{ lexicon('label_SectionsPositions_SectionAssignment', 'Section Assignment') }}"
                                    x-tooltip="'{{ addslashes(lexicon('tooltip_SectionsPositions_SectionAssignmentSelect', 'Reassign this position to a different organisational section')) }}'"
                                    class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent">
                                @foreach($sections as $sec)
                                    <option value="{{ $sec->id }}">{{ $sec->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="grid grid-cols-1 md-grid-cols-2 gap-md bg-panel-sub border border-panel rounded-panel p-3">
                            <x-form-input name="name"
                                          id="edit_position_name"
                                          xModel="position.name"
                                          :label="lexicon('label_SectionsPositions_PositionName', 'Position Name')"
                                          :tooltip="lexicon('tooltip_SectionsPositions_PositionNameInput', 'Enter the role or duty title')"
                                          :required="true" />

                            <x-form-input name="capacity"
                                          id="edit_position_capacity"
                                          type="number"
                                          min="1"
                                          max="999"
                                          xModel="position.capacity"
                                          class="text-center"
                                          :label="lexicon('label_SectionsPositions_Capacity', 'Max Capacity')"
                                          :tooltip="lexicon('tooltip_SectionsPositions_CapacityInput', 'Maximum number of active characters that can hold this position')"
                                          :required="true" />
                        </div>

                        <div class="flex flex-row flex-wrap items-center gap-sm bg-panel-sub border border-panel rounded-panel p-3">
                            <input type="hidden" name="is_open" value="0">
                            <input type="checkbox"
                                   id="edit_position_is_open"
                                   name="is_open"
                                   value="1"
                                   x-model="position.is_open"
                                   aria-label="{{ lexicon('label_SectionsPositions_IsOpen', 'Open for Applications') }}"
                                   x-tooltip="'{{ addslashes(lexicon('tooltip_SectionsPositions_IsOpenToggle', 'Allow applicants to apply for vacant slots in this position')) }}'"
                                   class="appearance-none bg-input m-0 w-4 h-4 border-input rounded-input-sm grid place-content-center checkbox-engine cursor-pointer">
                            <label for="edit_position_is_open" class="text-xs font-bold text-input-muted cursor-pointer">
                                {{ lexicon('label_SectionsPositions_IsOpen', 'Open for Applications') }}
                            </label>
                        </div>

                        <div class="flex flex-col gap-xs">
                            <label for="edit_position_desc" class="text-xs font-bold text-input-muted">
                                {{ lexicon('label_SectionsPositions_PositionDesc', 'Description (Optional)') }}
                            </label>
                            <textarea id="edit_position_desc"
                                      x-model="position.description" 
                                      name="description" 
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
            @endcan

        </x-slot:content>
    </x-admin-grid-wrapper>
</div>
@endsection