{{-- resources/views/components/tab.blade.php --}}
@props([
    'type' => null,
    'title' => '',
    'icon' => null,
    'open' => false,
    'expanded' => null,
    'activeTab' => null,
    'tabs' => [],
    'headerClass' => '',
    'volume' => null,
    'book' => null,
    'isLoose' => false,
    'tooltip' => null,
])

@php
    $volumeItem = $volume ?? $book;
    $isOpen =$expanded !== null ? (bool) $expanded : (bool)$open;
    $type = $type ?? ($volumeItem ? 'volume' : 'accordion');
    $toggleTooltip =$tooltip ?? lexicon('tooltip_common_TogglePanel', 'Click to expand or collapse panel');
@endphp

@switch($type)
    @case('sub')
        <div {{ $attributes->except('headerClass')->merge(['class' => 'bg-panel-sub border-panel border-rounded p-3 flex flex-col gap-sm w-full']) }}>
            @if($title || isset($top))
                <div class="flex flex-row flex-wrap justify-between items-center gap-sm">
                    @if($title)
                        <span class="text-xs font-bold text-h uppercase text-wider flex flex-row items-center gap-xs">
                            @if($icon)
                                <i class="bi {{ $icon }} text-accent"></i>
                            @endif
                            <span>{{ $title }}</span>
                        </span>
                    @endif
                    @if(isset($top))
                        <div class="flex flex-row flex-wrap items-center gap-sm">
                            {{ $top }}
                        </div>
                    @endif
                </div>
            @endif
            <div class="flex flex-col gap-sm w-full">
                {{ $slot }}
            </div>
        </div>
        @break

    @case('panel')
        <div {{ $attributes->except('headerClass')->merge(['class' => 'bg-panel-body border-panel border-rounded shadow-panel overflow-hidden flex flex-col w-full']) }}>
            @if($title || isset($top))
                <div class="bg-panel-header text-h border-b-panel p-panel-header flex flex-row flex-wrap justify-between items-center gap-md {{ $headerClass }}">
                    <div class="flex flex-row flex-wrap items-center gap-sm">
                        @if($icon)
                            <i class="bi {{ $icon }} text-accent"></i>
                        @endif
                        <span class="font-bold text-h">{{ $title }}</span>
                    </div>
                    @if(isset($top))
                        <div class="flex flex-row flex-wrap items-center gap-sm">
                            {{ $top }}
                        </div>
                    @endif
                </div>
            @endif
            <div class="p-panel-body flex flex-col gap-md flex-grow">
                {{ $slot }}
            </div>
        </div>
        @break

    @case('tabs')
        <div {{ $attributes->except('headerClass')->merge(['class' => 'bg-panel-body border-panel border-rounded shadow-panel overflow-hidden flex flex-col w-full']) }}
             @if($activeTab) x-data="{ activeTab: @js($activeTab) }" @endif>
            <div class="bg-panel-header border-b-panel p-2 flex flex-row flex-wrap items-center justify-between gap-sm {{ $headerClass }}">
                <div class="flex flex-row flex-wrap items-center gap-xs">
                    @if(!empty($tabs))
                        @foreach($tabs as $tabKey =>$tabLabel)
                            <button type="button"
                                    @click="activeTab = '{{ $tabKey }}'"
                                    :class="activeTab === '{{ $tabKey }}' ? 'bg-primary text-h border-accent' : 'bg-transparent text-muted hover-bg-panel-hover hover-text-h'"
                                    class="p-btn-sm text-xs font-bold border-rounded-sm cursor-pointer transition-colors">
                                {{ $tabLabel }}
                            </button>
                        @endforeach
                    @elseif(isset($top))
                        {{ $top }}
                    @endif
                </div>
                @if(!empty($tabs) && isset($top))
                    <div class="flex flex-row flex-wrap items-center gap-sm">
                        {{ $top }}
                    </div>
                @endif
            </div>
            <div class="p-panel-body flex flex-col gap-md flex-grow">
                {{ $slot }}
            </div>
        </div>
        @break

    @case('volume')
        <div class="bg-panel-body {{ $isLoose ? 'border-danger' : 'border-panel' }} border-rounded shadow-panel flex flex-col overflow-hidden w-full"
             data-id="{{ $volumeItem->id }}"
             x-data="{ volumeExpanded: @js($isOpen) }"
             x-show="typeof matchesVolume === 'function' ? matchesVolume({{ json_encode($volumeItem->volume_title) }}, {{ json_encode($volumeItem->volume_synopsis ?? '') }}) : true">

            <div @click="if (!$event.target.closest('button')) volumeExpanded = !volumeExpanded"
                 class="bg-panel-header text-h border-b-panel p-panel-header flex flex-row flex-wrap justify-between items-center gap-md cursor-pointer select-none transition-colors hover-bg-panel-hover"
                 x-tooltip="'{{ addslashes($toggleTooltip) }}'"
                 title="{{ $toggleTooltip }}"
                 aria-label="{{ $toggleTooltip }}">

                <div class="flex flex-row flex-wrap items-center gap-sm">
                    <i class="bi bi-grip-vertical text-muted cursor-pointer"
                       x-tooltip="'{{ addslashes(lexicon('tooltip_Story_DragReorderVolume', 'Drag to reorder volume')) }}'"
                       title="{{ lexicon('tooltip_Story_DragReorderVolume', 'Drag to reorder volume') }}"
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

                <div class="flex flex-row flex-wrap gap-sm items-center" @click.stop>
                    <x-button type="button"
                              variant="ghost"
                              size="sm"
                              icon="bi-pencil"
                              :tooltip="lexicon('tooltip_Story_EditVolume', 'Edit volume details')"
                              @click="$dispatch('open-volume-modal', {{ json_encode([
                                  'mode'                 => 'edit',
                                  'id'                   => $volumeItem->id,
                                  'volume_title'         => $volumeItem->volume_title,
                                  'volume_synopsis'      => $volumeItem->volume_synopsis ?? '',
                                  'publication_state_id' => $volumeItem->publication_state_id,
                                  'ic_day'               => $volumeItem->ic_day,
                                  'ic_time'              => $volumeItem->ic_time,
                                  'shelf_id'             => $volumeItem->shelf_id ?? ''
                              ]) }})">
                        {{ lexicon('btn_Story_EditVolume', 'Edit Volume') }}
                    </x-button>

                    <x-button type="button"
                              variant="danger"
                              size="sm"
                              icon="bi-trash"
                              :tooltip="lexicon('tooltip_Story_DeleteVolume', 'Delete this volume')"
                              x-data="deleteAction('/admin/volumes/{{ $volumeItem->id }}', 'DELETE', '{{ addslashes($volumeItem->volume_title) }}')"
                              @click.prevent="confirmDelete()">
                        {{ lexicon('btn_common_Delete', 'Delete') }}
                    </x-button>
                </div>
            </div>

            <div x-show="volumeExpanded" x-collapse x-cloak class="p-panel-body flex flex-col gap-md">
                @if(!empty($volumeItem->volume_synopsis))
                    <p class="text-sm text-muted font-italic m-0 pb-2 border-b-panel">
                        {{ $volumeItem->volume_synopsis }}
                    </p>
                @endif

                <div class="flex flex-col gap-xs sortable-folios"
                     id="sortable-folios-{{ $volumeItem->id }}"
                     data-volume-id="{{ $volumeItem->id }}"
                     x-data="sortableHelper('/admin/folios/reorder', 'folio_sort_order')">
                    @forelse($volumeItem->folios as $folio)
                        <div class="bg-panel-sub border-panel border-rounded p-2 flex flex-row flex-wrap items-center justify-between gap-sm"
                             data-id="{{ $folio->id }}">
                            <div class="flex flex-row flex-wrap items-center gap-sm text-sm">
                                <i class="bi bi-grip-vertical text-muted cursor-pointer"></i>
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
                                          variant="ghost"
                                          size="sm"
                                          icon="bi-arrow-left-right"
                                          :tooltip="lexicon('tooltip_Story_MoveFolio', 'Move this folio to another volume')"
                                          @click="$dispatch('open-move-folio-modal', {{ json_encode([
                                              'id'        => $folio->id,
                                              'volume_id' => $volumeItem->id
                                          ]) }})">
                                    {{ lexicon('btn_Story_MoveFolio', 'Relocate') }}
                                </x-button>
                            </div>
                        </div>
                    @empty
                        <div class="p-2 text-center text-xs text-muted font-italic">
                            <span>{{ lexicon('msg_Story_NoFoliosInVolume', 'No folios recorded in this volume yet.') }}</span>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
        @break

    @case('accordion')
    @default
        <div {{ $attributes->except('headerClass')->merge(['class' => 'bg-panel-body border-panel border-rounded shadow-panel overflow-hidden flex flex-col w-full']) }}
             x-data="{ expanded: @js($isOpen) }">
            <div class="bg-panel-header text-h border-b-panel p-panel-header flex flex-row flex-wrap justify-between items-center gap-md cursor-pointer select-none transition-colors hover-bg-panel-hover {{ $headerClass }}"
                 @click="expanded = !expanded"
                 x-tooltip="'{{ addslashes($toggleTooltip) }}'"
                 title="{{ $toggleTooltip }}"
                 aria-label="{{ $toggleTooltip }}">
                <div class="flex flex-row flex-wrap items-center gap-sm">
                    <span class="text-xs text-muted" x-text="expanded ? '▼' : '▶'"></span>
                    @if($icon)
                        <i class="bi {{ $icon }} text-accent"></i>
                    @endif
                    <span class="font-bold text-h">{{ $title }}</span>
                </div>
                @if(isset($top))
                    <div class="flex flex-row flex-wrap items-center gap-sm" @click.stop>
                        {{ $top }}
                    </div>
                @endif
            </div>
            <div x-show="expanded" x-collapse x-cloak class="p-panel-body flex flex-col gap-md flex-grow">
                {{ $slot }}
            </div>
        </div>
@endswitch