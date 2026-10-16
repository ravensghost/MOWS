@extends('layouts.app')
@section('title', lexicon('admin_label_ranks', 'Rank Management'))

@php
    $defaultDeptColor =$departmentColor ?? '#ffffffFF';
    $defaultBaseLayer =$baseLayer ?? '#1a1a1aFF';
@endphp

@section('content')
<div x-data="rankManager('{{ $defaultDeptColor }}', '{{$defaultBaseLayer }}')" 
     x-init="initData()"
     @ajax-success.window="refreshDOM_US()">

    <x-admin-grid-wrapper :page="$systemPage">
        <x-slot:content>
            <x-page-header :title="lexicon('admin_label_ranks', 'Rank Management')">
                <x-slot:actions>
                    @can('manage_ranks')
                        <x-button variant="secondary" 
                                  @click="modal = 'rankset-create'" 
                                  aria-label="{{ lexicon('tooltip_ranks_CreateSet', 'Create a new rank set folder') }}"
                                  x-tooltip="'{{ addslashes(lexicon('tooltip_ranks_CreateSet', 'Create a new rank set folder')) }}'">
                            + {{ lexicon('btn_ranks_NewSet', 'New Rank Set') }}
                        </x-button>
                        <x-button variant="success" 
                                  @click="openCreateRank()" 
                                  aria-label="{{ lexicon('tooltip_ranks_CreateRank', 'Create a new rank badge') }}"
                                  x-tooltip="'{{ addslashes(lexicon('tooltip_ranks_CreateRank', 'Create a new rank badge')) }}'">
                            + {{ lexicon('btn_ranks_CreateRank', 'Create Rank') }}
                        </x-button>
                    @endcan
                </x-slot:actions>
            </x-page-header>

            <div x-ref="ranksList" class="flex flex-col gap-lg">
                @forelse($rankSets as $set)
                    <div class="bg-panel border border-panel rounded-panel shadow-panel flex flex-col overflow-hidden">
                        <div class="bg-panel-header text-h border-b border-panel p-panel-header flex flex-row flex-wrap items-center justify-between gap-md">
                            <div class="flex flex-col gap-xs">
                                <span class="text-xl font-bold text-h block">{{ $set->name }}</span>
                                <div class="flex flex-row flex-wrap items-center gap-xs text-xs text-muted">
                                    <span>{{ lexicon('label_ranks_AssignedTo', 'Assigned to:') }}</span>
                                    @if($set->sections &&$set->sections->count() > 0)
                                        @foreach($set->sections as $assignedSection)
                                            <x-badge variant="primary">{{ $assignedSection->name }}</x-badge>
                                        @endforeach
                                    @else
                                        <span class="italic">{{ lexicon('label_common_Unassigned', 'Unassigned') }}</span>
                                    @endif
                                </div>
                            </div>
                            @can('manage_ranks')
                                <div class="flex flex-row flex-wrap gap-md items-center">
                                    <x-button variant="text" 
                                              class="text-accent hover-main text-sm" 
                                              aria-label="{{ lexicon('tooltip_ranks_AssignSections', 'Assign organisational sections to this rank set') }}"
                                              x-tooltip="'{{ addslashes(lexicon('tooltip_ranks_AssignSections', 'Assign organisational sections to this rank set')) }}'"
                                              @click="assignSet = { id: {{ $set->id }}, name: '{{ addslashes($set->name) }}', assignedIds: {{ $set->sections ? $set->sections->pluck('id')->map(fn($id) => (string)$id)->toJson() : '[]' }} }; modal = 'rankset-assign'">
                                        {{ lexicon('btn_ranks_AssignSections', 'Assign Sections') }}
                                    </x-button>
                                    
                                    <x-button variant="text" 
                                              class="text-danger hover-danger text-sm" 
                                              aria-label="{{ lexicon('tooltip_ranks_DeleteSet', 'Delete this rank set and all its ranks') }}"
                                              x-tooltip="'{{ addslashes(lexicon('tooltip_ranks_DeleteSet', 'Delete this rank set and all its ranks')) }}'"
                                              x-data="deleteAction('/admin/rank-sets/{{ $set->id }}', 'DELETE', '{{ addslashes($set->name) }}')" 
                                              @click="confirmDelete()">
                                        {{ lexicon('btn_ranks_DeleteSet', 'Delete Set') }}
                                    </x-button>
                                </div>
                            @endcan
                        </div>
                        
                        <div class="p-panel grid grid-cols-1 md-grid-cols-2 lg-grid-cols-3 gap-md w-full"
                             x-data="sortableManager('/admin/ranks/reorder')">
                            @forelse($set->ranks as $rank)
                                @php
                                    $badgeColor =$set->sections && $set->sections->count() > 0 ? ($set->sections->first()->color ?? '#ffffffFF') : '#ffffffFF';
                                @endphp
                                <div class="bg-panel-sub border border-panel rounded-panel p-4 flex flex-row flex-wrap items-center justify-between gap-md cursor-grab active:cursor-grabbing"
                                     data-id="{{ $rank->id }}">
                                    <div class="flex flex-row flex-wrap items-center gap-md">
                                        <x-rank-badge 
                                            :rankSet="$set->name" 
                                            :baseLayer="$defaultBaseLayer" 
                                            :departmentColor="$badgeColor" 
                                            :midType="$rank->mid_type"
                                            :midImage="$rank->mid_image"
                                            :midCss="$rank->mid_css"
                                            :midColor="$rank->mid_color"
                                            :topType="$rank->top_type"
                                            :topImage="$rank->top_image"
                                            :topCss="$rank->top_css"
                                            :topColor="$rank->top_color"
                                            :tooltip="$rank->name . ' (' .$rank->short_name . ')'"
                                        />
                                        <div class="flex flex-col gap-xs">
                                            <span class="font-bold text-p block">{{ $rank->name }} ({{$rank->short_name }})</span>
                                            <span class="text-xs text-muted block">{{ lexicon('label_common_SortOrder', 'Sort Order') }}: {{ $rank->sort_order }}</span>
                                        </div>
                                    </div>
                                    @can('manage_ranks')
                                        <div class="flex flex-col gap-sm text-right">
                                            <x-button variant="text" 
                                                      class="text-warning hover-warning text-xs justify-end" 
                                                      aria-label="{{ lexicon('tooltip_ranks_EditRank', 'Edit this rank badge') }}"
                                                      x-tooltip="'{{ addslashes(lexicon('tooltip_ranks_EditRank', 'Edit this rank badge')) }}'"
                                                      @click="openEditRank({
                                                          id: {{ $rank->id }},
                                                          name: '{{ addslashes($rank->name) }}',
                                                          short_name: '{{ addslashes($rank->short_name) }}',
                                                          sort_order: {{ $rank->sort_order }},
                                                          description: '{{ addslashes($rank->description ?? '') }}',
                                                          mid_type: '{{ $rank->mid_type }}',
                                                          mid_image: '{{ addslashes($rank->mid_image ?? '') }}',
                                                          mid_css: '{{ addslashes($rank->mid_css ?? '') }}',
                                                          mid_color: '{{ addslashes($rank->mid_color ?? '') }}',
                                                          top_type: '{{ $rank->top_type }}',
                                                          top_image: '{{ addslashes($rank->top_image ?? '') }}',
                                                          top_css: '{{ addslashes($rank->top_css ?? '') }}',
                                                          top_color: '{{ addslashes($rank->top_color ?? '') }}'
                                                      }, { id: {{ $set->id }}, name: '{{ addslashes($set->name) }}' }, '{{$badgeColor }}')">
                                                {{ lexicon('btn_common_Edit', 'Edit') }}
                                            </x-button>
                                            
                                            <x-button variant="text" 
                                                      class="text-danger hover-danger text-xs justify-end" 
                                                      aria-label="{{ lexicon('tooltip_ranks_DeleteRank', 'Delete this rank badge') }}"
                                                      x-tooltip="'{{ addslashes(lexicon('tooltip_ranks_DeleteRank', 'Delete this rank badge')) }}'"
                                                      x-data="deleteAction('/admin/ranks/{{ $rank->id }}', 'DELETE', '{{ addslashes($rank->name) }}')" 
                                                      @click="confirmDelete()">
                                                {{ lexicon('btn_common_Delete', 'Delete') }}
                                            </x-button>
                                        </div>
                                    @endcan
                                </div>
                            @empty
                                <div class="col-span-1 md-col-span-2 lg-col-span-3 text-sm text-muted italic p-2">
                                    <span>{{ lexicon('msg_ranks_NoneInSet', 'No ranks have been added to this set yet.') }}</span>
                                </div>
                            @endforelse
                        </div>
                    </div>
                @empty
                    <div class="bg-panel border border-panel rounded-panel shadow-panel p-6 text-center text-muted italic">
                        <span class="block">{{ lexicon('msg_ranks_NoSetsCreated', 'No rank sets have been created.') }}</span>
                    </div>
                @endforelse
            </div>

            @can('manage_ranks')
                <!-- Create Rank Set Modal -->
                <x-modal show="modal === 'rankset-create'" maxWidth="max-w-lg" onClose="modal = null">
                    <x-slot:top>
                        <span>{{ lexicon('label_ranks_CreateRankSet', 'Create Rank Set') }}</span>
                    </x-slot:top>
                    
                    <form action="/admin/rank-sets" 
                          method="POST" 
                          class="flex flex-col gap-md" 
                          x-data="ajaxForm" 
                          @submit.prevent="submit" 
                          data-loading-title="{{ addslashes(lexicon('msg_ranks_CreatingSet', 'Creating Set...')) }}">
                        @csrf
                        <x-form-input 
                            name="name" 
                            :label="lexicon('label_ranks_SetFolderName', 'Set Folder Name')" 
                            :placeholder="lexicon('msg_ranks_SetFolderPlaceholder', 'e.g. Starfleet_2409')" 
                            :tooltip="lexicon('tooltip_ranks_SetFolderName', 'Must match the folder name inside public/images/ranks/')"
                            :required="true" 
                        />
                        <div class="border-t-actions pt-6 mt-4 flex flex-row flex-wrap items-center justify-end gap-md">
                            <x-button type="button" 
                                      variant="secondary" 
                                      @click="modal = null"
                                      aria-label="{{ lexicon('tooltip_common_Cancel', 'Cancel and close modal') }}"
                                      x-tooltip="'{{ addslashes(lexicon('tooltip_common_Cancel', 'Cancel and close modal')) }}'">
                                {{ lexicon('btn_common_Cancel', 'Cancel') }}
                            </x-button>
                            <x-button type="submit" 
                                      variant="primary"
                                      aria-label="{{ lexicon('tooltip_ranks_SaveSet', 'Save this rank set') }}"
                                      x-tooltip="'{{ addslashes(lexicon('tooltip_ranks_SaveSet', 'Save this rank set')) }}'">
                                {{ lexicon('btn_ranks_SaveSet', 'Save Set') }}
                            </x-button>
                        </div>
                    </form>
                </x-modal>

                <!-- Assign Sections Modal -->
                <x-modal show="modal === 'rankset-assign'" maxWidth="max-w-lg" onClose="modal = null">
                    <x-slot:top>
                        <span>{{ lexicon('label_ranks_AssignSections', 'Assign Sections') }}</span>
                        <span class="text-accent" x-text="' - ' + assignSet.name"></span>
                    </x-slot:top>
                    
                    <form :action="`/admin/rank-sets/${assignSet.id}/assign`" 
                          method="POST" 
                          class="flex flex-col gap-md" 
                          x-data="ajaxForm" 
                          @submit.prevent="submit" 
                          data-loading-title="{{ addslashes(lexicon('msg_ranks_AssigningSections', 'Assigning Sections...')) }}">
                        @csrf
                        <div x-ref="assignSectionsList" class="bg-panel-sub border border-panel rounded-panel p-4 flex flex-col gap-sm max-h-64 overflow-y-auto">
                            @foreach($sections as $section)
                                <div class="flex flex-row flex-wrap items-center gap-sm">
                                    <input type="checkbox" 
                                           name="sections[]" 
                                           id="sec_{{ $section->id }}" 
                                           value="{{ $section->id }}" 
                                           x-model="assignSet.assignedIds" 
                                           class="appearance-none bg-input m-0 w-4 h-4 border-input rounded-input-sm grid place-content-center checkbox-engine">
                                    <label for="sec_{{ $section->id }}" class="text-sm font-bold text-p cursor-pointer">
                                        <span>{{ $section->name }}</span>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                        <div class="border-t-actions pt-6 mt-4 flex flex-row flex-wrap items-center justify-end gap-md">
                            <x-button type="button" 
                                      variant="secondary" 
                                      @click="modal = null"
                                      aria-label="{{ lexicon('tooltip_common_Cancel', 'Cancel and close modal') }}"
                                      x-tooltip="'{{ addslashes(lexicon('tooltip_common_Cancel', 'Cancel and close modal')) }}'">
                                {{ lexicon('btn_common_Cancel', 'Cancel') }}
                            </x-button>
                            <x-button type="submit" 
                                      variant="primary"
                                      aria-label="{{ lexicon('tooltip_ranks_SaveAssignments', 'Save section assignments for this rank set') }}"
                                      x-tooltip="'{{ addslashes(lexicon('tooltip_ranks_SaveAssignments', 'Save section assignments for this rank set')) }}'">
                                {{ lexicon('btn_ranks_SaveAssignments', 'Save Assignments') }}
                            </x-button>
                        </div>
                    </form>
                </x-modal>

                <!-- Single Unified Create / Edit Rank Modal -->
                <x-modal show="modal === 'rank-create' || modal === 'rank-edit'" maxWidth="max-w-5xl" onClose="modal = null">
                    <x-slot:top>
                        <span x-text="modal === 'rank-edit' ? '{{ addslashes(lexicon('label_ranks_EditRankTitle', 'Edit Rank')) }}' : '{{ addslashes(lexicon('label_ranks_CreateRankTitle', 'Create Rank')) }}'"></span>
                        <span class="text-accent" x-text="modal === 'rank-edit' && rankForm.name ? ' - ' + rankForm.name : ''"></span>
                    </x-slot:top>
                    
                    <div class="grid grid-cols-1 lg-grid-cols-12 gap-md w-full items-start">
                        <form :action="modal === 'rank-edit' ? `/admin/ranks/${rankForm.id}` : '/admin/ranks'" 
                              method="POST" 
                              class="lg-col-span-8 flex flex-col gap-md" 
                              x-data="ajaxForm" 
                              @submit.prevent="submit" 
                              :data-loading-title="modal === 'rank-edit' ? '{{ addslashes(lexicon('msg_ranks_UpdatingRank', 'Updating Rank...')) }}' : '{{ addslashes(lexicon('msg_ranks_SavingRank', 'Saving Rank...')) }}'">
                            @csrf
                            <template x-if="modal === 'rank-edit'">
                                <input type="hidden" name="_method" value="PUT">
                            </template>

                            <!-- Rank Set Selection (Visible on Create, Hidden on Edit) -->
                            <div x-show="modal === 'rank-create'" class="flex flex-col gap-xs">
                                <label for="unified_rank_set_id" class="text-xs font-bold text-input-muted">
                                    <span>{{ lexicon('label_ranks_RankSet', 'Rank Set') }}</span>
                                    <span class="text-required">*</span>
                                </label>
                                <select id="unified_rank_set_id"
                                        x-ref="rankSetSelect"
                                        name="rank_set_id" 
                                        x-model="rankForm.set_id" 
                                        @change="onSetChange" 
                                        aria-label="{{ lexicon('label_ranks_RankSet', 'Rank Set') }}"
                                        class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent"
                                        x-tooltip="'{{ addslashes(lexicon('tooltip_ranks_SelectRankSet', 'Select which rank set this rank belongs to')) }}'"
                                        :required="modal === 'rank-create'">
                                    <option value="">{{ lexicon('msg_ranks_SelectSet', 'Select a Set...') }}</option>
                                    @foreach($rankSets as $set)
                                        @php $setColor =$set->sections && $set->sections->count() > 0 ? ($set->sections->first()->color ?? '#ffffffFF') : '#ffffffFF'; @endphp
                                        <option value="{{ $set->id }}" data-name="{{ $set->name }}" data-color="{{ $setColor }}">{{ $set->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <template x-if="modal === 'rank-edit'">
                                <input type="hidden" name="rank_set_id" :value="rankForm.set_id">
                            </template>

                            <!-- Reusable Badge Layer Fields Component -->
                            <x-badge-layer-fields :midShapes="$midShapes" :topShapes="$topShapes" />
                            
                            <!-- Rank Name & Short Name -->
                            <div class="grid grid-cols-1 md-grid-cols-3 gap-md w-full">
                                <div class="md-col-span-2">
                                    <x-form-input 
                                        name="name" 
                                        :label="lexicon('label_ranks_RankName', 'Rank Name')" 
                                        xModel="rankForm.name"
                                        :tooltip="lexicon('tooltip_ranks_RankName', 'Full title of the rank (e.g. Captain)')"
                                        :required="true" 
                                    />
                                </div>
                                <div>
                                    <x-form-input 
                                        name="short_name" 
                                        :label="lexicon('label_ranks_ShortName', 'Short Name')" 
                                        xModel="rankForm.short_name"
                                        :tooltip="lexicon('tooltip_ranks_ShortName', 'Abbreviated rank prefix (e.g. Capt.)')"
                                        :required="true" 
                                    />
                                </div>
                            </div>

                            <!-- Description & Sort Order -->
                            <div class="grid grid-cols-1 md-grid-cols-3 gap-md w-full">
                                <div class="md-col-span-2">
                                    <x-form-input 
                                        name="description" 
                                        :label="lexicon('label_common_Description', 'Description')" 
                                        xModel="rankForm.description"
                                        :tooltip="lexicon('tooltip_ranks_Description', 'Optional description of this rank')"
                                    />
                                </div>
                                <div>
                                    <x-form-input 
                                        name="sort_order" 
                                        type="number" 
                                        :label="lexicon('label_common_SortOrder', 'Sort Order')" 
                                        xModel="rankForm.sort_order"
                                        :tooltip="lexicon('tooltip_ranks_SortOrder', 'Numeric hierarchy order (lower numbers appear first)')"
                                    />
                                </div>
                            </div>

                            <div class="border-t-actions pt-6 mt-4 flex flex-row flex-wrap items-center justify-end gap-md">
                                <x-button type="button" 
                                          variant="secondary" 
                                          @click="modal = null"
                                          aria-label="{{ lexicon('tooltip_common_Cancel', 'Cancel and close modal') }}"
                                          x-tooltip="'{{ addslashes(lexicon('tooltip_common_Cancel', 'Cancel and close modal')) }}'">
                                    {{ lexicon('btn_common_Cancel', 'Cancel') }}
                                </x-button>
                                <x-button type="submit" 
                                          variant="primary"
                                          aria-label="modal === 'rank-edit' ? '{{ lexicon('tooltip_ranks_UpdateRank', 'Update rank configuration') }}' : '{{ lexicon('tooltip_ranks_SaveRank', 'Save rank configuration') }}'"
                                          x-tooltip="modal === 'rank-edit' ? '{{ addslashes(lexicon('tooltip_ranks_UpdateRank', 'Update rank configuration')) }}' : '{{ addslashes(lexicon('tooltip_ranks_SaveRank', 'Save rank configuration')) }}'">
                                    <span x-text="modal === 'rank-edit' ? '{{ addslashes(lexicon('btn_common_Update', 'Update')) }}' : '{{ addslashes(lexicon('btn_ranks_SaveRank', 'Save Rank')) }}'"></span>
                                </x-button>
                            </div>
                        </form>
                    </div>
                </x-modal>
            @endcan

        </x-slot:content>
    </x-admin-grid-wrapper>
</div>
@endsection