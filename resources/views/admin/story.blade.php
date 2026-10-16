@extends('layouts.app')

@section('title', lexicon('admin_label_story', 'Story Library Manager'))

@section('content')
<div x-data="storyManager" 
     @ajax-success.window="refreshDOM()"
     @open-status-modal.window="openStatusModal($event.detail)"
     @open-bookcase-modal.window="openBookcaseModal($event.detail)"
     @open-book-modal.window="openBookModal($event.detail)"
     @open-move-chapter-modal.window="openMoveChapterModal($event.detail)">

    <x-admin-grid-wrapper :page="$systemPage">
        
        <x-slot:top>
            <script type="application/json" x-ref="storySearchData">
                {!! json_encode($bookcases->map(fn($bc) => [
                    'id' => $bc->id,
                    'name' => $bc->name,
                    'books' => $bc->books->map(fn($b) => [
                        'title' => $b->title,
                        'description' => $b->description ?? ''
                    ])->values()
                ])->values()) !!}
            </script>

            <x-page-header :title="lexicon('admin_label_story', 'Story Library Manager')">
                <x-slot:actions>
                    <x-button type="button" 
                              variant="success" 
                              class="p-btn-sm text-sm flex flex-row items-center gap-xs" 
                              @click="$dispatch('open-bookcase-modal', { mode: 'create' })"
                              x-tooltip="'{{ addslashes(lexicon('tooltip_Story_AddBookcase', 'Create a new bookcase to organise story books')) }}'"
                              aria-label="{{ lexicon('tooltip_Story_AddBookcase', 'Create a new bookcase to organise story books') }}">
                        <i class="bi bi-plus-lg"></i>
                        <span>{{ lexicon('btn_Story_AddBookcase', 'New Bookcase') }}</span>
                    </x-button>
                </x-slot:actions>
            </x-page-header>
        </x-slot:top>

        <x-slot:middle>
            <div class="flex flex-col gap-lg">
                
                {{-- Instant Fuzzy Search Bar (Fuse.js) --}}
                <div class="bg-panel border border-panel rounded-panel shadow-panel p-panel">
                    <x-form-input name="story_search"
                                  id="story-search-input"
                                  :label="lexicon('label_Story_SearchLibrary', 'Filter Story Library')"
                                  :placeholder="lexicon('label_Story_SearchPlaceholder', 'Search bookcases or books by title and synopsis...')"
                                  :tooltip="lexicon('tooltip_Story_SearchInput', 'Instantaneously filter bookcases and books using fuzzy search')"
                                  xModel="searchQuery"
                                  @input="filterLibrary()" />
                </div>

                {{-- Bookcases & Nested Books --}}
                <div id="bookcases-wrapper" x-ref="bookcasesWrapper">
                    <div class="flex flex-col gap-lg" id="sortable-bookcases" x-data="sortableManager('/admin/bookcases/reorder')">
                        @forelse($bookcases as $bookcase)
                            <div class="bg-panel border border-panel rounded-panel shadow-panel flex flex-col overflow-hidden w-full" 
                                 data-id="{{ $bookcase->id }}" 
                                 x-data="{ expanded: false }"
                                 x-show="matchesBookcase({{ $bookcase->id }}, {{ json_encode($bookcase->name) }})">
                                
                                <div @click="if (!$event.target.closest('button')) expanded = !expanded" 
                                     class="bg-panel-header text-h border-b border-panel p-panel-header font-bold w-full flex flex-row flex-wrap items-center justify-between gap-md text-left cursor-pointer select-none"
                                     x-tooltip="'{{ addslashes(lexicon('tooltip_common_TogglePanel', 'Click to expand or collapse panel')) }}'"
                                     aria-label="{{ lexicon('tooltip_common_TogglePanel', 'Click to expand or collapse panel') }}">
                                    
                                    <div class="flex flex-row flex-wrap items-center gap-sm">
                                        <i class="bi bi-grip-vertical text-muted cursor-move"
                                           x-tooltip="'{{ addslashes(lexicon('tooltip_Story_DragReorderBookcase', 'Drag to reorder bookcase')) }}'"
                                           aria-label="{{ lexicon('tooltip_Story_DragReorderBookcase', 'Drag to reorder bookcase') }}"></i>
                                        <span class="text-xs text-muted" x-text="expanded ? '▼' : '▶'"></span>
                                        <i class="bi bi-collection text-accent"></i>
                                        <span class="text-xl font-bold text-h">{{ $bookcase->name }}</span> 
                                        <span class="text-xs text-muted">({{ count($bookcase->books) }} {{ lexicon('label_Story_BooksCount', 'Books') }})</span>
                                        <x-button type="button" 
                                                  variant="success" 
                                                  class="p-btn-sm text-xs flex flex-row items-center gap-xs" 
                                                  @click.stop="$dispatch('open-book-modal', { mode: 'create', bookcaseId: '{{ $bookcase->id }}' })"
                                                  x-tooltip="'{{ addslashes(lexicon('tooltip_Story_AddBook', 'Add a new book to this bookcase')) }}'"
                                                  aria-label="{{ lexicon('tooltip_Story_AddBook', 'Add a new book to this bookcase') }}">
                                            <i class="bi bi-plus-lg"></i>
                                            <span>{{ lexicon('btn_Story_AddBook', 'New Book') }}</span>
                                        </x-button>
                                    </div>

                                    <div class="flex flex-row flex-wrap gap-md items-center" @click.stop>
                                        <x-button type="button" 
                                                  variant="text" 
                                                  class="text-warning hover-text-h text-sm flex flex-row items-center gap-xs" 
                                                  @click="$dispatch('open-bookcase-modal', {{ json_encode(['mode' => 'edit', 'id' => $bookcase->id, 'name' => $bookcase->name]) }})"
                                                  x-tooltip="'{{ addslashes(lexicon('tooltip_Story_EditBookcase', 'Rename this bookcase')) }}'"
                                                  aria-label="{{ lexicon('tooltip_Story_EditBookcase', 'Rename this bookcase') }}">
                                            <i class="bi bi-pencil"></i>
                                            <span>{{ lexicon('btn_common_Edit', 'Edit') }}</span>
                                        </x-button>
                                        <x-button type="button" 
                                                  variant="text" 
                                                  class="text-danger hover-text-h text-sm flex flex-row items-center gap-xs" 
                                                  x-data="deleteAction('/admin/bookcases/{{ $bookcase->id }}', 'DELETE', '{{ addslashes($bookcase->name) }}')" 
                                                  @click.prevent="confirmDelete()"
                                                  x-tooltip="'{{ addslashes(lexicon('tooltip_Story_DeleteBookcase', 'Delete this bookcase')) }}'"
                                                  aria-label="{{ lexicon('tooltip_Story_DeleteBookcase', 'Delete this bookcase') }}">
                                            <i class="bi bi-trash"></i>
                                            <span>{{ lexicon('btn_common_Delete', 'Delete') }}</span>
                                        </x-button>
                                    </div>
                                </div>
                                
                                <div x-show="expanded || searchQuery.trim().length > 0" x-collapse x-cloak 
                                     class="bg-panel-sub flex flex-col gap-md p-panel w-full box-border sortable-books" 
                                     id="sortable-books-{{ $bookcase->id }}" 
                                     data-bookcase-id="{{ $bookcase->id }}"
                                     x-data="sortableManager('/admin/books/reorder')">
                                    @forelse($bookcase->books as $book)
                                        <x-story-book-panel :book="$book" :isLoose="false" />
                                    @empty
                                        <div class="p-4 text-center text-muted italic">
                                            <span>{{ lexicon('msg_Story_NoBooksInBookcase', 'No books placed in this bookcase.') }}</span>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        @empty
                            <div class="bg-panel border border-panel rounded-panel shadow-panel p-6 text-center text-muted italic">
                                <span>{{ lexicon('msg_Story_NoBookcases', 'No bookcases built yet.') }}</span>
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- Loose / Unassigned Books --}}
                <div id="loose-books-wrapper" x-ref="looseBooksWrapper">
                    @if(count($looseBooks) > 0)
                        <x-accordion-panel :title="lexicon('label_Story_LooseBooks', 'Loose Books (Unassigned)')" :expanded="false">
                            <div class="flex flex-col gap-md" id="sortable-loose-books" x-data="sortableManager('/admin/books/reorder')">
                                @foreach($looseBooks as $book)
                                    <x-story-book-panel :book="$book" :isLoose="true" />
                                @endforeach
                            </div>
                        </x-accordion-panel>
                    @endif
                </div>

                {{-- Book Statuses Management --}}
                <div id="statuses-wrapper" x-ref="statusesWrapper">
                    <x-accordion-panel :title="lexicon('admin_label_BookStatuses', 'Book Statuses')" :expanded="false">
                        <x-slot:top>
                            <x-button type="button" 
                                      variant="success" 
                                      class="p-btn-sm text-sm flex flex-row items-center gap-xs" 
                                      @click="$dispatch('open-status-modal', { mode: 'create' })"
                                      x-tooltip="'{{ addslashes(lexicon('tooltip_Story_AddStatus', 'Create a new book status')) }}'"
                                      aria-label="{{ lexicon('tooltip_Story_AddStatus', 'Create a new book status') }}">
                                <i class="bi bi-plus-lg"></i>
                                <span>{{ lexicon('btn_Story_AddStatus', 'New Status') }}</span>
                            </x-button>
                        </x-slot:top>
                    
                        <x-table>
                            <x-slot:headers>
                                <th class="p-4 text-left">{{ lexicon('label_Story_StatusName', 'Status Name') }}</th>
                                <th class="p-4 text-right">{{ lexicon('label_common_Actions', 'Actions') }}</th>
                            </x-slot:headers>

                            @forelse($statuses as $status)
                                <tr class="border-b-table-row hover-bg-table-row">
                                    <td class="p-4 font-bold text-p">
                                        <x-badge :variant="$status->slug ?? 'primary'"
                                                 x-tooltip="'{{ addslashes(lexicon('tooltip_Story_StatusPreviewBadge', 'Status badge preview')) }}'"
                                                 aria-label="{{ lexicon('tooltip_Story_StatusPreviewBadge', 'Status badge preview') }}">
                                            {{ $status->name }}
                                        </x-badge>
                                    </td>
                                    <td class="p-4 text-right">
                                        <div class="flex flex-row flex-wrap gap-sm items-center justify-end">
                                            <x-button type="button" 
                                                      variant="text" 
                                                      class="text-warning hover-text-h text-xs flex flex-row items-center gap-xs" 
                                                      @click="$dispatch('open-status-modal', {{ json_encode(['mode' => 'edit', 'id' => $status->id, 'name' => $status->name]) }})"
                                                      x-tooltip="'{{ addslashes(lexicon('tooltip_Story_EditStatus', 'Edit book status')) }}'"
                                                      aria-label="{{ lexicon('tooltip_Story_EditStatus', 'Edit book status') }}">
                                                <i class="bi bi-pencil"></i>
                                                <span>{{ lexicon('btn_common_Edit', 'Edit') }}</span>
                                            </x-button>
                                            <x-button type="button" 
                                                      variant="text" 
                                                      class="text-danger hover-text-h text-sm flex flex-row items-center gap-xs" 
                                                      x-data="deleteAction('/admin/statuses/{{ $status->id }}', 'DELETE', '{{ addslashes($status->name) }}')" 
                                                      @click.prevent="confirmDelete()"
                                                      x-tooltip="'{{ addslashes(lexicon('tooltip_Story_DeleteStatus', 'Delete this book status')) }}'"
                                                      aria-label="{{ lexicon('tooltip_Story_DeleteStatus', 'Delete this book status') }}">
                                                <i class="bi bi-trash"></i>
                                                <span>{{ lexicon('btn_common_Delete', 'Delete') }}</span>
                                            </x-button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="p-6 text-center text-muted italic">{{ lexicon('msg_Story_NoStatuses', 'No statuses created yet.') }}</td>
                                </tr>
                            @endforelse
                        </x-table>
                    </x-accordion-panel>
                </div>

            </div>
        </x-slot:middle>

        <x-slot:bottom>
            {{-- MODAL 1: Bookcase Modal --}}
            <x-modal show="bookcaseModalOpen" maxWidth="max-w-lg">
                <x-slot:top>
                    <span x-text="bookcaseMode === 'create' ? '{{ addslashes(lexicon('label_Story_CreateBookcase', 'Build Bookcase')) }}' : '{{ addslashes(lexicon('label_Story_EditBookcase', 'Edit Bookcase')) }}'"></span>
                </x-slot:top>

                <form :action="bookcaseFormAction" 
                      :method="bookcaseMethod" 
                      class="flex flex-col gap-md" 
                      x-data="ajaxForm" 
                      @submit.prevent="submit($event)">
                    @csrf
                    <input type="hidden" name="_method" :value="bookcaseMethod">
                    
                    <x-form-input name="name"
                                  id="bookcase-name-input"
                                  :label="lexicon('label_Story_BookcaseName', 'Bookcase Name')"
                                  :tooltip="lexicon('tooltip_Story_BookcaseNameInput', 'Enter the display title for this bookcase')"
                                  xModel="bookcaseName"
                                  :required="true" />

                    <div class="border-t-actions pt-6 text-right flex flex-row flex-wrap items-center justify-end gap-md">
                        <x-button type="submit" 
                                  variant="primary" 
                                  ::disabled="submitting"
                                  x-tooltip="'{{ addslashes(lexicon('tooltip_Story_SaveBookcase', 'Save bookcase changes')) }}'"
                                  aria-label="{{ lexicon('tooltip_Story_SaveBookcase', 'Save bookcase changes') }}"
                                  x-text="submitting ? '{{ addslashes(lexicon('msg_common_Saving', 'Saving...')) }}' : '{{ addslashes(lexicon('btn_Story_SaveBookcase', 'Save Bookcase')) }}'">
                            {{ lexicon('btn_Story_SaveBookcase', 'Save Bookcase') }}
                        </x-button>
                    </div>
                </form>
            </x-modal>

            {{-- MODAL 2: Book Modal --}}
            <x-modal show="bookModalOpen" maxWidth="max-w-4xl">
                <x-slot:top>
                    <span x-text="bookMode === 'create' ? '{{ addslashes(lexicon('label_Story_CreateBook', 'Write Book Details')) }}' : '{{ addslashes(lexicon('label_Story_EditBook', 'Edit Book Details')) }}'"></span>
                </x-slot:top>

                <form :action="bookFormAction" 
                      :method="bookMethod" 
                      class="flex flex-col gap-md" 
                      x-data="ajaxForm" 
                      @submit.prevent="submit($event)">
                    @csrf
                    <input type="hidden" name="_method" :value="bookMethod">
                    
                    <div class="grid grid-cols-1 md-grid-cols-2 gap-sm w-full">
                        <x-form-input name="title"
                                      id="book-title-input"
                                      :label="lexicon('label_Story_BookTitle', 'Book Title')"
                                      :tooltip="lexicon('tooltip_Story_BookTitleInput', 'Enter the title of this story book')"
                                      xModel="bookTitle"
                                      :required="true" />

                        <div class="flex flex-col gap-xs">
                            <label for="book-bookcase-select" class="text-xs font-bold text-input-muted">
                                <span>{{ lexicon('label_Story_BookcaseLocation', 'Location (Bookcase)') }}</span>
                            </label>
                            <select id="book-bookcase-select"
                                    name="bookcase_id" 
                                    x-model="bookBookcaseId" 
                                    aria-label="{{ lexicon('label_Story_BookcaseLocation', 'Location (Bookcase)') }}"
                                    x-tooltip="'{{ addslashes(lexicon('tooltip_Story_BookcaseLocationSelect', 'Assign this book to a bookcase or leave unassigned')) }}'"
                                    class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent">
                                <option value="">{{ lexicon('label_Story_OptLooseUnassigned', '-- Loose (Unassigned) --') }}</option>
                                @foreach($bookcases as $bc)
                                    <option value="{{ $bc->id }}">{{ $bc->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="flex flex-col gap-xs">
                        <label for="book-description-textarea" class="text-xs font-bold text-input-muted">
                            <span>{{ lexicon('label_Story_BookDescription', 'Description / Synopsis') }}</span>
                        </label>
                        <textarea id="book-description-textarea"
                                  name="description" 
                                  x-model="bookDesc" 
                                  x-ref="bookDescTextarea"
                                  rows="3" 
                                  aria-label="{{ lexicon('label_Story_BookDescription', 'Description / Synopsis') }}"
                                  x-tooltip="'{{ addslashes(lexicon('tooltip_Story_BookDescriptionInput', 'Provide a brief synopsis or overview for this book')) }}'"
                                  x-init="window.autosize && window.autosize($el)"
                                  class="bg-input text-input border-input rounded-input p-input outline-none w-full box-border focus-border-accent focus-shadow-accent"></textarea>
                    </div>

                    <div class="grid grid-cols-1 lg-grid-cols-3 gap-sm w-full">
                        <div class="flex flex-col gap-xs">
                            <label for="book-status-select" class="text-xs font-bold text-input-muted">
                                <span>{{ lexicon('label_common_Status', 'Status') }}</span>
                                <span class="text-required">*</span>
                            </label>
                            <select id="book-status-select"
                                    name="status_id" 
                                    x-model="bookStatusId" 
                                    aria-label="{{ lexicon('label_common_Status', 'Status') }}"
                                    x-tooltip="'{{ addslashes(lexicon('tooltip_Story_BookStatusSelect', 'Select the current status of this book')) }}'"
                                    class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent" 
                                    required>
                                <option value="">{{ lexicon('label_Story_OptSelectStatus', '-- Select Status --') }}</option>
                                @foreach($statuses ?? [] as $st)
                                    <option value="{{ $st->id }}">{{ $st->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <x-form-input name="ic_day"
                                      id="book-ic-day-input"
                                      :label="lexicon('label_Story_CurrentIcDay', 'Current IC Day')"
                                      :placeholder="lexicon('label_WriteChapter_PlaceholderDay', 'e.g. 14')"
                                      :tooltip="lexicon('tooltip_Story_CurrentIcDayInput', 'Enter the current in-character day for this book')"
                                      xModel="bookDay" />

                        <x-form-input name="ic_time"
                                      id="book-ic-time-input"
                                      :label="lexicon('label_Story_CurrentIcTime', 'Current IC Time')"
                                      :placeholder="lexicon('label_WriteChapter_PlaceholderTime', 'e.g. 14:00')"
                                      :tooltip="lexicon('tooltip_Story_CurrentIcTimeInput', 'Enter the current in-character time for this book')"
                                      xModel="bookTime" />
                    </div>

                    <div class="border-t-actions pt-6 text-right flex flex-row flex-wrap items-center justify-end gap-md">
                        <x-button type="submit" 
                                  variant="success" 
                                  ::disabled="submitting" 
                                  x-tooltip="'{{ addslashes(lexicon('tooltip_Story_SaveBook', 'Save book details')) }}'"
                                  aria-label="{{ lexicon('tooltip_Story_SaveBook', 'Save book details') }}"
                                  x-text="submitting ? '{{ addslashes(lexicon('msg_common_Saving', 'Saving...')) }}' : '{{ addslashes(lexicon('btn_Story_SaveBook', 'Save Book')) }}'">
                            {{ lexicon('btn_Story_SaveBook', 'Save Book') }}
                        </x-button>
                    </div>
                </form>
            </x-modal>

            {{-- MODAL 3: Move Chapter Modal --}}
            <x-modal show="moveModalOpen" maxWidth="max-w-lg">
                <x-slot:top>
                    <span>{{ lexicon('label_Story_RelocateChapter', 'Relocate Chapter') }}</span>
                </x-slot:top>

                <form :action="moveFormAction" 
                      method="POST" 
                      class="flex flex-col gap-md" 
                      x-data="ajaxForm" 
                      @submit.prevent="submit($event)">
                    @csrf
                    @method('PUT')
                    <div class="flex flex-col gap-xs">
                        <label for="move-chapter-book-select" class="text-xs font-bold text-input-muted">
                            <span>{{ lexicon('label_WriteChapter_TargetBook', 'Target Book') }}</span>
                            <span class="text-required">*</span>
                        </label>
                        <select id="move-chapter-book-select"
                                name="book_id" 
                                x-model="moveBookId" 
                                aria-label="{{ lexicon('label_WriteChapter_TargetBook', 'Target Book') }}"
                                x-tooltip="'{{ addslashes(lexicon('tooltip_Story_MoveTargetBookSelect', 'Choose the destination book for this chapter')) }}'"
                                class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent" 
                                required>
                            @foreach($bookcases as $bc)
                                <optgroup label="{{ $bc->name }}" class="bg-panel text-accent">
                                    @foreach($bc->books as $b)
                                        <option value="{{ $b->id }}" class="text-p">{{ $b->title }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                            @if(count($looseBooks) > 0)
                                <optgroup label="{{ lexicon('label_Story_LooseBooks', 'Loose Books (Unassigned)') }}" class="bg-panel text-warning">
                                    @foreach($looseBooks as $b)
                                        <option value="{{ $b->id }}" class="text-p">{{ $b->title }}</option>
                                    @endforeach
                                </optgroup>
                            @endif
                        </select>
                    </div>
                    <div class="border-t-actions pt-6 text-right flex flex-row flex-wrap items-center justify-end gap-md">
                        <x-button type="submit" 
                                  variant="primary" 
                                  ::disabled="submitting" 
                                  x-tooltip="'{{ addslashes(lexicon('tooltip_Story_ConfirmMoveChapter', 'Confirm chapter relocation')) }}'"
                                  aria-label="{{ lexicon('tooltip_Story_ConfirmMoveChapter', 'Confirm chapter relocation') }}"
                                  x-text="submitting ? '{{ addslashes(lexicon('msg_Story_Moving', 'Moving...')) }}' : '{{ addslashes(lexicon('btn_Story_MoveChapterSubmit', 'Move Chapter')) }}'">
                            {{ lexicon('btn_Story_MoveChapterSubmit', 'Move Chapter') }}
                        </x-button>
                    </div>
                </form>
            </x-modal>

            {{-- MODAL 4: Status Modal --}}
            <x-modal show="statusModalOpen" maxWidth="max-w-lg">
                <x-slot:top>
                    <span x-text="statusMode === 'create' ? '{{ addslashes(lexicon('label_Story_CreateStatus', 'Create Status')) }}' : '{{ addslashes(lexicon('label_Story_EditStatus', 'Edit Status')) }}'"></span>
                </x-slot:top>

                <form :action="statusFormAction" 
                      :method="statusMethod" 
                      class="flex flex-col gap-md" 
                      x-data="ajaxForm" 
                      @submit.prevent="submit($event)">
                    @csrf
                    <input type="hidden" name="_method" :value="statusMethod">
                    
                    <x-form-input name="name"
                                  id="status-name-input"
                                  :label="lexicon('label_Story_StatusName', 'Status Name')"
                                  :tooltip="lexicon('tooltip_Story_StatusNameInput', 'Enter the display name for this book status')"
                                  xModel="statusName"
                                  :required="true" />

                    <div class="border-t-actions pt-6 text-right flex flex-row flex-wrap items-center justify-end gap-md">
                        <x-button type="submit" 
                                  variant="primary" 
                                  ::disabled="submitting" 
                                  x-tooltip="'{{ addslashes(lexicon('tooltip_Story_SaveStatus', 'Save book status')) }}'"
                                  aria-label="{{ lexicon('tooltip_Story_SaveStatus', 'Save book status') }}"
                                  x-text="submitting ? '{{ addslashes(lexicon('msg_common_Saving', 'Saving...')) }}' : '{{ addslashes(lexicon('btn_Story_SaveStatus', 'Save Status')) }}'">
                            {{ lexicon('btn_Story_SaveStatus', 'Save Status') }}
                        </x-button>
                    </div>
                </form>
            </x-modal>
        </x-slot:bottom>
        
    </x-admin-grid-wrapper>
</div>
@endsection