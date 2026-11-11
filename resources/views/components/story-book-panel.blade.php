@props([
    'book', // Represents the Volume instance in the Story Library Manager
    'isLoose' => false,
])

<div class="bg-panel border {{ $isLoose ? 'border-danger' : 'border-panel' }} rounded-panel shadow-panel flex flex-col overflow-hidden w-full"
     data-id="{{ $book->id }}"
     x-data="{ bookExpanded: false }"
     x-show="matchesVolume ? matchesVolume({{ json_encode($book->volume_title) }}, {{ json_encode($book->volume_synopsis ?? '') }}) : matchesBook({{ json_encode($book->volume_title) }}, {{ json_encode($book->volume_synopsis ?? '') }})">
    
    <div @click="if (!$event.target.closest('button')) bookExpanded = !bookExpanded" 
         class="p-3 w-full flex flex-row flex-wrap items-center justify-between gap-md text-left hover-bg-panel-hover border-b border-panel cursor-pointer select-none"
         x-tooltip="'{{ addslashes(lexicon('tooltip_common_TogglePanel', 'Click to expand or collapse panel')) }}'"
         aria-label="{{ lexicon('tooltip_common_TogglePanel', 'Click to expand or collapse panel') }}">
        
        <div class="flex flex-row flex-wrap items-center gap-sm">
            <i class="bi bi-grip-vertical text-muted cursor-move"
               x-tooltip="'{{ addslashes(lexicon('tooltip_Story_DragReorderVolume', 'Drag to reorder volume')) }}'"
               aria-label="{{ lexicon('tooltip_Story_DragReorderVolume', 'Drag to reorder volume') }}"></i>
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
                ({{ count($book->folios) }} {{ lexicon('label_Story_ChaptersCount', 'Folios') }})
            </span>
        </div>

        <div class="flex flex-row flex-wrap gap-md items-center" @click.stop>
            <x-button type="button" 
                      variant="text" 
                      class="text-warning hover-text-h text-xs flex flex-row items-center gap-xs" 
                      @click="$dispatch('open-volume-modal', {{ json_encode([
                          'mode'                 => 'edit', 
                          'id'                   => $book->id, 
                          'volume_title'         => $book->volume_title, 
                          'volume_synopsis'      => $book->volume_synopsis ?? '', 
                          'publication_state_id' => $book->publication_state_id, 
                          'ic_day'               => $book->ic_day, 
                          'ic_time'              => $book->ic_time, 
                          'shelf_id'             => $book->shelf_id ?? ''
                      ]) }})"
                      x-tooltip="'{{ addslashes(lexicon('tooltip_Story_EditVolume', 'Edit volume details')) }}'"
                      aria-label="{{ lexicon('tooltip_Story_EditVolume', 'Edit volume details') }}">
                <i class="bi bi-pencil"></i>
                <span>{{ lexicon('btn_Story_EditBook', 'Edit Volume') }}</span>
            </x-button>

            <x-button type="button" 
                      variant="text" 
                      class="text-danger hover-text-h text-xs flex flex-row items-center gap-xs" 
                      x-data="deleteAction('/admin/books/{{ $book->id }}', 'DELETE', '{{ addslashes($book->volume_title) }}')" 
                      @click.prevent="confirmDelete()"
                      x-tooltip="'{{ addslashes(lexicon('tooltip_Story_DeleteVolume', 'Delete this volume')) }}'"
                      aria-label="{{ lexicon('tooltip_Story_DeleteVolume', 'Delete this volume') }}">
                <i class="bi bi-trash"></i>
                <span>{{ lexicon('btn_common_Delete', 'Delete') }}</span>
            </x-button>
        </div>
    </div>

    <div x-show="bookExpanded" x-collapse x-cloak class="bg-panel p-panel flex flex-col gap-sm">
        @if(!empty($book->volume_synopsis))
            <p class="text-sm text-muted italic m-0 pb-2 border-b border-panel">
                {{ $book->volume_synopsis }}
            </p>
        @endif

        <div class="flex flex-col gap-xs sortable-folios"
             id="sortable-folios-{{ $book->id }}"
             data-volume-id="{{ $book->id }}"
             x-data="sortableHelper('/admin/chapters/reorder', 'folio_sort_order')">
            @forelse($book->folios as $folio)
                <div class="bg-panel-sub border border-panel rounded-input p-2 flex flex-row flex-wrap items-center justify-between gap-sm"
                     data-id="{{ $folio->id }}">
                    <div class="flex flex-row flex-wrap items-center gap-sm text-sm">
                        <i class="bi bi-grip-vertical text-muted cursor-move"></i>
                        <span class="font-bold text-p">#{{ $folio->id }}</span>
                        @if(!empty($folio->scene_location))
                            <span class="text-p">{{ $folio->scene_location }}</span>
                        @endif
                        <span class="text-xs text-muted">
                            (Day {{ $folio->ic_day ?? 1 }} @ {{ $folio->ic_time ?? '0800' }})
                        </span>
                    </div>

                    <div class="flex flex-row flex-wrap items-center gap-sm">
                        <x-button type="button"
                                  variant="text"
                                  class="text-accent hover-text-h text-xs flex flex-row items-center gap-xs"
                                  @click="$dispatch('open-move-folio-modal', {{ json_encode([
                                      'id'        => $folio->id,
                                      'volume_id' => $book->id
                                  ]) }})"
                                  x-tooltip="'{{ addslashes(lexicon('tooltip_Story_MoveFolio', 'Move this folio to another volume')) }}'"
                                  aria-label="{{ lexicon('tooltip_Story_MoveFolio', 'Move this folio to another volume') }}">
                            <i class="bi bi-arrow-left-right"></i>
                            <span>{{ lexicon('btn_Story_MoveFolio', 'Relocate') }}</span>
                        </x-button>
                    </div>
                </div>
            @empty
                <div class="p-2 text-center text-xs text-muted italic">
                    <span>{{ lexicon('msg_Story_NoFoliosInVolume', 'No folios recorded in this volume yet.') }}</span>
                </div>
            @endforelse
        </div>
    </div>
</div>