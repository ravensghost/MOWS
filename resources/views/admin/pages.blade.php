@extends('layouts.app')

@section('title', lexicon('admin_label_PageBuilder', 'Page Builder'))

@section('content')
@php
    $widgetRegistry = cache('mows_widget_manifest', []);
@endphp

<div x-data="pageBuilderManager({{ json_encode($layouts) }}, {{ json_encode($availableWidgets) }}, {{ json_encode($widgetRegistry) }})" @ajax-success.window="refreshDOM_US()">
    <x-admin-grid-wrapper :page="$systemPage">
        
        <x-slot:top>
            <div id="pb-fresh-data" class="hidden" 
                 data-layouts="{{ json_encode($layouts) }}" 
                 data-widgets="{{ json_encode($availableWidgets) }}">
            </div>
            
            <x-page-header :title="lexicon('admin_label_PageBuilder', 'Page Builder')">
                <x-slot:actions>
                    <x-button variant="success" href="/admin/pages/create" class="text-sm p-btn-sm"
                              x-tooltip="'{{ addslashes(lexicon('tooltip_PageBuilder_CreateNewPage', 'Create a new custom or system page')) }}'">
                        <i class="bi bi-plus-lg"></i> {{ lexicon('btn_PageBuilder_CreateNewPage', 'Create New Page') }}
                    </x-button>

                    <x-button variant="primary" href="/admin/forms" class="text-sm p-btn-sm"
                              x-tooltip="'{{ addslashes(lexicon('tooltip_PageBuilder_ManageForms', 'Open the Forms & Component Hub')) }}'">
                        <i class="bi bi-ui-checks-grid"></i> {{ lexicon('btn_PageBuilder_ManageForms', 'Manage Forms') }}
                    </x-button>

                    <x-button type="button" variant="primary" class="text-sm p-btn-sm inline-flex flex-row items-center gap-xs"
                              @click="syncWidgets()"
                              x-bind:disabled="isSyncing"
                              x-bind:class="isSyncing ? 'opacity-50 cursor-not-allowed' : ''"
                              x-tooltip="'{{ addslashes(lexicon('tooltip_PageBuilder_SyncWidgets', 'Scan widget files and synchronise the widget registry')) }}'">
                        <i class="bi bi-arrow-repeat" :class="isSyncing ? 'animate-spin-base' : ''"></i>
                        <span x-text="isSyncing ? '{{ addslashes(lexicon('msg_PageBuilder_Scanning', 'Scanning...')) }}' : '{{ addslashes(lexicon('btn_PageBuilder_SyncWidgets', 'Sync Widget Registry')) }}'">{{ lexicon('btn_PageBuilder_SyncWidgets', 'Sync Widget Registry') }}</span>
                    </x-button>
                </x-slot:actions>
            </x-page-header>
        </x-slot:top>

        <x-slot:bottom>
            <!-- 1. PAGES TABLE -->
            <x-accordion-panel :title="lexicon('label_PageBuilder_AllPages', 'All Published & Draft Pages')" :expanded="true">
                <div id="pages-table-content" x-ref="pagesTableContent">
                    <x-table>
                        <x-slot:headers>
                            <th class="p-4 text-left">{{ lexicon('label_PageBuilder_PageTitle', 'Page Title') }}</th>
                            <th class="p-4 text-left">{{ lexicon('label_PageBuilder_Grid', 'Grid Layout') }}</th>
                            <th class="p-4 text-left">{{ lexicon('label_PageBuilder_UrlSlug', 'URL Slug') }}</th>
                            <th class="p-4 text-left">{{ lexicon('label_common_Status', 'Status') }}</th>
                            <th class="p-4 text-right">{{ lexicon('label_common_Actions', 'Actions') }}</th>
                        </x-slot:headers>

                        @forelse($pages as $page)
                            <tr class="table-row">
                                <td class="p-4 font-bold text-p">{{ $page->title }}</td>
                                <td class="p-4 text-sm text-muted">{{ $page->layout ? $page->layout->name : lexicon('label_common_NoneAssigned', 'None Assigned') }}</td>
                                <td class="p-4 font-mono text-xs text-muted">{{ \Illuminate\Support\Str::start($page->slug, '/') }}</td>
                                <td class="p-4">
                                    <x-badge :variant="$page->is_published ? 'success' : 'warning'"
                                             x-tooltip="'{{ addslashes($page->is_published ? lexicon('tooltip_PageBuilder_StatusPublished', 'This page is live and visible') : lexicon('tooltip_PageBuilder_StatusDraft', 'This page is saved as a draft')) }}'">
                                        {{ $page->is_published ? lexicon('label_common_Published', 'Published') : lexicon('label_common_Draft', 'Draft') }}
                                    </x-badge>
                                </td>
                                <td class="p-4 text-right">
                                    <div class="flex flex-row justify-end items-center gap-sm text-sm">
                                        <x-button variant="text" href="{{ \Illuminate\Support\Str::start($page->slug, '/') }}" target="_blank" class="text-accent hover-text-h"
                                                  x-tooltip="'{{ addslashes(lexicon('tooltip_common_View', 'View live page in a new tab')) }}'">
                                            <i class="bi bi-box-arrow-up-right"></i> {{ lexicon('btn_common_View', 'View') }}
                                        </x-button>

                                        <x-button variant="text" href="/admin/pages/{{ $page->id }}/edit" class="text-warning hover-text-h"
                                                  x-tooltip="'{{ addslashes(lexicon('tooltip_common_Edit', 'Edit page layout and widgets')) }}'">
                                            <i class="bi bi-pencil"></i> {{ lexicon('btn_common_Edit', 'Edit') }}
                                        </x-button>

                                        <x-button variant="text" class="text-danger hover-text-h" 
                                                  x-data="deleteAction(`/admin/pages/{{ $page->id }}`, 'DELETE', '{{ addslashes($page->title) }}')" 
                                                  @click="confirmDelete"
                                                  x-tooltip="'{{ addslashes(lexicon('tooltip_common_Delete', 'Delete this page')) }}'">
                                            <i class="bi bi-trash"></i> {{ lexicon('btn_common_Delete', 'Delete') }}
                                        </x-button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-6 text-center text-muted italic">
                                    <span class="block">{{ lexicon('msg_PageBuilder_NoRecords', 'No custom pages have been created yet.') }}</span>
                                </td>
                            </tr>
                        @endforelse
                    </x-table>
                </div>
            </x-accordion-panel>

            <!-- 2. GRID LAYOUTS TABLE -->
            <x-accordion-panel :title="lexicon('label_PageBuilder_GridStructures', 'Grid Layout Structures')">
                <x-slot:top>
                    <x-button variant="success" @click="openLayoutModal('create')" class="text-sm p-btn-sm"
                              x-tooltip="'{{ addslashes(lexicon('tooltip_LayoutManager_CreateLayout', 'Create a new 12-column grid layout structure')) }}'">
                        <i class="bi bi-plus-lg"></i> {{ lexicon('btn_PageBuilder_CreateLayout', 'Create New Grid') }}
                    </x-button>
                </x-slot:top>

                <div id="layouts-table-content" x-ref="layoutsTableContent">
                    <x-table>
                        <x-slot:headers>
                            <th class="p-4 text-left">{{ lexicon('label_LayoutManager_LayoutName', 'Layout Name') }}</th>
                            <th class="p-4 text-left">{{ lexicon('label_LayoutManager_Blocks', 'Blocks') }}</th>
                            <th class="p-4 text-right">{{ lexicon('label_common_Actions', 'Actions') }}</th>
                        </x-slot:headers>

                        @forelse($layouts as $layout)
                            <tr class="table-row">
                                <td class="p-4 font-bold text-p">{{ $layout->name }}</td>
                                <td class="p-4 text-sm text-muted">
                                    <x-badge variant="info" x-tooltip="'{{ addslashes(lexicon('tooltip_LayoutManager_BlockCount', 'Total content blocks in this grid layout')) }}'">
                                        {{ $layout->blocks->count() }} {{ lexicon('label_common_Blocks', 'Blocks') }}
                                    </x-badge>
                                </td>
                                <td class="p-4 text-right">
                                    <div class="flex flex-row justify-end items-center gap-sm text-sm">
                                        <x-button variant="text" class="text-warning hover-text-h"
                                                  @click="openLayoutModal('edit', { id: {{ $layout->id }}, name: '{{ addslashes($layout->name) }}', description: '{{ addslashes($layout->description) }}', blocks: {{ json_encode($layout->blocks) }} })"
                                                  x-tooltip="'{{ addslashes(lexicon('tooltip_LayoutManager_EditLayout', 'Edit grid layout blocks and dimensions')) }}'">
                                            <i class="bi bi-pencil"></i> {{ lexicon('btn_common_Edit', 'Edit') }}
                                        </x-button>

                                        <x-button variant="text" class="text-danger hover-text-h" 
                                                  x-data="deleteAction(`/admin/layouts/{{ $layout->id }}`, 'DELETE', '{{ addslashes($layout->name) }}')" 
                                                  @click="confirmDelete"
                                                  x-tooltip="'{{ addslashes(lexicon('tooltip_LayoutManager_DeleteLayout', 'Delete this grid layout')) }}'">
                                            <i class="bi bi-trash"></i> {{ lexicon('btn_common_Delete', 'Delete') }}
                                        </x-button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="p-6 text-center text-muted italic">
                                    <span class="block">{{ lexicon('msg_LayoutManager_NoLayouts', 'No grid layouts have been created yet.') }}</span>
                                </td>
                            </tr>
                        @endforelse
                    </x-table>
                </div>
            </x-accordion-panel>

            <!-- MODAL: GRID LAYOUT CREATION/EDITING -->
            <x-modal show="modals.layout.open" maxWidth="max-w-4xl">
                <x-slot:top>
                    <span x-text="modals.layout.mode === 'create' ? '{{ addslashes(lexicon('label_LayoutManager_CreateLayout', 'Create Grid Layout')) }}' : '{{ addslashes(lexicon('label_LayoutManager_EditLayout', 'Edit Grid Layout')) }}'"></span>
                </x-slot:top>

                <form :action="modals.layout.formAction" method="POST" x-data="ajaxForm" @submit.prevent="submit" class="flex flex-col gap-md">
                    @csrf
                    <input type="hidden" name="_method" :value="modals.layout.method">
                    
                    <div class="grid grid-cols-1 md-grid-cols-2 gap-md bg-panel-sub border border-panel rounded-panel p-3">
                        <x-form-input 
                            name="name" 
                            id="layout-name"
                            :label="lexicon('label_LayoutManager_LayoutName', 'Layout Name')" 
                            xModel="modals.layout.name" 
                            :tooltip="lexicon('tooltip_LayoutManager_LayoutNameInput', 'Enter a descriptive name for this grid layout')"
                            required />

                        <x-form-input 
                            name="description" 
                            id="layout-description"
                            :label="lexicon('label_common_Description', 'Description')" 
                            xModel="modals.layout.description" 
                            :tooltip="lexicon('tooltip_LayoutManager_DescriptionInput', 'Optional summary of how this layout is structured')" />
                    </div>

                    <div class="bg-panel border border-panel rounded-panel shadow-panel overflow-hidden mt-4">
                        <div class="bg-panel-header text-h border-b border-panel p-panel-header font-bold text-sm p-3 flex flex-row justify-between items-center flex-wrap gap-sm">
                            <span>{{ lexicon('label_LayoutManager_GridBlocks', 'Grid Blocks (12x12 System)') }}</span>
                            <x-button type="button" variant="success" @click="addLayoutBlock" class="text-sm p-btn-sm"
                                      x-tooltip="'{{ addslashes(lexicon('tooltip_LayoutManager_AddBlock', 'Add a new block slot to this grid layout')) }}'">
                                <i class="bi bi-plus-lg"></i> {{ lexicon('btn_LayoutManager_AddBlock', 'Add Block') }}
                            </x-button>
                        </div>
                        <div class="p-4 flex flex-col gap-md">
                            <template x-for="(block, index) in modals.layout.blocks" :key="index">
                                <div class="flex flex-row items-end flex-wrap gap-sm pb-4 border-b border-dashed-panel">
                                    <input type="hidden" :name="`blocks[${index}][id]`" x-model="block.id">
                                    
                                    <div class="flex flex-col gap-xs flex-grow">
                                        <label class="text-xs font-bold text-input-muted">{{ lexicon('label_LayoutManager_BlockName', 'Block Name') }}</label>
                                        <input type="text" :name="`blocks[${index}][name]`" x-model="block.name" 
                                               class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent p-2" 
                                               x-tooltip="'{{ addslashes(lexicon('tooltip_LayoutManager_BlockNameInput', 'Slot name (e.g. top, middle, bottom, sidebar, content)')) }}'"
                                               required>
                                    </div>
                                    <div class="flex flex-col gap-xs w-24">
                                        <label class="text-xs font-bold text-input-muted text-center">{{ lexicon('label_LayoutManager_Wide', 'Wide (1-12)') }}</label>
                                        <input type="number" :name="`blocks[${index}][col_span]`" x-model="block.col_span" 
                                               class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent p-2 text-center" 
                                               min="1" max="12" 
                                               x-tooltip="'{{ addslashes(lexicon('tooltip_LayoutManager_ColSpanInput', 'Column width span from 1 to 12')) }}'"
                                               required>
                                    </div>
                                    <div class="flex flex-col gap-xs w-24">
                                        <label class="text-xs font-bold text-input-muted text-center">{{ lexicon('label_LayoutManager_Tall', 'Tall (1-12)') }}</label>
                                        <input type="number" :name="`blocks[${index}][row_span]`" x-model="block.row_span" 
                                               class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent p-2 text-center" 
                                               min="1" max="12" 
                                               x-tooltip="'{{ addslashes(lexicon('tooltip_LayoutManager_RowSpanInput', 'Row height span from 1 to 12')) }}'"
                                               required>
                                    </div>
                                    <div class="pb-1">
                                        <x-button type="button" variant="danger" @click="removeLayoutBlock(index)" class="text-xs p-btn-sm"
                                                  x-tooltip="'{{ addslashes(lexicon('tooltip_LayoutManager_RemoveBlock', 'Remove this block from the layout')) }}'"
                                                  aria-label="{{ lexicon('tooltip_LayoutManager_RemoveBlock', 'Remove this block from the layout') }}">
                                            <i class="bi bi-x-lg"></i>
                                        </x-button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="border-t-actions border-panel pt-6 text-right mt-4 flex flex-row flex-wrap items-center justify-end gap-sm">
                        <x-button type="button" variant="secondary" @click="modals.layout.open = false"
                                  x-tooltip="'{{ addslashes(lexicon('tooltip_common_Cancel', 'Cancel and close modal')) }}'">
                            {{ lexicon('btn_common_Cancel', 'Cancel') }}
                        </x-button>
                        <x-button type="submit" variant="primary"
                                  x-tooltip="'{{ addslashes(lexicon('tooltip_LayoutManager_SaveLayout', 'Save grid layout configuration')) }}'">
                            <i class="bi bi-check-lg"></i> {{ lexicon('btn_LayoutManager_SaveLayout', 'Save Grid Layout') }}
                        </x-button>
                    </div>
                </form>
            </x-modal>
        </x-slot:bottom>

    </x-admin-grid-wrapper>
</div>
@endsection