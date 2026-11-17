{{-- resources/views/admin/books.blade.php --}}
@extends('layouts.app')

@php
    $bookModel = $systemBook ?? $book ?? null;
@endphp

@section('title', lexicon('admin_books_title', $bookModel->book_title ?? 'Books & Grids Manager'))

@section('content')
<div x-data="bookBuilderManager(@js($grids ?? []), @js($modules ?? []), @js($availableModules ?? []))"
     @ajax-success.window="refreshDOM()"
     x-ref="bookBuilderWrapper"
     class="flex flex-col gap-lg">

    <div id="pb-fresh-data"
         class="hidden"
         data-grids='@json($grids ?? [])'
         data-modules='@json($availableModules ?? [])'></div>

    <x-grid-wrapper :book="$bookModel">
        <x-slot:content>
            <x-page-header :title="lexicon('admin_books_title', $bookModel->book_title ?? 'Books & Grids Manager')"
                           :subtitle="lexicon('desc_admin_books', 'Manage portal books, hybrid 12-column grids, and assigned module manifests.')"
                           icon="bi-journal-richtext">
                <x-slot:actions>
                    @can('manage_pages')
                        <x-metadata type="search"
                                    name="book_search"
                                    :bare="true"
                                    xModel="searchQuery"
                                    :placeholder="lexicon('placeholder_books_Search', 'Search books or grids...')"
                                    :tooltip="lexicon('tooltip_books_Search', 'Filter books and grids by title or URL')" />

                        <x-button type="button"
                                  variant="secondary"
                                  size="sm"
                                  icon="bi-arrow-repeat"
                                  :tooltip="lexicon('tooltip_books_SyncModules', 'Scan resources/views/modules for @WidgetManifest declarations')"
                                  @click="syncModules()">
                            <span x-text="isSyncing ? '{{ addslashes(lexicon('msg_common_Syncing', 'Syncing...')) }}' : '{{ addslashes(lexicon('btn_books_SyncModules', 'Sync Modules')) }}'"></span>
                        </x-button>

                        <x-button type="button"
                                  variant="secondary"
                                  size="sm"
                                  icon="bi-grid-3x3-gap"
                                  :tooltip="lexicon('tooltip_books_CreateGrid', 'Create a new 12-column layout grid')"
                                  @click="openModal('create', {}, 'grid')">
                            + {{ lexicon('btn_books_NewGrid', 'New Grid') }}
                        </x-button>

                        <x-button type="button"
                                  variant="primary"
                                  size="sm"
                                  icon="bi-journal-plus"
                                  :tooltip="lexicon('tooltip_books_CreateBook', 'Create a new portal book page')"
                                  @click="openModal('create', {}, 'book')">
                            + {{ lexicon('btn_books_NewBook', 'New Book') }}
                        </x-button>
                    @endcan
                </x-slot:actions>
            </x-page-header>

            @if(isset($books))
                <x-tab type="accordion"
                       :title="lexicon('heading_books_PortalBooks', 'Portal Books')"
                       icon="bi-journals"
                       :open="true">
                    <div x-ref="booksTableContent">
                        <x-table type="admin"
                                 :headers="[
                                     lexicon('col_books_Title', 'Book Title'),
                                     lexicon('col_books_Url', 'Route URL'),
                                     lexicon('col_books_Grid', 'Assigned Grid'),
                                     lexicon('col_common_Status', 'Status'),
                                     lexicon('col_common_Actions', 'Actions')
                                 ]"
                                 :emptyText="lexicon('msg_books_NoBooksFound', 'No portal books configured.')">
                            @foreach($books as $bItem)
                                <tr class="border-b-table-row hover-bg-panel-hover transition-colors"
                                    data-search-item
                                    data-book-item
                                    data-id="{{ $bItem->id }}"
                                    data-book_title="{{ $bItem->book_title }}"
                                    data-book_url="{{ $bItem->book_url }}"
                                    x-show="isItemVisible({{ $bItem->id }})">
                                    <td class="p-4 font-bold text-h">{{ $bItem->book_title }}</td>
                                    <td class="p-4 font-mono text-xs text-accent">/{{ ltrim($bItem->book_url, '/') }}</td>
                                    <td class="p-4 text-sm text-muted">{{ $bItem->grid?->grid_title ?? lexicon('label_common_Unassigned', 'Unassigned') }}</td>
                                    <td class="p-4">
                                        <x-badge type="pill" :variant="$bItem->is_published ? 'success' : 'warning'">
                                            {{ $bItem->is_published ? lexicon('status_common_Published', 'Published') : lexicon('status_common_Draft', 'Draft') }}
                                        </x-badge>
                                    </td>
                                    <td class="p-4 text-right">
                                        <div class="flex flex-row flex-wrap justify-end items-center gap-xs">
                                            <x-button tag="a"
                                                      href="{{ route('admin.books.edit', $bItem) }}"
                                                      variant="secondary"
                                                      size="sm"
                                                      icon="bi-layout-wtf"
                                                      :tooltip="lexicon('tooltip_books_OpenGridBuilder', 'Open visual grid & module builder')">
                                                {{ lexicon('btn_books_Builder', 'Builder') }}
                                            </x-button>

                                            <x-button type="button"
                                                      variant="ghost"
                                                      size="sm"
                                                      icon="bi-pencil"
                                                      :tooltip="lexicon('tooltip_books_EditBook', 'Edit book properties')"
                                                      @click="openModal('edit', {{ Js::from([
                                                          'id' => $bItem->id,
                                                          'book_title' => $bItem->book_title,
                                                          'book_url' => $bItem->book_url,
                                                          'grid_id' => $bItem->grid_id,
                                                          'is_published' => (bool) $bItem->is_published,
                                                      ]) }}, 'book')">
                                                {{ lexicon('btn_common_Edit', 'Edit') }}
                                            </x-button>

                                            <x-button type="button"
                                                      variant="danger"
                                                      size="sm"
                                                      icon="bi-trash"
                                                      :tooltip="lexicon('tooltip_books_DeleteBook', 'Delete this book')"
                                                      x-data="deleteAction('{{ route('admin.books.destroy', $bItem) }}', 'DELETE', '{{ addslashes($bItem->book_title) }}')"
                                                      @click="confirmDelete()">
                                                {{ lexicon('btn_common_Delete', 'Delete') }}
                                            </x-button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </x-table>
                    </div>
                </x-tab>
            @endif

            @if(!empty($grids))
                <x-tab type="accordion"
                       :title="lexicon('heading_books_HybridGrids', 'Hybrid Layout Grids')"
                       icon="bi-grid-1x2"
                       :open="true">
                    <div x-ref="gridsTableContent">
                        <x-table type="admin"
                                 :headers="[
                                     lexicon('col_grids_Title', 'Grid Title'),
                                     lexicon('col_grids_Synopsis', 'Synopsis'),
                                     lexicon('col_common_Actions', 'Actions')
                                 ]"
                                 :emptyText="lexicon('msg_grids_NoGridsFound', 'No layout grids configured.')">
                            @foreach($grids as $gItem)
                                @php
                                    $gId = is_array($gItem) ? ($gItem['id'] ?? '') : $gItem->id;
                                    $gTitle = is_array($gItem) ? ($gItem['grid_title'] ?? '') : $gItem->grid_title;
                                    $gSynopsis = is_array($gItem) ? ($gItem['grid_synopsis'] ?? '') : $gItem->grid_synopsis;
                                @endphp
                                <tr class="border-b-table-row hover-bg-panel-hover transition-colors"
                                    data-search-item
                                    data-grid-item
                                    data-id="{{ $gId }}"
                                    data-grid_title="{{ $gTitle }}"
                                    x-show="isItemVisible({{ (int) $gId }})">
                                    <td class="p-4 font-bold text-h">{{ $gTitle }}</td>
                                    <td class="p-4 text-sm text-muted">{{ $gSynopsis ?: '—' }}</td>
                                    <td class="p-4 text-right">
                                        <div class="flex flex-row flex-wrap justify-end items-center gap-xs">
                                            <x-button type="button"
                                                      variant="ghost"
                                                      size="sm"
                                                      icon="bi-pencil"
                                                      :tooltip="lexicon('tooltip_grids_EditGrid', 'Edit grid template')"
                                                      @click="openModal('edit', {{ Js::from([
                                                          'id' => $gId,
                                                          'grid_title' => $gTitle,
                                                          'grid_synopsis' => $gSynopsis,
                                                      ]) }}, 'grid')">
                                                {{ lexicon('btn_common_Edit', 'Edit') }}
                                            </x-button>

                                            <x-button type="button"
                                                      variant="danger"
                                                      size="sm"
                                                      icon="bi-trash"
                                                      :tooltip="lexicon('tooltip_grids_DeleteGrid', 'Delete grid template')"
                                                      x-data="deleteAction('/admin/grids/{{ $gId }}', 'DELETE', '{{ addslashes($gTitle) }}')"
                                                      @click="confirmDelete()">
                                                {{ lexicon('btn_common_Delete', 'Delete') }}
                                            </x-button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </x-table>
                    </div>
                </x-tab>
            @endif
        </x-slot:content>
    </x-grid-wrapper>

    {{-- MODAL: CREATE / EDIT BOOK --}}
    <x-modal show="modals.book.open" onClose="closeModal('book')" maxWidth="max-w-lg">
        <x-slot:top>
            <span x-text="modals.book.mode === 'edit'
                ? '{{ addslashes(lexicon('modal_books_EditBook', 'Edit Book')) }}'
                : '{{ addslashes(lexicon('modal_books_CreateBook', 'Create New Book')) }}'"></span>
        </x-slot:top>

        <x-meta type="modal" xAction="modals.book.action" xMethod="modals.book.method">
            <x-metadata type="text"
                        name="book_title"
                        xModel="modals.book.book_title"
                        :label="lexicon('label_books_BookTitle', 'Book Title')"
                        :tooltip="lexicon('tooltip_books_BookTitle', 'Display title of the book page')"
                        :required="true" />

            <x-metadata type="text"
                        name="book_url"
                        xModel="modals.book.book_url"
                        :label="lexicon('label_books_BookUrl', 'Route URL Slug')"
                        :tooltip="lexicon('tooltip_books_BookUrl', 'Relative URL path (e.g. roster, wiki, or / for home)')"
                        :required="true" />

            <x-metadata type="select"
                        name="grid_id"
                        xModel="modals.book.grid_id"
                        :label="lexicon('label_books_AssignedGrid', 'Assigned Layout Grid')"
                        :tooltip="lexicon('tooltip_books_AssignedGrid', 'Select the 12-column hybrid grid for this book')">
                <option value="">{{ lexicon('option_common_SelectGrid', '— Select Grid —') }}</option>
                @foreach($grids ?? [] as $gridOpt)
                    @php
                        $optId = is_array($gridOpt) ? ($gridOpt['id'] ?? '') : $gridOpt->id;
                        $optTitle = is_array($gridOpt) ? ($gridOpt['grid_title'] ?? '') : $gridOpt->grid_title;
                    @endphp
                    <option value="{{ $optId }}">{{ $optTitle }}</option>
                @endforeach
            </x-metadata>

            <x-metadata type="checkbox"
                        name="is_published"
                        value="1"
                        xModel="modals.book.is_published"
                        :label="lexicon('label_books_IsPublished', 'Published & Publicly Accessible')"
                        :tooltip="lexicon('tooltip_books_IsPublished', 'Toggle whether visitors can view this book')" />

            <x-slot:footer>
                <x-button type="button"
                          variant="secondary"
                          :tooltip="lexicon('tooltip_common_Cancel', 'Cancel and close modal')"
                          @click="closeModal('book')">
                    {{ lexicon('btn_common_Cancel', 'Cancel') }}
                </x-button>
                <x-button type="submit"
                          variant="primary"
                          icon="bi-save"
                          :tooltip="lexicon('tooltip_common_Save', 'Save book changes')">
                    {{ lexicon('btn_common_Save', 'Save Book') }}
                </x-button>
            </x-slot:footer>
        </x-meta>
    </x-modal>

    {{-- MODAL: CREATE / EDIT GRID --}}
    <x-modal show="modals.grid.open" onClose="closeModal('grid')" maxWidth="max-w-lg">
        <x-slot:top>
            <span x-text="modals.grid.mode === 'edit'
                ? '{{ addslashes(lexicon('modal_grids_EditGrid', 'Edit Layout Grid')) }}'
                : '{{ addslashes(lexicon('modal_grids_CreateGrid', 'Create Layout Grid')) }}'"></span>
        </x-slot:top>

        <x-meta type="modal" xAction="modals.grid.action" xMethod="modals.grid.method">
            <x-metadata type="text"
                        name="grid_title"
                        xModel="modals.grid.grid_title"
                        :label="lexicon('label_grids_GridTitle', 'Grid Title')"
                        :tooltip="lexicon('tooltip_grids_GridTitle', 'Unique title for this 12-column hybrid grid')"
                        :required="true" />

            <x-metadata type="textarea"
                        name="grid_synopsis"
                        xModel="modals.grid.grid_synopsis"
                        :label="lexicon('label_grids_GridSynopsis', 'Grid Synopsis')"
                        :tooltip="lexicon('tooltip_grids_GridSynopsis', 'Brief description of this grid layout structure')" />

            <x-slot:footer>
                <x-button type="button"
                          variant="secondary"
                          :tooltip="lexicon('tooltip_common_Cancel', 'Cancel and close modal')"
                          @click="closeModal('grid')">
                    {{ lexicon('btn_common_Cancel', 'Cancel') }}
                </x-button>
                <x-button type="submit"
                          variant="primary"
                          icon="bi-save"
                          :tooltip="lexicon('tooltip_common_Save', 'Save grid changes')">
                    {{ lexicon('btn_common_Save', 'Save Grid') }}
                </x-button>
            </x-slot:footer>
        </x-meta>
    </x-modal>
</div>
@endsection