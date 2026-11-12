@props([
    'volume' => null,
    'book' => null, // Fallback if passed via legacy prop
    'isLoose' => false,
])

@php
    $volumeItem = $volume ?? $book;
@endphp

<div class="bg-panel border {{ $isLoose ? 'border-danger' : 'border-panel' }} rounded-panel shadow-panel flex flex-col overflow-hidden w-full"
     data-id="{{ $volumeItem->id }}"
     x-data="{ volumeExpanded: false }"
     x-show="matchesVolume({{ json_encode($volumeItem->volume_title) }}, {{ json_encode($volumeItem->volume_synopsis ?? '') }})">
    
    <div @click="if (!$event.target.closest('button')) volumeExpanded = !volumeExpanded" 
         class="p-3 w-full flex flex-row flex-wrap items-center justify-between gap-md text-left hover-bg-panel-hover border-b border-panel cursor-pointer select-none"
         x-tooltip="'{{ addslashes(lexicon('tooltip_common_TogglePanel', 'Click to expand or collapse panel')) }}'"
         aria-label="{{ lexicon('tooltip_common_TogglePanel', 'Click to expand or collapse panel') }}">
        
        <div class="flex flex-row flex-wrap items-center gap-sm">
            <i class="bi bi-grip-vertical text-muted cursor-move"
               x-tooltip="'{{ addslashes(lexicon('tooltip_Story_DragReorderVolume', 'Drag to reorder volume')) }}'"
               aria-label="{{ lexicon('tooltip_Story_DragReorderVolume', 'Drag to reorder volume') }}"></i>
            <span class="text-xs text-muted" x-text="volumeExpanded ? '▼' : '▶'"></span>
            <i class="bi bi-journal-bookmark text-accent"></i>
            <span class="text-lg font-bold text-p">{{ $volumeItem->volume_title }}</span>
            
            @if($isLoose)
                <x-badge variant="warning">
                    {{ lexicon('label_Story_NeedsShelf', 'Needs Shelf') }}
                </x-badge>
            @endif

            @if($volumeItem->publicationState)
                <x-badge :variant="$volumeItem->publicationState->book_url ?? 'primary'">
                    {{ $volumeItem->publicationState->state_title }}
                </x-badge>
            @endif

            <span class="text-xs text-muted">
                ({{ count($volumeItem->folios) }} {{ lexicon('label_Story_FoliosCount', 'Folios') }})
            </span>
        </div>

        <div class="flex flex-row flex-wrap gap-md items-center" @click.stop>
            <x-button type="button" 
                      variant="text" 
                      class="text-warning hover-text-h text-xs flex flex-row items-center gap-xs" 
                      @click="$dispatch('open-volume-modal', {{ json_encode([
                          'mode'                 => 'edit', 
                          'id'                   => $volumeItem->id, 
                          'volume_title'         => $volumeItem->volume_title, 
                          'volume_synopsis'      => $volumeItem->volume_synopsis ?? '', 
                          'publication_state_id' => $volumeItem->publication_state_id, 
                          'ic_day'               => $volumeItem->ic_day, 
                          'ic_time'              => $volumeItem->ic_time, 
                          'shelf_id'             => $volumeItem->shelf_id ?? ''
                      ]) }})"
                      x-tooltip="'{{ addslashes(lexicon('tooltip_Story_EditVolume', 'Edit volume details')) }}'"
                      aria-label="{{ lexicon('tooltip_Story_EditVolume', 'Edit volume details') }}">
                <i class="bi bi-pencil"></i>
                <span>{{ lexicon('btn_Story_EditVolume', 'Edit Volume') }}</span>
            </x-button>

            <x-button type="button" 
                      variant="text" 
                      class="text-danger hover-text-h text-xs flex flex-row items-center gap-xs" 
                      x-data="deleteAction('/admin/volumes/{{ $volumeItem->id }}', 'DELETE', '{{ addslashes($volumeItem->volume_title) }}')" 
                      @click.prevent="confirmDelete()"
                      x-tooltip="'{{ addslashes(lexicon('tooltip_Story_DeleteVolume', 'Delete this volume')) }}'"
                      aria-label="{{ lexicon('tooltip_Story_DeleteVolume', 'Delete this volume') }}">
                <i class="bi bi-trash"></i>
                <span>{{ lexicon('btn_common_Delete', 'Delete') }}</span>
            </x-button>
        </div>
    </div>

    <div x-show="volumeExpanded" x-collapse x-cloak class="bg-panel p-panel flex flex-col gap-sm">
        @if(!empty($volumeItem->volume_synopsis))
            <p class="text-sm text-muted italic m-0 pb-2 border-b border-panel">
                {{ $volumeItem->volume_synopsis }}
            </p>
        @endif

        <div class="flex flex-col gap-xs sortable-folios"
             id="sortable-folios-{{ $volumeItem->id }}"
             data-volume-id="{{ $volumeItem->id }}"
             x-data="sortableHelper('/admin/folios/reorder', 'folio_sort_order')">
            @forelse($volumeItem->folios as $folio)
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
                                      'volume_id' => $volumeItem->id
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