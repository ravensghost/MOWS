@props([
    'book',
    'isLoose' => false,
])

<div class="bg-panel border {{ $isLoose ? 'border-danger' : 'border-panel' }} rounded-panel shadow-panel flex flex-col overflow-hidden w-full"
     data-id="{{ $book->id }}"
     x-data="{ bookExpanded: false }"
     x-show="matchesBook({{ json_encode($book->title) }}, {{ json_encode($book->description ?? '') }})">
    
    <div @click="if (!$event.target.closest('button')) bookExpanded = !bookExpanded" 
         class="p-3 w-full flex flex-row flex-wrap items-center justify-between gap-md text-left hover-bg-panel-hover border-b border-panel cursor-pointer select-none"
         x-tooltip="'{{ addslashes(lexicon('tooltip_common_TogglePanel', 'Click to expand or collapse panel')) }}'"
         aria-label="{{ lexicon('tooltip_common_TogglePanel', 'Click to expand or collapse panel') }}">
        
        <div class="flex flex-row flex-wrap items-center gap-sm">
            <i class="bi bi-grip-vertical text-muted cursor-move"
               x-tooltip="'{{ addslashes(lexicon('tooltip_Story_DragReorderBook', 'Drag to reorder book')) }}'"
               aria-label="{{ lexicon('tooltip_Story_DragReorderBook', 'Drag to reorder book') }}"></i>
            <span class="text-xs text-muted" x-text="bookExpanded ? '▼' : '▶'"></span>
            <i class="bi bi-journal-bookmark text-accent"></i>
            <span class="text-lg font-bold text-p">{{ $book->title }}</span>
            
            @if($isLoose)
                <x-badge variant="warning"
                         x-tooltip="'{{ addslashes(lexicon('tooltip_Story_NeedsBookcaseBadge', 'This book is not assigned to any bookcase')) }}'"
                         aria-label="{{ lexicon('tooltip_Story_NeedsBookcaseBadge', 'This book is not assigned to any bookcase') }}">
                    {{ lexicon('label_Story_NeedsBookcase', 'Needs Bookcase') }}
                </x-badge>
            @endif

            @if($book->status)
                <x-badge :variant="$book->status->slug ?? 'primary'"
                         x-tooltip="'{{ addslashes(lexicon('tooltip_Story_BookStatusBadge', 'Current publication status of this book')) }}'"
                         aria-label="{{ lexicon('tooltip_Story_BookStatusBadge', 'Current publication status of this book') }}">
                    {{ $book->status->name }}
                </x-badge>
            @endif

            <span class="text-xs text-muted">
                ({{ count($book->chapters) }} {{ lexicon('label_Story_ChaptersCount', 'Chapters') }})
            </span>
        </div>

        <div class="flex flex-row flex-wrap gap-md items-center" @click.stop>
            <x-button type="button" 
                      variant="text" 
                      class="text-warning hover-text-h text-xs flex flex-row items-center gap-xs" 
                      @click="$dispatch('open-book-modal', {{ json_encode([
                          'mode' => 'edit', 
                          'id' => $book->id, 
                          'title' => $book->title, 
                          'desc' => $book->description, 
                          'bookStatusId' => $book->status_id, 
                          'day' => $book->ic_day, 
                          'time' => $book->ic_time, 
                          'bookcaseId' => $book->bookcase_id ?? ''
                      ]) }})"
                      x-tooltip="'{{ addslashes(lexicon('tooltip_Story_EditBook', 'Edit book details and status')) }}'"
                      aria-label="{{ lexicon('tooltip_Story_EditBook', 'Edit book details and status') }}">
                <i class="bi bi-pencil"></i>
                <span>{{ lexicon('btn_Story_EditBook', 'Edit Book') }}</span>
            </x-button>

            <x-button type="button" 
                      variant="text" 
                      class="text-danger hover-text-h text-sm flex flex-row items-center gap-xs" 
                      x-data="deleteAction('/admin/books/{{ $book->id }}', 'DELETE', '{{ addslashes($book->title) }}')" 
                      @click.prevent="confirmDelete()"
                      x-tooltip="'{{ addslashes(lexicon('tooltip_Story_DeleteBook', 'Delete this book and its chapters')) }}'"
                      aria-label="{{ lexicon('tooltip_Story_DeleteBook', 'Delete this book and its chapters') }}">
                <i class="bi bi-trash"></i>
                <span>{{ lexicon('btn_common_Delete', 'Delete') }}</span>
            </x-button>
        </div>
    </div>

    <div x-show="bookExpanded" x-collapse x-cloak class="flex flex-col w-full box-border">
        @php
            $tbodyAttrs = 'class="sortable-chapters" id="sortable-chapters-' . $book->id . '" data-book-id="' . $book->id . '" x-data="sortableManager(\'/admin/chapters/reorder\')"';
        @endphp
        <x-table :tbodyAttributes="$tbodyAttrs">
            <x-slot:headers>
                <th class="p-3 text-left">{{ lexicon('label_common_Id', 'ID') }}</th>
                <th class="p-3 text-left">{{ lexicon('label_WriteChapter_ChapterType', 'Type') }}</th>
                <th class="p-3 text-left">{{ lexicon('label_Story_DayTime', 'Day / Time') }}</th>
                <th class="p-3 text-left">{{ lexicon('label_Story_LsvScore', 'LSV Score') }}</th>
                <th class="p-3 text-right">{{ lexicon('label_common_Actions', 'Actions') }}</th>
            </x-slot:headers>

            @forelse($book->chapters as $chapter)
                <tr class="border-b-table-row hover-bg-table-row" data-id="{{ $chapter->id }}">
                    <td class="p-3 font-mono text-xs text-muted">
                        <div class="flex flex-row items-center gap-xs">
                            <i class="bi bi-grip-vertical text-muted cursor-move"
                               x-tooltip="'{{ addslashes(lexicon('tooltip_Story_DragReorderChapter', 'Drag to reorder chapter')) }}'"
                               aria-label="{{ lexicon('tooltip_Story_DragReorderChapter', 'Drag to reorder chapter') }}"></i>
                            <span>#{{ $chapter->id }}</span>
                        </div>
                    </td>
                    <td class="p-3 font-bold text-p text-sm">{{ ucfirst(str_replace('_', ' ', $chapter->chapter_type)) }}</td>
                    <td class="p-3 text-xs text-muted">{{ lexicon('label_common_Day', 'Day') }} {{ $chapter->ic_day }} | {{ $chapter->ic_time }}</td>
                    <td class="p-3 text-xs font-mono">
                        <x-badge variant="info"
                                 x-tooltip="'{{ addslashes(lexicon('tooltip_Story_ChapterLsvBadge', 'Language, Sex, and Violence content rating score')) }}'"
                                 aria-label="{{ lexicon('tooltip_Story_ChapterLsvBadge', 'Language, Sex, and Violence content rating score') }}">
                            L{{ $chapter->rating_l ?? 0 }} S{{ $chapter->rating_s ?? 0 }} V{{ $chapter->rating_v ?? 0 }}
                        </x-badge>
                    </td>
                    <td class="p-3 text-right">
                        <div class="flex flex-row flex-wrap gap-sm items-center justify-end">
                            <x-button type="button" 
                                    variant="text" 
                                    class="text-warning hover-text-h flex flex-row items-center gap-xs" 
                                    @click="$dispatch('open-chapter-modal', { chapterId: {{ $chapter->id }} })"
                                    x-tooltip="'{{ addslashes(lexicon('tooltip_Story_EditChapter', 'Edit this chapter')) }}'">
                                <i class="bi bi-pencil"></i>
                                <span>{{ lexicon('btn_common_Edit', 'Edit') }}</span>
                            </x-button>
                            <x-button type="button" 
                                      variant="text" 
                                      class="text-accent hover-text-h text-xs flex flex-row items-center gap-xs" 
                                      @click="$dispatch('open-move-chapter-modal', {{ json_encode(['chapterId' => $chapter->id, 'bookId' => $book->id]) }})"
                                      x-tooltip="'{{ addslashes(lexicon('tooltip_Story_MoveChapter', 'Relocate this chapter to another book')) }}'"
                                      aria-label="{{ lexicon('tooltip_Story_MoveChapter', 'Relocate this chapter to another book') }}">
                                <i class="bi bi-arrows-move"></i>
                                <span>{{ lexicon('btn_Story_MoveChapter', 'Move') }}</span>
                            </x-button>
                            <x-button type="button" 
                                      variant="text" 
                                      class="text-danger hover-text-h text-sm flex flex-row items-center gap-xs" 
                                      x-data="deleteAction('/admin/chapters/{{ $chapter->id }}', 'DELETE', '{{ addslashes($chapter->title ?? lexicon('label_common_Chapter', 'Chapter') . ' #' . $chapter->id) }}')" 
                                      @click.prevent="confirmDelete()"
                                      x-tooltip="'{{ addslashes(lexicon('tooltip_Story_DeleteChapter', 'Delete this chapter permanently')) }}'"
                                      aria-label="{{ lexicon('tooltip_Story_DeleteChapter', 'Delete this chapter permanently') }}">
                                <i class="bi bi-trash"></i>
                                <span>{{ lexicon('btn_common_Delete', 'Delete') }}</span>
                            </x-button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="p-4 text-center text-muted italic">{{ lexicon('msg_Story_NoChapters', 'No chapters in this book yet.') }}</td>
                </tr>
            @endforelse
        </x-table>
    </div>
</div>