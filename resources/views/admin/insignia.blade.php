@extends('layouts.app')
@section('title', lexicon('admin_label_ranks', 'Rank Management'))

@php
    $defaultDeptColor =$departmentColor ?? '#ffffffFF';
    $defaultBaseLayer =$baseLayer ?? '#1a1a1aFF';
@endphp

@section('content')
<div x-data="insigniaManager('{{ $defaultDeptColor }}', '{{$defaultBaseLayer }}')" 
     x-init="initData()"
     @ajax-success.window="refreshDOM_US()">

    <x-admin-grid-wrapper :page="$systemPage">
        <x-slot:content>
            <x-page-header :title="lexicon('admin_label_ranks', 'Rank Management')">
                <x-slot:actions>
                    @can('manage_ranks')
                        <div class="flex flex-row flex-wrap items-center gap-sm">
                            <x-button variant="secondary" 
                                      tag="a"
                                      href="{{ route('admin.ranks.export') }}"
                                      x-tooltip="'{{ addslashes(lexicon('tooltip_ranks_Export', 'Download ranks JSON manifest')) }}'">
                                <i class="bi bi-download"></i>
                                <span>{{ lexicon('btn_common_Export', 'Export Ranks') }}</span>
                            </x-button>

                            <x-button variant="secondary" 
                                      tag="a"
                                      href="{{ route('admin.ranks.template') }}"
                                      x-tooltip="'{{ addslashes(lexicon('tooltip_ranks_Template', 'Download blank JSON rank template')) }}'">
                                <i class="bi bi-file-earmark-code"></i>
                                <span>{{ lexicon('btn_common_DownloadTemplate', 'Download Blank Template') }}</span>
                            </x-button>

                            <x-button variant="secondary" 
                                      @click="importModalOpen = true"
                                      x-tooltip="'{{ addslashes(lexicon('tooltip_ranks_Import', 'Import ranks from JSON manifest')) }}'">
                                <i class="bi bi-upload"></i>
                                <span>{{ lexicon('btn_common_Import', 'Import Ranks') }}</span>
                            </x-button>

                            <x-button variant="secondary" 
                                      @click="modal = 'rankset-create'" 
                                      aria-label="{{ lexicon('tooltip_ranks_CreateSet', 'Create a new rank set folder') }}"
                                      x-tooltip="'{{ addslashes(lexicon('tooltip_ranks_CreateSet', 'Create a new rank set folder')) }}'">
                                + {{ lexicon('btn_ranks_NewSet', 'New Rank Set') }}
                            </x-button>
                            <x-button variant="success" 
                                      @click="openModal('create', {}, 'insignia')" 
                                      aria-label="{{ lexicon('tooltip_ranks_CreateRank', 'Create a new rank badge') }}"
                                      x-tooltip="'{{ addslashes(lexicon('tooltip_ranks_CreateRank', 'Create a new rank badge')) }}'">
                                + {{ lexicon('btn_ranks_CreateRank', 'Create Rank') }}
                            </x-button>
                        </div>

                        {{-- Import Modal --}}
                        <x-modal show="importModalOpen" maxWidth="max-w-xl">
                            <x-slot:top>
                                <div class="flex flex-row items-center gap-xs">
                                    <i class="bi bi-upload text-accent"></i>
                                    <span>{{ lexicon('label_ranks_ImportModal', 'Import Ranks Manifest') }}</span>
                                </div>
                            </x-slot:top>

                            <div class="flex flex-col gap-md" @upload-completed.window="handleImportSuccess($event.detail)">
                                <div class="flex flex-col gap-xs">
                                    <label class="text-sm font-bold text-h">{{ lexicon('label_common_ImportMode', 'Import Mode') }}</label>
                                    <select x-model="importMode" class="lumi-input">
                                        <option value="merge">{{ lexicon('label_common_MergeUpsert', 'Merge / Upsert (Preserves active records)') }}</option>
                                        <option value="replace">{{ lexicon('label_common_ReplaceAll', 'Replace All (Clears existing rank sets)') }}</option>
                                    </select>
                                </div>

                                <div class="flex flex-col gap-xs">
                                    <label class="text-sm font-bold text-h">{{ lexicon('label_common_UploadJson', 'Upload JSON Manifest') }}</label>
                                    <x-dropzone :url="route('admin.ranks.import')"
                                                id="ranks-import-dropzone"
                                                :maxFiles="1"
                                                acceptedFiles="application/json,.json,.txt"
                                                :title="lexicon('label_ranks_DropzoneTitle', 'Drop ranks manifest (.json) here')" />
                                </div>
                            </div>

                            <x-slot:bottom>
                                <x-button variant="secondary" @click="importModalOpen = false">
                                    {{ lexicon('btn_common_Cancel', 'Cancel') }}
                                </x-button>
                            </x-slot:bottom>
                        </x-modal>
                    @endcan
                </x-slot:actions>
            </x-page-header>

            <div x-ref="ranksList" class="flex flex-col gap-lg">
                @forelse($rankSets as$set)
                    <div class="bg-panel border border-panel rounded-panel shadow-panel flex flex-col overflow-hidden">
                        <div class="bg-panel-header text-h border-b border-panel p-panel-header flex flex-row flex-wrap items-center justify-between gap-md">
                            <div class="flex flex-col gap-xs">
                                <span class="text-xl font-bold text-h block">{{ $set->catalogue_title }}</span>
                                <div class="flex flex-row flex-wrap items-center gap-xs text-xs text-muted">
                                    <span>{{ lexicon('label_ranks_AssignedTo', 'Assigned to:') }}</span>
                                    @if($set->divisions &&$set->divisions->count() > 0)
                                        @foreach($set->divisions as$assignedDivision)
                                            <x-badge variant="primary">{{ $assignedDivision->division_title }}</x-badge>
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
                                              @click="assignSet = { id: {{ $set->id }}, catalogue_title: '{{ addslashes($set->catalogue_title) }}', assignedIds: {{ $set->divisions ? $set->divisions->pluck('id')->map(fn($id) => (string)$id)->toJson() : '[]' }} }; modal = 'rankset-assign'">
                                        {{ lexicon('btn_ranks_AssignSections', 'Assign Sections') }}
                                    </x-button>
                                    
                                    <x-button variant="text" 
                                              class="text-danger hover-danger text-sm" 
                                              aria-label="{{ lexicon('tooltip_ranks_DeleteSet', 'Delete this rank set and all its ranks') }}"
                                              x-tooltip="'{{ addslashes(lexicon('tooltip_ranks_DeleteSet', 'Delete this rank set and all its ranks')) }}'"
                                              x-data="deleteAction('/admin/rank-sets/{{ $set->id }}', 'DELETE', '{{ addslashes($set->catalogue_title) }}')" 
                                              @click="confirmDelete()">
                                        {{ lexicon('btn_ranks_DeleteSet', 'Delete Set') }}
                                    </x-button>
                                </div>
                            @endcan
                        </div>
                        
                        <div class="p-panel grid grid-cols-1 md-grid-cols-2 lg-grid-cols-3 gap-md w-full"
                             x-data="sortableManager('/admin/ranks/reorder')">
                            @forelse($set->insignia as$rank)
                                @php
                                    $badgeColor =$set->divisions && $set->divisions->count() > 0 ? ($set->divisions->first()->division_hue ?? '#ffffffFF') : '#ffffffFF';
                                @endphp
                                <div class="bg-panel-sub border border-panel rounded-panel p-4 flex flex-row flex-wrap items-center justify-between gap-md cursor-grab active:cursor-grabbing"
                                     data-id="{{ $rank->id }}">
                                    <div class="flex flex-row flex-wrap items-center gap-md">
                                        <x-insignia-badge 
                                            :rankSet="$set->catalogue_title" 
                                            :baseLayer="$defaultBaseLayer" 
                                            :departmentColor="$badgeColor" 
                                            :midLayerMode="$rank->mid_layer_mode"
                                            :midLayerAsset="$rank->mid_layer_asset"
                                            :midLayerModifier="$rank->mid_layer_modifier"
                                            :midLayerHue="$rank->mid_layer_hue"
                                            :topLayerMode="$rank->top_layer_mode"
                                            :topLayerAsset="$rank->top_layer_asset"
                                            :topLayerModifier="$rank->top_layer_modifier"
                                            :topLayerHue="$rank->top_layer_hue"
                                            :tooltip="$rank->insignia_title . ' (' .$rank->abbreviated_title . ')'"
                                        />
                                        <div class="flex flex-col gap-xs">
                                            <span class="font-bold text-p block">{{ $rank->insignia_title }} ({{$rank->abbreviated_title }})</span>
                                            <span class="text-xs text-muted block">{{ lexicon('label_common_SortOrder', 'Sort Order') }}: {{ $rank->insignia_sort_order }}</span>
                                        </div>
                                    </div>
                                    @can('manage_ranks')
                                        <div class="flex flex-col gap-sm text-right">
                                            <x-button variant="text" 
                                                      class="text-warning hover-warning text-xs justify-end" 
                                                      aria-label="{{ lexicon('tooltip_ranks_EditRank', 'Edit this rank badge') }}"
                                                      x-tooltip="'{{ addslashes(lexicon('tooltip_ranks_EditRank', 'Edit this rank badge')) }}'"
                                                      @click="openModal('edit', {
                                                          id: {{ $rank->id }},
                                                          insignia_catalogue_id: {{ $set->id }},
                                                          catalogue_title: '{{ addslashes($set->catalogue_title) }}',
                                                          set_color: '{{ $badgeColor }}',
                                                          insignia_title: '{{ addslashes($rank->insignia_title) }}',
                                                          abbreviated_title: '{{ addslashes($rank->abbreviated_title) }}',
                                                          insignia_sort_order: {{ $rank->insignia_sort_order }},
                                                          insignia_description: '{{ addslashes($rank->insignia_description ?? '') }}',
                                                          is_visible: {{ $rank->is_visible ? 'true' : 'false' }},
                                                          mid_layer_mode: '{{ $rank->mid_layer_mode }}',
                                                          mid_layer_asset: '{{ addslashes($rank->mid_layer_asset ?? '') }}',
                                                          mid_layer_modifier: '{{ addslashes($rank->mid_layer_modifier ?? '') }}',
                                                          mid_layer_hue: '{{ addslashes($rank->mid_layer_hue ?? '') }}',
                                                          top_layer_mode: '{{ $rank->top_layer_mode }}',
                                                          top_layer_asset: '{{ addslashes($rank->top_layer_asset ?? '') }}',
                                                          top_layer_modifier: '{{ addslashes($rank->top_layer_modifier ?? '') }}',
                                                          top_layer_hue: '{{ addslashes($rank->top_layer_hue ?? '') }}'
                                                      }, 'insignia')">
                                                {{ lexicon('btn_common_Edit', 'Edit') }}
                                            </x-button>
                                            
                                            <x-button variant="text" 
                                                      class="text-danger hover-danger text-xs justify-end" 
                                                      aria-label="{{ lexicon('tooltip_ranks_DeleteRank', 'Delete this rank badge') }}"
                                                      x-tooltip="'{{ addslashes(lexicon('tooltip_ranks_DeleteRank', 'Delete this rank badge')) }}'"
                                                      x-data="deleteAction('/admin/ranks/{{ $rank->id }}', 'DELETE', '{{ addslashes($rank->insignia_title) }}')" 
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
                            name="catalogue_title" 
                            :label="lexicon('label_ranks_SetFolderName', 'Set Folder Name')" 
                            :placeholder="lexicon('msg_ranks_SetFolderPlaceholder', 'e.g. Starfleet_2409')" 
                            :tooltip="lexicon('tooltip_ranks_SetFolderName', 'Must match the folder inside public/images/ranks/')"
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
                        <span class="text-accent" x-text="' - ' + assignSet.catalogue_title"></span>
                    </x-slot:top>
                    
                    <form :action="`/admin/rank-sets/${assignSet.id}/assign`" 
                          method="POST" 
                          class="flex flex-col gap-md" 
                          x-data="ajaxForm" 
                          @submit.prevent="submit" 
                          data-loading-title="{{ addslashes(lexicon('msg_ranks_AssigningSections', 'Assigning Sections...')) }}">
                        @csrf
                        <div x-ref="assignSectionsList" class="bg-panel-sub border border-panel rounded-panel p-4 flex flex-col gap-sm max-h-64 overflow-y-auto">
                            @foreach($sections as$section)
                                <div class="flex flex-row flex-wrap items-center gap-sm">
                                    <input type="checkbox" 
                                           name="divisions[]" 
                                           id="sec_{{ $section->id }}" 
                                           value="{{ $section->id }}" 
                                           x-model="assignSet.assignedIds" 
                                           class="appearance-none bg-input m-0 w-4 h-4 border-input rounded-input-sm grid place-content-center checkbox-engine">
                                    <label for="sec_{{ $section->id }}" class="text-sm font-bold text-p cursor-pointer">
                                        <span>{{ $section->division_title }}</span>
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
                        <span class="text-accent" x-text="modal === 'rank-edit' && modals.insignia.insignia_title ? ' - ' + modals.insignia.insignia_title : ''"></span>
                    </x-slot:top>
                    
                    <div class="grid grid-cols-1 lg-grid-cols-12 gap-md w-full items-start">
                        <form :action="modal === 'rank-edit' ? `/admin/ranks/${modals.insignia.id}` : '/admin/ranks'" 
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
                                <label for="unified_insignia_catalogue_id" class="text-xs font-bold text-input-muted">
                                    <span>{{ lexicon('label_ranks_RankSet', 'Rank Set') }}</span>
                                    <span class="text-required">*</span>
                                </label>
                                <select id="unified_insignia_catalogue_id"
                                        x-ref="rankSetSelect"
                                        name="insignia_catalogue_id" 
                                        x-model="modals.insignia.insignia_catalogue_id" 
                                        @change="onSetChange" 
                                        aria-label="{{ lexicon('label_ranks_RankSet', 'Rank Set') }}"
                                        class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent"
                                        x-tooltip="'{{ addslashes(lexicon('tooltip_ranks_SelectRankSet', 'Select which rank set this rank belongs to')) }}'"
                                        :required="modal === 'rank-create'">
                                    <option value="">{{ lexicon('msg_ranks_SelectSet', 'Select a Set...') }}</option>
                                    @foreach($rankSets as$set)
                                        @php $setColor =$set->divisions && $set->divisions->count() > 0 ? ($set->divisions->first()->division_hue ?? '#ffffffFF') : '#ffffffFF'; @endphp
                                        <option value="{{ $set->id }}" data-catalogue-title="{{ $set->catalogue_title }}" data-color="{{ $setColor }}">{{ $set->catalogue_title }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <template x-if="modal === 'rank-edit'">
                                <input type="hidden" name="insignia_catalogue_id" :value="modals.insignia.insignia_catalogue_id">
                            </template>

                            <!-- Reusable Badge Layer Fields Component -->
                            <x-insignia-layer :midShapes="$midShapes" :topShapes="$topShapes" />
                            
                            <!-- Rank Title & Abbreviated Title -->
                            <div class="grid grid-cols-1 md-grid-cols-3 gap-md w-full">
                                <div class="md-col-span-2">
                                    <x-form-input 
                                        name="insignia_title" 
                                        :label="lexicon('label_ranks_RankName', 'Rank Name')" 
                                        xModel="modals.insignia.insignia_title"
                                        :tooltip="lexicon('tooltip_ranks_RankName', 'Full title of the rank (e.g. Captain)')"
                                        :required="true" 
                                    />
                                </div>
                                <div>
                                    <x-form-input 
                                        name="abbreviated_title" 
                                        :label="lexicon('label_ranks_ShortName', 'Short Name')" 
                                        xModel="modals.insignia.abbreviated_title"
                                        :tooltip="lexicon('tooltip_ranks_ShortName', 'Abbreviated rank prefix (e.g. Capt.)')"
                                        :required="true" 
                                    />
                                </div>
                            </div>

                            <!-- Insignia Description & Sort Order -->
                            <div class="grid grid-cols-1 md-grid-cols-3 gap-md w-full">
                                <div class="md-col-span-2">
                                    <x-form-input 
                                        name="insignia_description" 
                                        :label="lexicon('label_common_Description', 'Description')" 
                                        xModel="modals.insignia.insignia_description"
                                        :tooltip="lexicon('tooltip_ranks_Description', 'Optional description of this rank')"
                                    />
                                </div>
                                <div>
                                    <x-form-input 
                                        name="insignia_sort_order" 
                                        type="number" 
                                        :label="lexicon('label_common_SortOrder', 'Sort Order')" 
                                        xModel="modals.insignia.insignia_sort_order"
                                        :tooltip="lexicon('tooltip_ranks_SortOrder', 'Numeric hierarchy order (lower numbers appear first)')"
                                    />
                                </div>
                                <!-- Visibility Toggle Field -->
                                <div class="flex flex-row items-center gap-sm mt-2">
                                    <input type="checkbox" 
                                        name="is_visible" 
                                        id="rank_is_visible" 
                                        value="1" 
                                        x-model="modals.insignia.is_visible" 
                                        class="appearance-none bg-input m-0 w-4 h-4 border-input rounded-input-sm grid place-content-center checkbox-engine">
                                    <label for="rank_is_visible" class="text-sm font-bold text-p cursor-pointer">
                                        <span>{{ lexicon('label_common_ShowOnSite', 'Show on Site (Visible)') }}</span>
                                    </label>
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

                        <div class="lg-col-span-4 flex flex-col gap-md">
                            <x-insignia-preview :baseLayer="$defaultBaseLayer" :departmentColor="$defaultDeptColor" />
                        </div>
                    </div>
                </x-modal>
            @endcan

        </x-slot:content>
    </x-admin-grid-wrapper>
</div>
@endsection