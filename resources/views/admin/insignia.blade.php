{{-- resources/views/admin/insignia.blade.php --}}
@extends('layouts.app')

@section('title', lexicon('admin_label_ranks', 'Insignia & Rank Management'))

@php
    $defaultDeptColour = $departmentColour ?? '#ffffffFF';
    $defaultBaseLayer = $baseLayer ?? '#1a1a1aFF';
    $cataloguesList = $rankSets ?? $insigniaCatalogues ?? collect();
    $divisionsList = $sections ?? $divisions ?? collect();
@endphp

@section('content')
<x-grid-wrapper :book="$systemBook">
    <x-slot:content>
        <div x-data="insigniaManager('{{ $defaultDeptColour }}', '{{ $defaultBaseLayer }}')"
             @ajax-success.window="refreshDOM()"
             x-ref="insigniaTableWrapper"
             class="flex flex-col gap-lg">

            {{-- Page Header --}}
            <x-page-header :title="lexicon('admin_label_ranks', 'Insignia & Rank Management')"
                           :subtitle="lexicon('desc_admin_ranks', 'Manage rank insignia catalogues, department colour bindings, and 3-layer rank badges.')"
                           icon="bi-chevron-double-up">
                <x-slot:actions>
                    <x-metadata type="search"
                                name="insignia_search"
                                :bare="true"
                                xModel="searchQuery"
                                :placeholder="lexicon('placeholder_ranks_Search', 'Search ranks or sets...')"
                                :tooltip="lexicon('tooltip_ranks_Search', 'Filter rank insignia by title, abbreviation, set, or synopsis')" />

                    @can('manage_ranks')
                        <x-button variant="secondary"
                                  size="sm"
                                  tag="a"
                                  href="{{ route('admin.insignia.export') }}"
                                  icon="bi-download"
                                  :tooltip="lexicon('tooltip_ranks_Export', 'Download insignia JSON manifest')">
                            {{ lexicon('btn_common_Export', 'Export') }}
                        </x-button>

                        <x-button variant="secondary"
                                  size="sm"
                                  tag="a"
                                  href="{{ route('admin.insignia.template') }}"
                                  icon="bi-file-earmark-code"
                                  :tooltip="lexicon('tooltip_ranks_Template', 'Download blank JSON insignia template')">
                            {{ lexicon('btn_common_DownloadTemplate', 'Template') }}
                        </x-button>

                        <x-button type="button"
                                  variant="secondary"
                                  size="sm"
                                  icon="bi-upload"
                                  :tooltip="lexicon('tooltip_ranks_Import', 'Import insignia from JSON manifest')"
                                  @click="openModal('create', {}, 'import')">
                            {{ lexicon('btn_common_Import', 'Import') }}
                        </x-button>

                        <x-button type="button"
                                  variant="secondary"
                                  size="sm"
                                  icon="bi-folder-plus"
                                  :tooltip="lexicon('tooltip_ranks_CreateSet', 'Create a new insignia catalogue folder')"
                                  @click="openModal('create', {}, 'catalogue')">
                            + {{ lexicon('btn_ranks_NewSet', 'New Rank Set') }}
                        </x-button>

                        <x-button type="button"
                                  variant="success"
                                  size="sm"
                                  icon="bi-plus-lg"
                                  :tooltip="lexicon('tooltip_ranks_CreateRank', 'Create a new rank insignia badge')"
                                  @click="openModal('create', {}, 'insignia')">
                            + {{ lexicon('btn_ranks_CreateRank', 'Create Insignia') }}
                        </x-button>
                    @endcan
                </x-slot:actions>
            </x-page-header>

            {{-- Insignia Catalogues & Sortable Cards --}}
            <div x-ref="ranksList" class="flex flex-col gap-lg">
                @forelse($cataloguesList as $set)
                    <x-tab type="accordion"
                           :title="$set->catalogue_title"
                           icon="bi-collection"
                           :open="true">
                        <x-slot:top>
                            <div class="flex flex-row flex-wrap items-center gap-xs" @click.stop>
                                <span class="text-xs text-muted">{{ lexicon('label_ranks_AssignedTo', 'Assigned to:') }}</span>
                                @if($set->divisions && $set->divisions->count() > 0)
                                    @foreach($set->divisions as $assignedDivision)
                                        <x-badge type="pill" variant="primary">{{ $assignedDivision->division_title }}</x-badge>
                                    @endforeach
                                @else
                                    <span class="text-xs text-muted font-italic">{{ lexicon('label_common_Unassigned', 'Unassigned') }}</span>
                                @endif

                                @can('manage_ranks')
                                    <x-button type="button"
                                              variant="secondary"
                                              size="sm"
                                              icon="bi-diagram-3"
                                              :tooltip="lexicon('tooltip_ranks_AssignSections', 'Assign organisational divisions to this insignia set')"
                                              @click="openModal('edit', {
                                                  id: {{ $set->id }},
                                                  catalogue_title: '{{ addslashes($set->catalogue_title) }}',
                                                  assignedIds: {{ $set->divisions ? $set->divisions->pluck('id')->map(fn ($id) => (string) $id)->toJson() : '[]' }},
                                                  is_visible: {{ $set->is_visible ? 'true' : 'false' }}
                                              }, 'catalogue'); modals.catalogue.action = '/admin/insignia/catalogues/{{ $set->id }}/assign'; modals.catalogue.method = 'POST'">
                                        {{ lexicon('btn_ranks_AssignSections', 'Assign Divisions') }}
                                    </x-button>

                                    <x-button type="button"
                                              variant="danger"
                                              size="sm"
                                              icon="bi-trash"
                                              :tooltip="lexicon('tooltip_ranks_DeleteSet', 'Delete this insignia set and all its ranks')"
                                              x-data="deleteAction('/admin/insignia/catalogues/{{ $set->id }}', 'DELETE', '{{ addslashes($set->catalogue_title) }}')"
                                              @click="confirmDelete()">
                                        {{ lexicon('btn_ranks_DeleteSet', 'Delete Set') }}
                                    </x-button>
                                @endcan
                            </div>
                        </x-slot:top>

                        <div class="grid grid-cols-1 md-grid-cols-2 lg-grid-cols-3 gap-md w-full"
                             x-data="sortableManager('/admin/insignia/reorder')">
                            @forelse($set->insignia as $rank)
                                @php
                                    $badgeColour = $set->divisions && $set->divisions->count() > 0
                                        ? ($set->divisions->first()->division_base_colour ?? $defaultDeptColour)
                                        : $defaultDeptColour;
                                    $rankSynopsis = $rank->insignia_synopsis ?? $rank->insignia_description ?? '';
                                @endphp
                                <div class="bg-panel-sub border-panel border-rounded p-4 flex flex-row flex-wrap items-center justify-between gap-md cursor-grab"
                                     data-id="{{ $rank->id }}"
                                     data-search-item
                                     data-insignia-item
                                     data-insignia_title="{{ $rank->insignia_title }}"
                                     data-abbreviated_title="{{ $rank->abbreviated_title }}"
                                     data-catalogue_title="{{ $set->catalogue_title }}"
                                     data-insignia_synopsis="{{ $rankSynopsis }}"
                                     x-show="isItemVisible({{ $rank->id }})">
                                    <div class="flex flex-row flex-wrap items-center gap-md">
                                        <x-badge type="insignia"
                                                 :rank="$rank"
                                                 :rankSet="$set->catalogue_title"
                                                 :baseLayer="$defaultBaseLayer"
                                                 :departmentColour="$badgeColour"
                                                 :midLayerMode="$rank->mid_layer_mode"
                                                 :midLayerAsset="$rank->mid_layer_asset"
                                                 :midLayerModifier="$rank->mid_layer_modifier"
                                                 :midLayerHue="$rank->mid_layer_hue"
                                                 :topLayerMode="$rank->top_layer_mode"
                                                 :topLayerAsset="$rank->top_layer_asset"
                                                 :topLayerModifier="$rank->top_layer_modifier"
                                                 :topLayerHue="$rank->top_layer_hue"
                                                 :tooltip="$rank->insignia_title . ' (' . $rank->abbreviated_title . ')'" />
                                        <div class="flex flex-col gap-xs">
                                            <span class="font-bold text-h block">{{ $rank->insignia_title }} ({{ $rank->abbreviated_title }})</span>
                                            <span class="text-xs text-muted block">{{ lexicon('label_common_SortOrder', 'Sort Order') }}: {{ $rank->insignia_sort_order }}</span>
                                        </div>
                                    </div>

                                    @can('manage_ranks')
                                        <div class="flex flex-row items-center gap-xs">
                                            <x-button type="button"
                                                      variant="ghost"
                                                      size="sm"
                                                      icon="bi-pencil"
                                                      :tooltip="lexicon('tooltip_ranks_EditRank', 'Edit this rank insignia')"
                                                      @click="openModal('edit', {{ Js::from([
                                                          'id' => $rank->id,
                                                          'insignia_catalogue_id' => $set->id,
                                                          'catalogue_title' => $set->catalogue_title,
                                                          'set_color' => $badgeColour,
                                                          'insignia_title' => $rank->insignia_title,
                                                          'abbreviated_title' => $rank->abbreviated_title,
                                                          'insignia_sort_order' => $rank->insignia_sort_order,
                                                          'insignia_synopsis' => $rankSynopsis,
                                                          'is_visible' => (bool) $rank->is_visible,
                                                          'mid_layer_mode' => $rank->mid_layer_mode,
                                                          'mid_layer_asset' => $rank->mid_layer_asset ?? '',
                                                          'mid_layer_modifier' => $rank->mid_layer_modifier ?? '',
                                                          'mid_layer_hue' => $rank->mid_layer_hue ?? $defaultBaseLayer,
                                                          'top_layer_mode' => $rank->top_layer_mode,
                                                          'top_layer_asset' => $rank->top_layer_asset ?? '',
                                                          'top_layer_modifier' => $rank->top_layer_modifier ?? '',
                                                          'top_layer_hue' => $rank->top_layer_hue ?? '#ffffffFF',
                                                      ]) }}, 'insignia')">
                                                {{ lexicon('btn_common_Edit', 'Edit') }}
                                            </x-button>

                                            <x-button type="button"
                                                      variant="danger"
                                                      size="sm"
                                                      icon="bi-trash"
                                                      :tooltip="lexicon('tooltip_ranks_DeleteRank', 'Delete this rank insignia')"
                                                      x-data="deleteAction('/admin/insignia/{{ $rank->id }}', 'DELETE', '{{ addslashes($rank->insignia_title) }}')"
                                                      @click="confirmDelete()" />
                                        </div>
                                    @endcan
                                </div>
                            @empty
                                <div class="col-span-full text-sm text-muted font-italic p-2">
                                    <span>{{ lexicon('msg_ranks_NoneInSet', 'No rank insignia have been added to this catalogue yet.') }}</span>
                                </div>
                            @endforelse
                        </div>
                    </x-tab>
                @empty
                    <x-tab type="panel">
                        <p class="text-center text-muted font-italic m-0">
                            {{ lexicon('msg_ranks_NoSetsCreated', 'No insignia catalogues have been created.') }}
                        </p>
                    </x-tab>
                @endforelse
            </div>

            @can('manage_ranks')
                {{-- MODAL 1: CREATE RANK SET OR ASSIGN DIVISIONS --}}
                <x-modal show="modals.catalogue.open" onClose="closeModal('catalogue')" maxWidth="max-w-lg">
                    <x-slot:top>
                        <span x-text="modals.catalogue.mode === 'edit'
                            ? '{{ addslashes(lexicon('label_ranks_AssignSections', 'Assign Divisions')) }} — ' + modals.catalogue.catalogue_title
                            : '{{ addslashes(lexicon('label_ranks_CreateRankSet', 'Create Insignia Catalogue')) }}'"></span>
                    </x-slot:top>

                    <x-meta type="modal" xAction="modals.catalogue.action" xMethod="modals.catalogue.method">
                        <div x-show="modals.catalogue.mode === 'create'">
                            <x-metadata type="text"
                                        name="catalogue_title"
                                        xModel="modals.catalogue.catalogue_title"
                                        :label="lexicon('label_ranks_SetFolderName', 'Catalogue Folder Name')"
                                        :placeholder="lexicon('msg_ranks_SetFolderPlaceholder', 'e.g. Starfleet_2409')"
                                        :tooltip="lexicon('tooltip_ranks_SetFolderName', 'Must match the folder inside public/images/ranks/')" />
                        </div>

                        <div x-show="modals.catalogue.mode === 'edit'"
                             x-ref="assignSectionsList"
                             class="bg-panel-sub border-panel border-rounded p-4 flex flex-col gap-sm max-h-64 overflow-y-auto">
                            @foreach($divisionsList as $section)
                                <div class="flex flex-row items-center gap-sm">
                                    <input type="checkbox"
                                           name="divisions[]"
                                           id="sec_{{ $section->id }}"
                                           value="{{ $section->id }}"
                                           x-model="modals.catalogue.assignedIds"
                                           class="w-4 h-4 m-0 bg-input border-input border-rounded-sm grid items-center justify-center checkbox-engine cursor-pointer">
                                    <label for="sec_{{ $section->id }}" class="text-sm font-bold text-p cursor-pointer">
                                        {{ $section->division_title }}
                                    </label>
                                </div>
                            @endforeach
                        </div>

                        <x-slot:footer>
                            <x-button type="button"
                                      variant="secondary"
                                      :tooltip="lexicon('tooltip_common_Cancel', 'Cancel and close modal')"
                                      @click="closeModal('catalogue')">
                                {{ lexicon('btn_common_Cancel', 'Cancel') }}
                            </x-button>

                            <x-button type="submit"
                                      variant="primary"
                                      icon="bi-save"
                                      :tooltip="lexicon('tooltip_common_Save', 'Save catalogue changes')">
                                {{ lexicon('btn_common_Save', 'Save Changes') }}
                            </x-button>
                        </x-slot:footer>
                    </x-meta>
                </x-modal>

                {{-- MODAL 2: UNIFIED CREATE / EDIT RANK INSIGNIA --}}
                <x-modal show="modals.insignia.open" onClose="closeModal('insignia')" maxWidth="4xl">
                    <x-slot:top>
                        <span x-text="modals.insignia.mode === 'edit'
                            ? '{{ addslashes(lexicon('label_ranks_EditRankTitle', 'Edit Insignia')) }}: ' + (modals.insignia.insignia_title || '')
                            : '{{ addslashes(lexicon('label_ranks_CreateRankTitle', 'Create Insignia')) }}'"></span>
                    </x-slot:top>

                    <x-meta type="modal" xAction="modals.insignia.action" xMethod="modals.insignia.method">
                        <div class="grid grid-cols-1 lg-grid-cols-12 gap-md items-start">
                            <div class="lg-col-span-8 flex flex-col gap-md">
                                <div x-show="modals.insignia.mode === 'create'" class="flex flex-col gap-xs">
                                    <label for="unified_insignia_catalogue_id" class="text-xs font-bold text-input-muted uppercase text-wider">
                                        <span>{{ lexicon('label_ranks_RankSet', 'Insignia Catalogue') }}</span>
                                        <span class="text-required">*</span>
                                    </label>
                                    <select id="unified_insignia_catalogue_id"
                                            x-ref="rankSetSelect"
                                            name="insignia_catalogue_id"
                                            x-model="modals.insignia.insignia_catalogue_id"
                                            @change="onSetChange($event)"
                                            class="w-full h-input p-input bg-input text-input border-input border-rounded text-sm transition-colors hover-border-accent">
                                        <option value="">{{ lexicon('msg_ranks_SelectSet', 'Select a Catalogue...') }}</option>
                                        @foreach($cataloguesList as $setOpt)
                                            @php
                                                $setColour = $setOpt->divisions && $setOpt->divisions->count() > 0
                                                    ? ($setOpt->divisions->first()->division_base_colour ?? $defaultDeptColour)
                                                    : $defaultDeptColour;
                                            @endphp
                                            <option value="{{ $setOpt->id }}"
                                                    data-catalogue-title="{{ $setOpt->catalogue_title }}"
                                                    data-colour="{{ $setColour }}"
                                                    data-color="{{ $setColour }}">{{ $setOpt->catalogue_title }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <template x-if="modals.insignia.mode === 'edit'">
                                    <input type="hidden" name="insignia_catalogue_id" :value="modals.insignia.insignia_catalogue_id">
                                </template>

                                {{-- Reusable Polymorphic 3-Layer Component bound to modals.insignia --}}
                                <x-metadata type="layer"
                                            modalKey="insignia"
                                            :midShapes="$midShapes ?? []"
                                            :topShapes="$topShapes ?? []" />

                                <div class="grid grid-cols-1 md-grid-cols-3 gap-md">
                                    <div class="md-col-span-2">
                                        <x-metadata type="text"
                                                    name="insignia_title"
                                                    xModel="modals.insignia.insignia_title"
                                                    :label="lexicon('label_ranks_RankName', 'Insignia Title')"
                                                    :tooltip="lexicon('tooltip_ranks_RankName', 'Full title of the rank (e.g. Captain)')"
                                                    :required="true" />
                                    </div>
                                    <div>
                                        <x-metadata type="text"
                                                    name="abbreviated_title"
                                                    xModel="modals.insignia.abbreviated_title"
                                                    :label="lexicon('label_ranks_ShortName', 'Abbreviation')"
                                                    :tooltip="lexicon('tooltip_ranks_ShortName', 'Abbreviated rank prefix (e.g. Capt.)')"
                                                    :required="true" />
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md-grid-cols-3 gap-md">
                                    <div class="md-col-span-2">
                                        <x-metadata type="text"
                                                    name="insignia_synopsis"
                                                    xModel="modals.insignia.insignia_synopsis"
                                                    :label="lexicon('label_common_Synopsis', 'Synopsis')"
                                                    :tooltip="lexicon('tooltip_ranks_Description', 'Optional synopsis of this rank insignia')" />
                                    </div>
                                    <div>
                                        <x-metadata type="number"
                                                    name="insignia_sort_order"
                                                    xModel="modals.insignia.insignia_sort_order"
                                                    :label="lexicon('label_common_SortOrder', 'Sort Order')"
                                                    :tooltip="lexicon('tooltip_ranks_SortOrder', 'Numeric hierarchy order (lower numbers appear first)')" />
                                    </div>
                                </div>

                                <x-metadata type="checkbox"
                                            name="is_visible"
                                            id="rank_is_visible"
                                            value="1"
                                            xModel="modals.insignia.is_visible"
                                            :label="lexicon('label_common_ShowOnSite', 'Show on Site (Visible)')"
                                            :tooltip="lexicon('tooltip_ranks_Visible', 'Toggle whether this rank insignia is visible on the site')" />
                            </div>

                            <div class="lg-col-span-4 flex flex-col gap-md">
                                <x-preview type="insignia"
                                           modalKey="insignia"
                                           :baseLayer="$defaultBaseLayer"
                                           :departmentColour="$defaultDeptColour" />
                            </div>
                        </div>

                        <x-slot:footer>
                            <x-button type="button"
                                      variant="secondary"
                                      :tooltip="lexicon('tooltip_common_Cancel', 'Cancel and close modal')"
                                      @click="closeModal('insignia')">
                                {{ lexicon('btn_common_Cancel', 'Cancel') }}
                            </x-button>

                            <x-button type="submit"
                                      variant="primary"
                                      icon="bi-save"
                                      :tooltip="lexicon('tooltip_ranks_SaveRank', 'Save rank insignia configuration')">
                                <span x-text="modals.insignia.mode === 'edit'
                                    ? '{{ addslashes(lexicon('btn_common_Update', 'Update Insignia')) }}'
                                    : '{{ addslashes(lexicon('btn_ranks_SaveRank', 'Save Insignia')) }}'"></span>
                            </x-button>
                        </x-slot:footer>
                    </x-meta>
                </x-modal>

                {{-- MODAL 3: IMPORT INSIGNIA MANIFEST --}}
                <x-modal show="modals.import.open" onClose="closeModal('import')" maxWidth="max-w-lg">
                    <x-slot:top>
                        <div class="flex flex-row items-center gap-xs">
                            <i class="bi bi-upload text-accent"></i>
                            <span>{{ lexicon('label_ranks_ImportModal', 'Import Insignia Manifest') }}</span>
                        </div>
                    </x-slot:top>

                    <div class="flex flex-col gap-md" @upload-completed.window="handleImportSuccess($event.detail)">
                        <x-metadata type="select"
                                    name="import_mode"
                                    xModel="modals.import.importMode"
                                    :label="lexicon('label_common_ImportMode', 'Import Mode')"
                                    :tooltip="lexicon('tooltip_common_ImportMode', 'Choose whether to merge or replace existing insignia sets')">
                            <option value="merge">{{ lexicon('label_common_MergeUpsert', 'Merge / Upsert (Preserves active records)') }}</option>
                            <option value="replace">{{ lexicon('label_common_ReplaceAll', 'Replace All (Clears existing rank sets)') }}</option>
                        </x-metadata>

                        <x-dropzone :url="route('admin.insignia.import')"
                                    id="ranks-import-dropzone"
                                    :maxFiles="1"
                                    acceptedFiles="application/json,.json,.txt"
                                    :title="lexicon('label_ranks_DropzoneTitle', 'Drop insignia manifest (.json) here')" />
                    </div>

                    <x-slot:bottom>
                        <x-button type="button"
                                  variant="secondary"
                                  @click="closeModal('import')">
                            {{ lexicon('btn_common_Cancel', 'Cancel') }}
                        </x-button>
                    </x-slot:bottom>
                </x-modal>
            @endcan

        </div>
    </x-slot:content>
</x-grid-wrapper>
@endsection