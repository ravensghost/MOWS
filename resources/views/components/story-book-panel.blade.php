@props([
    'book', // Represents the Volume instance in the story manager
    'isLoose' => false,
])

<div class="bg-panel border {{ $isLoose ? 'border-danger' : 'border-panel' }} rounded-panel shadow-panel flex flex-col overflow-hidden w-full"
     data-id="{{ $book->id }}"
     x-data="{ bookExpanded: false }"
     x-show="matchesBook({{ json_encode($book->volume_title) }}, {{ json_encode($book->volume_synopsis ?? '') }})">
    
    <div @click="if (!$event.target.closest('button')) bookExpanded = !bookExpanded" 
         class="p-3 w-full flex flex-row flex-wrap items-center justify-between gap-md text-left hover-bg-panel-hover border-b border-panel cursor-pointer select-none"
         x-tooltip="'{{ addslashes(lexicon('tooltip_common_TogglePanel', 'Click to expand or collapse panel')) }}'">
        
        <div class="flex flex-row flex-wrap items-center gap-sm">
            <i class="bi bi-grip-vertical text-muted cursor-move"></i>
            <span class="text-xs text-muted" x-text="bookExpanded ? '▼' : '▶'"></span>
            <i class="bi bi-journal-bookmark text-accent"></i>
            <span class="text-lg font-bold text-p">{{ $book->volume_title }}</span>
            
            @if($isLoose)
                <x-badge variant="warning">
                    {{ lexicon('label_Story_NeedsBookcase', 'Needs Shelf') }}
                </x-badge>
            @endif

            @if($book->publicationState)
                <x-badge :variant="$book->publicationState->book_url ?? 'primary'">
                    {{ $book->publicationState->state_title }}
                </x-badge>
            @endif

            <span class="text-xs text-muted">
                ({{ count($book->folios) }} {{ lexicon('label_Story_ChaptersCount', 'Chapters') }})
            </span>
        </div>

        <div class="flex flex-row flex-wrap gap-md items-center" @click.stop>
            <x-button type="button" 
                      variant="text" 
                      class="text-warning hover-text-h text-xs flex flex-row items-center gap-xs" 
                      @click="$dispatch('open-book-modal', {{ json_encode([
                          'mode' => 'edit', 
                          'id' => $book->id, 
                          'title' => $book->volume_title, 
                          'desc' => $book->volume_synopsis, 
                          'publicationStateId' => $book->publication_state_id, 
                          'day' => $book->ic_day, 
                          'time' => $book->ic_time, 
                          'shelfId' => $book->shelf_id ?? ''
                      ]) }})">
                <i class="bi bi-pencil"></i>
                <span>{{ lexicon('btn_Story_EditBook', 'Edit Volume') }}</span>
            </x-button>
        </div>
    </div>
</div>