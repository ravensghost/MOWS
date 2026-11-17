{{-- resources/views/admin/books_grid_builder.blade.php --}}
@extends('layouts.app')

@php
    $bookModel = $systemBook ?? $book ?? null;
@endphp

@section('title', lexicon('admin_books_grid_builder_title', $bookModel->book_title ?? 'Book Grid Builder'))

@section('content')
<div x-data="bookBuilderManager(@js($grids ?? []), @js($modules ?? []), @js($availableModules ?? []))"
     @ajax-success.window="refreshDOM()"
     x-ref="bookBuilderWrapper"
     class="flex flex-col gap-lg">

    <div id="pb-fresh-data"
         class="hidden"
         data-grids='@json($grids ?? [])'
         data-modules='@json($availableModules ?? [])'></div>

    @if($bookModel &&$bookModel->book_url !== '/')
        <x-page-header :title="lexicon('admin_books_grid_builder_title', $bookModel->book_title)"
                       :subtitle="lexicon('desc_books_grid_builder', 'Arrange grid cells, assign modules, and configure module settings for this book.')"
                       icon="bi-layout-wtf">
            <x-slot:actions>
                @can('manage_pages')
                    <x-button type="button"
                              variant="secondary"
                              size="sm"
                              icon="bi-arrow-repeat"
                              :tooltip="lexicon('tooltip_books_SyncModules', 'Synchronise @WidgetManifest declarations from resources/views/modules')"
                              @click="syncModules()">
                        <span x-text="isSyncing ? '{{ addslashes(lexicon('msg_common_Syncing', 'Syncing...')) }}' : '{{ addslashes(lexicon('btn_books_SyncModules', 'Sync Modules')) }}'"></span>
                    </x-button>

                    <x-button type="button"
                              variant="primary"
                              size="sm"
                              icon="bi-grid-3x3-gap"
                              :tooltip="lexicon('tooltip_grids_CreateGrid', 'Create a new layout grid')"
                              @click="openModal('create', {}, 'grid')">
                        + {{ lexicon('btn_books_NewGrid', 'New Grid') }}
                    </x-button>
                @endcan
            </x-slot:actions>
        </x-page-header>
    @endif

    @if($bookModel)
        <x-grid-wrapper :book="$bookModel" />
    @endif

    {{-- MODAL: MODULE INSTANCE SETTINGS --}}
    <x-modal show="modals.module.open" onClose="closeModal('module')" maxWidth="max-w-lg">
        <x-slot:top>
            <span x-text="modals.module.module_title || '{{ addslashes(lexicon('modal_books_ConfigureModule', 'Configure Book Module')) }}'"></span>
        </x-slot:top>

        <x-meta type="modal" xAction="modals.module.action" xMethod="modals.module.method">
            <x-metadata type="text"
                        name="access_clearance"
                        xModel="modals.module.access_clearance"
                        :label="lexicon('label_books_ModuleClearance', 'Required Permission / Clearance (Optional)')"
                        :tooltip="lexicon('tooltip_books_ModuleClearance', 'Leave blank for public access, or specify a Spatie permission token')" />

            <x-metadata type="checkbox"
                        name="is_visible"
                        value="1"
                        xModel="modals.module.is_visible"
                        :label="lexicon('label_common_ShowOnSite', 'Show on Site (Visible)')"
                        :tooltip="lexicon('tooltip_books_ModuleVisible', 'Toggle module visibility on this book')" />

            <x-slot:footer>
                <x-button type="button"
                          variant="secondary"
                          :tooltip="lexicon('tooltip_common_Cancel', 'Cancel and close modal')"
                          @click="closeModal('module')">
                    {{ lexicon('btn_common_Cancel', 'Cancel') }}
                </x-button>
                <x-button type="submit"
                          variant="primary"
                          icon="bi-save"
                          :tooltip="lexicon('tooltip_common_Save', 'Save module configuration')">
                    {{ lexicon('btn_common_Save', 'Save Module') }}
                </x-button>
            </x-slot:footer>
        </x-meta>
    </x-modal>
</div>
@endsection