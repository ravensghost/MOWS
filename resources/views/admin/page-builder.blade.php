@extends('layouts.app')

@section('title', $page->exists ? lexicon('admin_label_PageBuilderEditPage', 'Page Build: Edit Page') : lexicon('admin_label_PageBuilderCreatePage', 'Page Build: Create Page'))

@section('content')
@php
    $widgetRegistry = cache('mows_widget_manifest', []);
    
    $widgetData = $page->exists ? $page->widgets->map(fn($w) => [
        'id' => $w->id,
        'layout_block_id' => $w->pivot->layout_block_id,
        'sort_order' => $w->pivot->sort_order,
        'required_permission' => $w->pivot->required_permission,
        'is_visible' => (bool) ($w->pivot->is_visible ?? true),
        'instance_options' => is_string($w->pivot->instance_options) ? json_decode($w->pivot->instance_options, true) : ($w->pivot->instance_options ?? [])
    ]) : [];
@endphp

<div x-data="pageBuilderManager({{ json_encode($layouts) }}, {{ json_encode($availableWidgets) }}, {{ json_encode($widgetRegistry) }})" 
     x-init="
        @if($page->exists)
            openPageModal('edit', {
                id: {{ $page->id }},
                title: '{{ addslashes($page->title) }}',
                slug: '{{ $page->slug }}',
                layout_id: '{{ $page->layout_id }}',
                is_published: {{ $page->is_published ? 'true' : 'false' }},
                widgets: {{ json_encode($widgetData) }}
            });
        @else
            openPageModal('create');
        @endif
     "
     @ajax-success.window="window.location.href = '/admin/pages'">
    
    <x-admin-grid-wrapper :page="$systemPage">
        
        <x-slot:top>
            <x-page-header :title="$page->exists ? lexicon('admin_label_PageBuilderEditPage', 'Page Build: Edit Page') : lexicon('admin_label_PageBuilderCreatePage', 'Page Build: Create Page')" :showBack="false">
                <x-slot:actions>
                    <div class="flex flex-row items-center gap-sm">
                        <x-button variant="warning" href="/admin/pages" class="text-sm"
                                  x-tooltip="'{{ addslashes(lexicon('tooltip_PageBuilder_BackToPages', 'Return to the Page Builder overview')) }}'">
                            &larr; {{ lexicon('btn_PageBuilder_BackToPages', 'Back to Page Builder') }}
                        </x-button>
                        <x-button variant="primary" class="text-sm" @click="syncWidgets()"
                                  x-tooltip="'{{ addslashes(lexicon('tooltip_PageBuilder_SyncWidgets', 'Scan widget files and synchronise the widget registry')) }}'">
                            <i class="bi bi-arrow-repeat" :class="{ 'animate-spin': isSyncing }"></i> {{ lexicon('btn_PageBuilder_SyncWidgets', 'Sync Widget Registry') }}
                        </x-button>
                    </div>
                </x-slot:actions>
            </x-page-header>
        </x-slot:top>

        <x-slot:bottom>
            @if(empty($widgetRegistry))
                <div class="bg-panel-sub border border-accent rounded-panel p-4 mb-4 flex flex-row items-center justify-between gap-md">
                    <div class="flex flex-col gap-xs">
                        <span class="text-sm font-bold text-accent"><i class="bi bi-exclamation-triangle"></i> {{ lexicon('label_PageBuilder_RegistryEmptyTitle', 'Widget Registry Cache Empty') }}</span>
                        <span class="text-xs text-muted">{{ lexicon('msg_PageBuilder_RegistryEmptyDesc', 'The widget manifest cache is currently uninitialised. Please click the button to scan and synchronise widget options.') }}</span>
                    </div>
                    <x-button variant="primary" class="text-xs" @click="syncWidgets()">
                        {{ lexicon('btn_PageBuilder_SyncWidgets', 'Sync Widget Registry') }}
                    </x-button>
                </div>
            @endif

            <form action="{{ $page->exists ? '/admin/pages/' . $page->id : '/admin/pages' }}" method="POST" x-data="ajaxForm" @submit.prevent="submit" class="flex flex-col gap-md">
                @csrf
                @if($page->exists)
                    @method('PUT')
                @endif
                
                <div class="grid grid-cols-1 md-grid-cols-3 gap-md bg-panel-sub border border-panel rounded-panel p-3">
                    <x-form-input 
                        name="title" 
                        id="page-title"
                        :label="lexicon('label_PageBuilder_PageTitle', 'Page Title')" 
                        xModel="modals.page.title" 
                        :tooltip="lexicon('tooltip_PageBuilder_PageTitleInput', 'Enter the display title for this page')"
                        required />

                    <x-form-input 
                        name="slug" 
                        id="page-slug"
                        :label="lexicon('label_PageBuilder_UrlSlugAuto', 'URL Slug')" 
                        xModel="modals.page.slug" 
                        :placeholder="lexicon('label_PageBuilder_SlugPlaceholder', 'auto-generates')"
                        :tooltip="lexicon('tooltip_PageBuilder_UrlSlugInput', 'Leave blank to auto-generate from the page title')" />

                    <div class="flex flex-col gap-xs">
                        <label for="page-layout-id" class="text-xs font-bold text-input-muted">
                            <span>{{ lexicon('label_PageBuilder_GridLayout', 'Grid Layout') }}</span>
                            <span class="text-required">*</span>
                        </label>
                        <select id="page-layout-id" name="layout_id" x-model="modals.page.layout_id" 
                                class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent p-2" 
                                x-tooltip="'{{ addslashes(lexicon('tooltip_PageBuilder_SelectGridLayout', 'Choose the grid layout blueprint for this page')) }}'"
                                required>
                            <option value="" disabled>{{ lexicon('label_common_SelectLayout', '-- Select Layout --') }}</option>
                            <template x-for="layout in availableLayouts" :key="layout.id">
                                <option :value="layout.id" x-text="layout.name"></option>
                            </template>
                        </select>
                    </div>
                </div>

                <div class="bg-panel border border-panel rounded-panel shadow-panel overflow-hidden mt-4" x-show="modals.page.layout_id" x-cloak>
                    <div class="bg-panel-header text-h border-b border-panel p-panel-header font-bold text-sm p-3">
                        <span>{{ lexicon('label_PageBuilder_LayoutWidgets', 'Assign Widgets to Grid Blocks') }}</span>
                    </div>
                    
                    <x-table>
                        <x-slot:headers>
                            <th class="p-4 w-12 text-center">{{ lexicon('label_common_Use', 'Use') }}</th>
                            <th class="p-4 text-left">{{ lexicon('label_PageBuilder_WidgetName', 'Widget Name') }}</th>
                            <th class="p-4 w-48 text-left">{{ lexicon('label_PageBuilder_GridBlock', 'Grid Area (Block)') }}</th>
                            <th class="p-4 w-48 text-left">{{ lexicon('label_PageBuilder_RequiredPermission', 'Required Permission') }}</th>
                            <th class="p-4 w-24 text-center">{{ lexicon('label_common_SortOrder', 'Sort Order') }}</th>
                            <th class="p-4 w-24 text-center">{{ lexicon('label_common_ShowOnSite', 'Show on Site') }}</th>

                        </x-slot:headers>

                        @forelse($widgets ?? [] as $widget)
                            <tr class="table-row">
                                <td class="p-4 text-center">
                                    <input type="checkbox" name="widgets[{{ $widget->id }}][selected]" value="1" 
                                           class="appearance-none bg-input m-0 w-4 h-4 border-input rounded-input-sm grid place-content-center checkbox-engine cursor-pointer mx-auto" 
                                           x-model="modals.page.selectedWidgets[{{ $widget->id }}]"
                                           x-tooltip="'{{ addslashes(lexicon('tooltip_PageBuilder_ToggleWidget', 'Enable or disable this widget on the page')) }}'"
                                           aria-label="{{ lexicon('tooltip_PageBuilder_ToggleWidget', 'Enable or disable this widget on the page') }}">
                                </td>
                                <td class="p-4 font-bold text-p">
                                    <div class="flex flex-row items-center gap-sm">
                                        <span>{{ $widget->name }}</span>
                                        <x-button type="button" 
                                                  variant="primary"
                                                  class="text-xs p-btn-sm py-0 px-2 inline-flex flex-row items-center gap-xs"
                                                  x-cloak="true"
                                                  x-show="modals.page.selectedWidgets[{{ $widget->id }}]" 
                                                  @click="openConfigModal({{ $widget->id }}, '{{$widget->widget_key }}')" 
                                                  x-tooltip="'{{ addslashes(lexicon('tooltip_PageBuilder_ConfigureWidget', 'Configure widget instance settings')) }}'">
                                            <i class="bi bi-gear"></i> {{ lexicon('btn_PageBuilder_Config', 'Config') }}
                                        </x-button>
                                    </div>
                                </td>
                                <td class="p-4">
                                    <select x-model="modals.page.widgetBlocks[{{ $widget->id }}]" name="widgets[{{ $widget->id }}][layout_block_id]" 
                                            class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent p-2 text-sm"
                                            x-tooltip="'{{ addslashes(lexicon('tooltip_PageBuilder_SelectWidgetBlock', 'Assign this widget to a layout block slot')) }}'">
                                        <option value="" disabled>{{ lexicon('label_PageBuilder_OptSelectBlock', 'Select Block...') }}</option>
                                        <template x-for="block in getActiveBlocks()" :key="block.id">
                                            <option :value="block.id" x-text="block.name"></option>
                                        </template>
                                    </select>
                                </td>
                                <td class="p-4">
                                    <select x-model="modals.page.requiredPermissions[{{ $widget->id }}]" name="widgets[{{ $widget->id }}][required_permission]" 
                                            class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent p-2 text-sm"
                                            x-tooltip="'{{ addslashes(lexicon('tooltip_PageBuilder_WidgetPermission', 'Optionally restrict visibility of this widget to a specific Spatie permission')) }}'">
                                        <option value="">{{ lexicon('label_PageBuilder_OptNonePublic', 'None (Public)') }}</option>
                                        @foreach($permissions ?? [] as $perm)
                                            <option value="{{ $perm->name }}">{{ $perm->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="p-4 text-center">
                                    <input type="number" name="widgets[{{ $widget->id }}][sort_order]" 
                                           class="bg-input text-input border-input rounded-input p-input outline-none h-input w-16 box-border focus-border-accent focus-shadow-accent p-2 text-center mx-auto" 
                                           x-model="modals.page.sortOrders[{{ $widget->id }}]"
                                           x-tooltip="'{{ addslashes(lexicon('tooltip_PageBuilder_WidgetSortOrder', 'Numeric display order inside the assigned block')) }}'">
                                </td>
                                <td class="p-4 text-center">
                                    <input type="hidden" :name="`widgets[{{ $widget->id }}][is_visible]`" value="0">
                                    <input type="checkbox" :name="`widgets[{{ $widget->id }}][is_visible]`" value="1" 
                                           class="appearance-none bg-input m-0 w-4 h-4 border-input rounded-input-sm grid place-content-center checkbox-engine cursor-pointer mx-auto" 
                                           x-model="modals.page.selectedVisible[{{ $widget->id }}]"
                                           x-init="if (modals.page.selectedVisible[{{ $widget->id }}] === undefined) modals.page.selectedVisible[{{$widget->id }}] = true">
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-6 text-center text-muted italic">
                                    <span class="block">{{ lexicon('msg_PageBuilder_NoWidgetsAvailable', 'No widgets are available.') }}</span>
                                </td>
                            </tr>
                        @endforelse
                    </x-table>
                </div>

                <div class="flex flex-row items-center gap-sm mt-4">
                    <input type="checkbox" name="is_published" id="page-published" x-model="modals.page.is_published" 
                           class="appearance-none bg-input m-0 w-4 h-4 border-input rounded-input-sm grid place-content-center checkbox-engine cursor-pointer"
                           x-tooltip="'{{ addslashes(lexicon('tooltip_PageBuilder_PublishToggle', 'Toggle whether this page is publicly accessible')) }}'">
                    <label for="page-published" class="text-sm font-bold text-muted cursor-pointer">{{ lexicon('label_PageBuilder_PublishPageImmediately', 'Publish Page immediately') }}</label>
                </div>

                <div class="border-t-actions border-panel pt-6 text-right mt-4 flex flex-row flex-wrap items-center justify-end gap-sm">
                    <template x-for="(options, wId) in modals.page.instanceOptions" :key="'opts_'+wId">
                        <template x-for="(val, fKey) in options" :key="'opts_'+wId+'_'+fKey">
                            <input type="hidden" :name="`widgets[${wId}][instance_options][${fKey}]`" :value="val">
                        </template>
                    </template>
                    <x-button type="submit" variant="primary" class="text-lg"
                              x-tooltip="'{{ addslashes(lexicon('tooltip_PageBuilder_SavePage', 'Save page settings and widget assignments')) }}'">
                        <i class="bi bi-check-lg"></i> {{ lexicon('btn_PageBuilder_SavePage', 'Save Page') }}
                    </x-button>
                </div>
            </form>

            <!-- WIDGET CONFIGURATION SUB-MODAL -->
            <x-modal show="modals.widget.open" maxWidth="max-w-4xl" :closeOnOutside="false" backdropClass="z-100">
                <x-slot:top>
                    <span>{{ lexicon('label_PageBuilder_ConfigureWidgetInstance', 'Configure Widget Instance') }}</span>
                </x-slot:top>

                <div class="flex flex-col gap-md">
                    <!-- Registry Missing Warning Inside Modal -->
                    <template x-if="Object.keys(widgetData || {}).length === 0">
                        <div class="bg-panel-sub border border-accent rounded-panel p-4 flex flex-col gap-sm text-center">
                            <span class="text-sm font-bold text-accent"><i class="bi bi-exclamation-triangle"></i> {{ lexicon('label_PageBuilder_RegistryEmptyTitle', 'Widget Registry Cache Empty') }}</span>
                            <span class="text-xs text-muted">{{ lexicon('msg_PageBuilder_RegistryModalWarning', 'No configuration fields are available because the widget manifest has not been scanned yet.') }}</span>
                            <x-button variant="primary" class="text-xs mx-auto mt-2" @click="syncWidgets()">
                                {{ lexicon('btn_PageBuilder_SyncWidgets', 'Sync Widget Registry') }}
                            </x-button>
                        </div>
                    </template>

                    <template x-for="(fieldData, fieldKey) in currentWidgetFields" :key="modals.page.configuringWidgetId + '_' + fieldKey">
                        <div class="flex flex-col gap-xs mt-3">
                            <label class="text-xs font-bold text-input-muted" x-text="fieldData.label"></label>
                            
                            <template x-if="fieldData.type === 'richtext'">
                                <div class="bg-panel border border-panel rounded-panel flex flex-col overflow-hidden" 
                                     x-data="tiptapEditor(modals.page.instanceOptions[modals.page.configuringWidgetId]?.[fieldKey] || fieldData.default || '')"
                                     @tiptap-update="modals.page.instanceOptions[modals.page.configuringWidgetId][fieldKey] = $event.detail">
                                    <x-tiptap-toolbar />
                                    <div x-ref="editorContainer" class="p-3 flex-grow tiptap-content"></div>
                                    <input type="hidden" :name="`widgets[${modals.page.configuringWidgetId}][instance_options][${fieldKey}]`" :value="modals.page.instanceOptions[modals.page.configuringWidgetId]?.[fieldKey]">
                                </div>
                            </template>
                    
                            <template x-if="fieldData.type === 'dropdown'">
                                <select :name="`widgets[${modals.page.configuringWidgetId}][instance_options][${fieldKey}]`" 
                                        class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent p-2" 
                                        x-model="modals.page.instanceOptions[modals.page.configuringWidgetId][fieldKey]">
                                    <template x-for="(optLabel, optVal) in fieldData.options" :key="optVal">
                                        <option :value="optVal" x-text="optLabel"></option>
                                    </template>
                                </select>
                            </template>

                            <template x-if="fieldData.type === 'form'">
                                <select :name="`widgets[${modals.page.configuringWidgetId}][instance_options][${fieldKey}]`" 
                                        class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent p-2" 
                                        x-model="modals.page.instanceOptions[modals.page.configuringWidgetId][fieldKey]">
                                    <option value="">{{ lexicon('label_PageBuilder_OptSelectForm', '-- Select a Form --') }}</option>
                                    @foreach($forms ?? [] as $form)
                                        <option value="{{ $form->id }}">{{ $form->name }}</option>
                                    @endforeach
                                </select>
                            </template>
                    
                            <template x-if="fieldData.type === 'textarea'">
                                <textarea :name="`widgets[${modals.page.configuringWidgetId}][instance_options][${fieldKey}]`" 
                                          class="bg-input text-input border-input rounded-input p-input outline-none w-full box-border focus-border-accent focus-shadow-accent p-2" 
                                          rows="4" 
                                          x-init="window.autosize && window.autosize($el)"
                                          x-model="modals.page.instanceOptions[modals.page.configuringWidgetId][fieldKey]"></textarea>
                            </template>

                            <template x-if="fieldData.type === 'text'">
                                <input type="text" :name="`widgets[${modals.page.configuringWidgetId}][instance_options][${fieldKey}]`" 
                                       class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent p-2" 
                                       x-model="modals.page.instanceOptions[modals.page.configuringWidgetId][fieldKey]">
                            </template>
                        </div>
                    </template>
                </div>

                <x-slot:bottom>
                    <x-button type="button" variant="primary" @click="modals.widget.open = false"
                              x-tooltip="'{{ addslashes(lexicon('tooltip_PageBuilder_DoneWidgetConfig', 'Apply widget settings and close modal')) }}'">
                        <i class="bi bi-check-lg"></i> {{ lexicon('btn_common_Done', 'Done') }}
                    </x-button>
                </x-slot:bottom>
            </x-modal>

        </x-slot:bottom>
    </x-admin-grid-wrapper>
</div>
@endsection