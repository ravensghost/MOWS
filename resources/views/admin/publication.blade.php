@extends('layouts.app')

@section('title', lexicon('admin_label_story', 'Story Library Manager'))

@section('content')
<div x-data="publicationManager" 
     @ajax-success.window="refreshDOM($event.detail)"
     @open-status-modal.window="openModal($event.detail.mode, $event.detail, 'status')"
     @open-shelf-modal.window="openModal($event.detail.mode, $event.detail, 'shelf')"
     @open-volume-modal.window="openModal($event.detail.mode, $event.detail, 'volume')"
     @open-move-folio-modal.window="openModal('edit', $event.detail, 'move')">

    <x-admin-grid-wrapper :page="$systemPage">
        
        <x-slot:top>
            <script type="application/json" x-ref="publicationSearchData">
                {!! json_encode($shelves->map(fn($sh) => [
                    'id' => $sh->id,
                    'shelf_title' => $sh->shelf_title,
                    'volumes' => $sh->volumes->map(fn($v) => [
                        'volume_title' => $v->volume_title,
                        'volume_synopsis' => $v->volume_synopsis ?? ''
                    ])->values()
                ])->values()) !!}
            </script>

            <x-page-header :title="lexicon('admin_label_story', 'Story Library Manager')">
                <x-slot:actions>
                    <x-button type="button" 
                              variant="success" 
                              class="p-btn-sm text-sm flex flex-row items-center gap-xs" 
                              @click="openModal('create', {}, 'shelf')"
                              x-tooltip="'{{ addslashes(lexicon('tooltip_Story_AddShelf', 'Create a new shelf to organise volumes')) }}'"
                              aria-label="{{ lexicon('tooltip_Story_AddShelf', 'Create a new shelf to organise volumes') }}">
                        <i class="bi bi-plus-lg"></i>
                        <span>{{ lexicon('btn_Story_AddShelf', 'New Shelf') }}</span>
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
                                  :placeholder="lexicon('label_Story_SearchPlaceholder', 'Search shelves or volumes by title and synopsis...')"
                                  :tooltip="lexicon('tooltip_Story_SearchInput', 'Instantaneously filter shelves and volumes using fuzzy search')"
                                  xModel="searchQuery"
                                  @input="filterLibrary()" />
                </div>

                {{-- Shelves & Nested Volumes --}}
                <div id="shelves-wrapper" x-ref="shelvesWrapper">
                    <div class="flex flex-col gap-lg" id="sortable-shelves" x-data="sortableHelper('/admin/shelves/reorder', 'shelf_sort_order')">
                        @forelse($shelves as $shelf)
                            <div class="bg-panel border border-panel rounded-panel shadow-panel flex flex-col overflow-hidden w-full" 
                                 data-id="{{ $shelf->id }}" 
                                 x-data="{ expanded: false }"
                                 x-show="matchesShelf({{ $shelf->id }}, {{ json_encode($shelf->shelf_title) }})">
                                
                                <div @click="if (!$event.target.closest('button')) expanded = !expanded" 
                                     class="bg-panel-header text-h border-b border-panel p-panel-header font-bold w-full flex flex-row flex-wrap items-center justify-between gap-md text-left cursor-pointer select-none"
                                     x-tooltip="'{{ addslashes(lexicon('tooltip_common_TogglePanel', 'Click to expand or collapse panel')) }}'"
                                     aria-label="{{ lexicon('tooltip_common_TogglePanel', 'Click to expand or collapse panel') }}">
                                    
                                    <div class="flex flex-row flex-wrap items-center gap-sm">
                                        <i class="bi bi-grip-vertical text-muted cursor-move"
                                           x-tooltip="'{{ addslashes(lexicon('tooltip_Story_DragReorderShelf', 'Drag to reorder shelf')) }}'"
                                           aria-label="{{ lexicon('tooltip_Story_DragReorderShelf', 'Drag to reorder shelf') }}"></i>
                                        <span class="text-xs text-muted" x-text="expanded ? '▼' : '▶'"></span>
                                        <i class="bi bi-collection text-accent"></i>
                                        <span class="text-xl font-bold text-h">{{ $shelf->shelf_title }}</span> 
                                        <span class="text-xs text-muted">({{ count($shelf->volumes) }} {{ lexicon('label_Story_VolumesCount', 'Volumes') }})</span>
                                        <x-button type="button" 
                                                  variant="success" 
                                                  class="p-btn-sm text-xs flex flex-row items-center gap-xs" 
                                                  @click.stop="openModal('create', { shelf_id: '{{ $shelf->id }}' }, 'volume')"
                                                  x-tooltip="'{{ addslashes(lexicon('tooltip_Story_AddVolume', 'Add a new volume to this shelf')) }}'"
                                                  aria-label="{{ lexicon('tooltip_Story_AddVolume', 'Add a new volume to this shelf') }}">
                                            <i class="bi bi-plus-lg"></i>
                                            <span>{{ lexicon('btn_Story_AddVolume', 'New Volume') }}</span>
                                        </x-button>
                                    </div>

                                    <div class="flex flex-row flex-wrap gap-md items-center" @click.stop>
                                        <x-button type="button" 
                                                  variant="text" 
                                                  class="text-warning hover-text-h text-sm flex flex-row items-center gap-xs" 
                                                  @click="openModal('edit', {{ json_encode(['id' => $shelf->id, 'shelf_title' => $shelf->shelf_title]) }}, 'shelf')"
                                                  x-tooltip="'{{ addslashes(lexicon('tooltip_Story_EditShelf', 'Rename this shelf')) }}'"
                                                  aria-label="{{ lexicon('tooltip_Story_EditShelf', 'Rename this shelf') }}">
                                            <i class="bi bi-pencil"></i>
                                            <span>{{ lexicon('btn_common_Edit', 'Edit') }}</span>
                                        </x-button>
                                        <x-button type="button" 
                                                  variant="text" 
                                                  class="text-danger hover-text-h text-sm flex flex-row items-center gap-xs" 
                                                  x-data="deleteAction('/admin/shelves/{{ $shelf->id }}', 'DELETE', '{{ addslashes($shelf->shelf_title) }}')" 
                                                  @click.prevent="confirmDelete()"
                                                  x-tooltip="'{{ addslashes(lexicon('tooltip_Story_DeleteShelf', 'Delete this shelf')) }}'"
                                                  aria-label="{{ lexicon('tooltip_Story_DeleteShelf', 'Delete this shelf') }}">
                                            <i class="bi bi-trash"></i>
                                            <span>{{ lexicon('btn_common_Delete', 'Delete') }}</span>
                                        </x-button>
                                    </div>
                                </div>
                                
                                <div x-show="expanded || searchQuery.trim().length > 0" x-collapse x-cloak 
                                     class="bg-panel-sub flex flex-col gap-md p-panel w-full box-border sortable-volumes" 
                                     id="sortable-volumes-{{ $shelf->id }}" 
                                     data-shelf-id="{{ $shelf->id }}"
                                     x-data="sortableHelper('/admin/volumes/reorder', 'volume_sort_order')">
                                    @forelse($shelf->volumes as $volume)
                                        <x-volume-panel :volume="$volume" :isLoose="false" />
                                    @empty
                                        <div class="p-4 text-center text-muted italic">
                                            <span>{{ lexicon('msg_Story_NoVolumesInShelf', 'No volumes placed on this shelf.') }}</span>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        @empty
                            <div class="bg-panel border border-panel rounded-panel shadow-panel p-6 text-center text-muted italic">
                                <span>{{ lexicon('msg_Story_NoShelves', 'No shelves built yet.') }}</span>
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- Loose / Unassigned Volumes --}}
                <div id="loose-volumes-wrapper" x-ref="looseVolumesWrapper">
                    @if(count($looseVolumes) > 0)
                        <x-accordion-panel :title="lexicon('label_Story_LooseVolumes', 'Loose Volumes (Unassigned)')" :expanded="false">
                            <div class="flex flex-col gap-md" id="sortable-loose-volumes" x-data="sortableHelper('/admin/volumes/reorder', 'volume_sort_order')">
                                @foreach($looseVolumes as $volume)
                                    <x-volume-panel :volume="$volume" :isLoose="true" />
                                @endforeach
                            </div>
                        </x-accordion-panel>
                    @endif
                </div>

                {{-- Publication States Management --}}
                <div id="statuses-wrapper" x-ref="statusesWrapper">
                    <x-accordion-panel :title="lexicon('admin_label_PublicationStates', 'Publication States')" :expanded="false">
                        <x-slot:top>
                            <x-button type="button" 
                                      variant="success" 
                                      class="p-btn-sm text-sm flex flex-row items-center gap-xs" 
                                      @click="openModal('create', {}, 'status')"
                                      x-tooltip="'{{ addslashes(lexicon('tooltip_Story_AddStatus', 'Create a new publication state')) }}'"
                                      aria-label="{{ lexicon('tooltip_Story_AddStatus', 'Create a new publication state') }}">
                                <i class="bi bi-plus-lg"></i>
                                <span>{{ lexicon('btn_Story_AddStatus', 'New State') }}</span>
                            </x-button>
                        </x-slot:top>
                    
                        <x-table>
                            <x-slot:headers>
                                <th class="p-4 text-left">{{ lexicon('label_Story_StatusName', 'State Title') }}</th>
                                <th class="p-4 text-right">{{ lexicon('label_common_Actions', 'Actions') }}</th>
                            </x-slot:headers>

                            @forelse($statuses as $status)
                                <tr class="border-b-table-row hover-bg-table-row">
                                    <td class="p-4 font-bold text-p">
                                        <x-badge :variant="$status->book_url ?? 'primary'"
                                                 x-tooltip="'{{ addslashes(lexicon('tooltip_Story_StatusPreviewBadge', 'State badge preview')) }}'"
                                                 aria-label="{{ lexicon('tooltip_Story_StatusPreviewBadge', 'State badge preview') }}">
                                            {{ $status->state_title }}
                                        </x-badge>
                                    </td>
                                    <td class="p-4 text-right">
                                        <div class="flex flex-row flex-wrap gap-sm items-center justify-end">
                                            <x-button type="button" 
                                                      variant="text" 
                                                      class="text-warning hover-text-h text-xs flex flex-row items-center gap-xs" 
                                                      @click="openModal('edit', {{ json_encode(['id' => $status->id, 'state_title' => $status->state_title]) }}, 'status')"
                                                      x-tooltip="'{{ addslashes(lexicon('tooltip_Story_EditStatus', 'Edit publication state')) }}'"
                                                      aria-label="{{ lexicon('tooltip_Story_EditStatus', 'Edit publication state') }}">
                                                <i class="bi bi-pencil"></i>
                                                <span>{{ lexicon('btn_common_Edit', 'Edit') }}</span>
                                            </x-button>
                                            <x-button type="button" 
                                                      variant="text" 
                                                      class="text-danger hover-text-h text-sm flex flex-row items-center gap-xs" 
                                                      x-data="deleteAction('/admin/statuses/{{ $status->id }}', 'DELETE', '{{ addslashes($status->state_title) }}')" 
                                                      @click.prevent="confirmDelete()"
                                                      x-tooltip="'{{ addslashes(lexicon('tooltip_Story_DeleteStatus', 'Delete this publication state')) }}'"
                                                      aria-label="{{ lexicon('tooltip_Story_DeleteStatus', 'Delete this publication state') }}">
                                                <i class="bi bi-trash"></i>
                                                <span>{{ lexicon('btn_common_Delete', 'Delete') }}</span>
                                            </x-button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="p-6 text-center text-muted italic">{{ lexicon('msg_Story_NoStatuses', 'No publication states created yet.') }}</td>
                                </tr>
                            @endforelse
                        </x-table>
                    </x-accordion-panel>
                </div>

            </div>
        </x-slot:middle>

        <x-slot:bottom>
            {{-- MODAL 1: Shelf Modal --}}
            <x-modal show="shelfModal.open" maxWidth="max-w-lg">
                <x-slot:top>
                    <span x-text="shelfModal.mode === 'create' ? '{{ addslashes(lexicon('label_Story_CreateShelf', 'Build Shelf')) }}' : '{{ addslashes(lexicon('label_Story_EditShelf', 'Edit Shelf')) }}'"></span>
                </x-slot:top>

                <form :action="shelfModal.action" 
                      method="POST" 
                      class="flex flex-col gap-md" 
                      x-data="ajaxForm" 
                      @submit.prevent="submit($event)">
                    @csrf
                    <input type="hidden" name="_method" :value="shelfModal.method">
                    
                    <x-form-input name="shelf_title"
                                  id="shelf-title-input"
                                  :label="lexicon('label_Story_ShelfName', 'Shelf Title')"
                                  :tooltip="lexicon('tooltip_Story_ShelfNameInput', 'Enter the display title for this shelf')"
                                  xModel="shelfModal.shelf_title"
                                  :required="true" />

                    <div class="border-t-actions pt-6 text-right flex flex-row flex-wrap items-center justify-end gap-md">
                        <x-button type="submit" 
                                  variant="primary" 
                                  ::disabled="submitting"
                                  x-tooltip="'{{ addslashes(lexicon('tooltip_Story_SaveShelf', 'Save shelf changes')) }}'"
                                  aria-label="{{ lexicon('tooltip_Story_SaveShelf', 'Save shelf changes') }}">
                            <span x-text="submitting ? '{{ addslashes(lexicon('msg_common_Saving', 'Saving...')) }}' : '{{ addslashes(lexicon('btn_Story_SaveShelf', 'Save Shelf')) }}'">
                                {{ lexicon('btn_Story_SaveShelf', 'Save Shelf') }}
                            </span>
                        </x-button>
                    </div>
                </form>
            </x-modal>

            {{-- MODAL 2: Volume Modal --}}
            <x-modal show="volumeModal.open" maxWidth="max-w-4xl">
                <x-slot:top>
                    <span x-text="volumeModal.mode === 'create' ? '{{ addslashes(lexicon('label_Story_CreateVolume', 'Write Volume Details')) }}' : '{{ addslashes(lexicon('label_Story_EditVolume', 'Edit Volume Details')) }}'"></span>
                </x-slot:top>

                <form :action="volumeModal.action" 
                      method="POST" 
                      class="flex flex-col gap-md" 
                      x-data="ajaxForm" 
                      @submit.prevent="submit($event)">
                    @csrf
                    <input type="hidden" name="_method" :value="volumeModal.method">
                    
                    <div class="grid grid-cols-1 md-grid-cols-2 gap-sm w-full">
                        <x-form-input name="volume_title"
                                      id="volume-title-input"
                                      :label="lexicon('label_Story_VolumeTitle', 'Volume Title')"
                                      :tooltip="lexicon('tooltip_Story_VolumeTitleInput', 'Enter the title of this story volume')"
                                      xModel="volumeModal.volume_title"
                                      :required="true" />

                        <div class="flex flex-col gap-xs">
                            <label for="volume-shelf-select" class="text-xs font-bold text-input-muted">
                                <span>{{ lexicon('label_Story_ShelfLocation', 'Location (Shelf)') }}</span>
                            </label>
                            <select id="volume-shelf-select"
                                    name="shelf_id" 
                                    x-model="volumeModal.shelf_id" 
                                    aria-label="{{ lexicon('label_Story_ShelfLocation', 'Location (Shelf)') }}"
                                    x-tooltip="'{{ addslashes(lexicon('tooltip_Story_ShelfLocationSelect', 'Assign this volume to a shelf or leave unassigned')) }}'"
                                    class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent">
                                <option value="">{{ lexicon('label_Story_OptLooseUnassigned', '-- Loose (Unassigned) --') }}</option>
                                @foreach($shelves as $sh)
                                    <option value="{{ $sh->id }}">{{ $sh->shelf_title }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="flex flex-col gap-xs">
                        <label for="volume-synopsis-textarea" class="text-xs font-bold text-input-muted">
                            <span>{{ lexicon('label_Story_VolumeSynopsis', 'Description / Synopsis') }}</span>
                        </label>
                        <textarea id="volume-synopsis-textarea"
                                  name="volume_synopsis" 
                                  x-model="volumeModal.volume_synopsis" 
                                  x-ref="volumeSynopsisTextarea"
                                  rows="3" 
                                  aria-label="{{ lexicon('label_Story_VolumeSynopsis', 'Description / Synopsis') }}"
                                  x-tooltip="'{{ addslashes(lexicon('tooltip_Story_VolumeSynopsisInput', 'Provide a brief synopsis or overview for this volume')) }}'"
                                  x-init="window.Autosize && window.Autosize.init($el)"
                                  class="bg-input text-input border-input rounded-input p-input outline-none w-full box-border focus-border-accent focus-shadow-accent"></textarea>
                    </div>

                    <div class="grid grid-cols-1 lg-grid-cols-3 gap-sm w-full">
                        <div class="flex flex-col gap-xs">
                            <label for="volume-status-select" class="text-xs font-bold text-input-muted">
                                <span>{{ lexicon('label_common_Status', 'Publication State') }}</span>
                                <span class="text-required">*</span>
                            </label>
                            <select id="volume-status-select"
                                    name="publication_state_id" 
                                    x-model="volumeModal.publication_state_id" 
                                    aria-label="{{ lexicon('label_common_Status', 'Publication State') }}"
                                    x-tooltip="'{{ addslashes(lexicon('tooltip_Story_PublicationStateSelect', 'Select the current publication state of this volume')) }}'"
                                    class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent" 
                                    required>
                                <option value="">{{ lexicon('label_Story_OptSelectState', '-- Select State --') }}</option>
                                @foreach($statuses ?? [] as $st)
                                    <option value="{{ $st->id }}">{{ $st->state_title }}</option>
                                @endforeach
                            </select>
                        </div>

                        <x-form-input name="ic_day"
                                      id="volume-ic-day-input"
                                      :label="lexicon('label_Story_CurrentIcDay', 'Current IC Day')"
                                      :placeholder="lexicon('label_WriteChapter_PlaceholderDay', 'e.g. 14')"
                                      :tooltip="lexicon('tooltip_Story_CurrentIcDayInput', 'Enter the current in-character day for this volume')"
                                      xModel="volumeModal.ic_day" />

                        <x-form-input name="ic_time"
                                      id="volume-ic-time-input"
                                      :label="lexicon('label_Story_CurrentIcTime', 'Current IC Time')"
                                      :placeholder="lexicon('label_WriteChapter_PlaceholderTime', 'e.g. 14:00')"
                                      :tooltip="lexicon('tooltip_Story_CurrentIcTimeInput', 'Enter the current in-character time for this volume')"
                                      xModel="volumeModal.ic_time" />
                    </div>

                    <div class="border-t-actions pt-6 text-right flex flex-row flex-wrap items-center justify-end gap-md">
                        <x-button type="submit" 
                                  variant="success" 
                                  ::disabled="submitting" 
                                  x-tooltip="'{{ addslashes(lexicon('tooltip_Story_SaveVolume', 'Save volume details')) }}'"
                                  aria-label="{{ lexicon('tooltip_Story_SaveVolume', 'Save volume details') }}">
                            <span x-text="submitting ? '{{ addslashes(lexicon('msg_common_Saving', 'Saving...')) }}' : '{{ addslashes(lexicon('btn_Story_SaveVolume', 'Save Volume')) }}'">
                                {{ lexicon('btn_Story_SaveVolume', 'Save Volume') }}
                            </span>
                        </x-button>
                    </div>
                </form>
            </x-modal>

            {{-- MODAL 3: Move Folio Modal --}}
            <x-modal show="moveModal.open" maxWidth="max-w-lg">
                <x-slot:top>
                    <span>{{ lexicon('label_Story_RelocateFolio', 'Relocate Folio') }}</span>
                </x-slot:top>

                <form :action="moveModal.action" 
                      method="POST" 
                      class="flex flex-col gap-md" 
                      x-data="ajaxForm" 
                      @submit.prevent="submit($event)">
                    @csrf
                    @method('PUT')
                    <div class="flex flex-col gap-xs">
                        <label for="move-folio-volume-select" class="text-xs font-bold text-input-muted">
                            <span>{{ lexicon('label_WriteChapter_TargetVolume', 'Target Volume') }}</span>
                            <span class="text-required">*</span>
                        </label>
                        <select id="move-folio-volume-select"
                                name="volume_id" 
                                x-model="moveModal.volume_id" 
                                aria-label="{{ lexicon('label_WriteChapter_TargetVolume', 'Target Volume') }}"
                                x-tooltip="'{{ addslashes(lexicon('tooltip_Story_MoveTargetVolumeSelect', 'Choose the destination volume for this folio')) }}'"
                                class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent" 
                                required>
                            @foreach($shelves as $sh)
                                <optgroup label="{{ $sh->shelf_title }}" class="bg-panel text-accent">
                                    @foreach($sh->volumes as $v)
                                        <option value="{{ $v->id }}" class="text-p">{{ $v->volume_title }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                            @if(count($looseVolumes) > 0)
                                <optgroup label="{{ lexicon('label_Story_LooseVolumes', 'Loose Volumes (Unassigned)') }}" class="bg-panel text-warning">
                                    @foreach($looseVolumes as $v)
                                        <option value="{{ $v->id }}" class="text-p">{{ $v->volume_title }}</option>
                                    @endforeach
                                </optgroup>
                            @endif
                        </select>
                    </div>
                    <div class="border-t-actions pt-6 text-right flex flex-row flex-wrap items-center justify-end gap-md">
                        <x-button type="submit" 
                                  variant="primary" 
                                  ::disabled="submitting" 
                                  x-tooltip="'{{ addslashes(lexicon('tooltip_Story_ConfirmMoveFolio', 'Confirm folio relocation')) }}'"
                                  aria-label="{{ lexicon('tooltip_Story_ConfirmMoveFolio', 'Confirm folio relocation') }}">
                            <span x-text="submitting ? '{{ addslashes(lexicon('msg_Story_Moving', 'Moving...')) }}' : '{{ addslashes(lexicon('btn_Story_MoveFolioSubmit', 'Move Folio')) }}'">
                                {{ lexicon('btn_Story_MoveFolioSubmit', 'Move Folio') }}
                            </span>
                        </x-button>
                    </div>
                </form>
            </x-modal>

            {{-- MODAL 4: Status Modal --}}
            <x-modal show="statusModal.open" maxWidth="max-w-lg">
                <x-slot:top>
                    <span x-text="statusModal.mode === 'create' ? '{{ addslashes(lexicon('label_Story_CreateStatus', 'Create Publication State')) }}' : '{{ addslashes(lexicon('label_Story_EditStatus', 'Edit Publication State')) }}'"></span>
                </x-slot:top>

                <form :action="statusModal.action" 
                      method="POST" 
                      class="flex flex-col gap-md" 
                      x-data="ajaxForm" 
                      @submit.prevent="submit($event)">
                    @csrf
                    <input type="hidden" name="_method" :value="statusModal.method">
                    
                    <x-form-input name="state_title"
                                  id="status-name-input"
                                  :label="lexicon('label_Story_StatusName', 'State Title')"
                                  :tooltip="lexicon('tooltip_Story_StatusNameInput', 'Enter the display title for this publication state')"
                                  xModel="statusModal.state_title"
                                  :required="true" />

                    <div class="border-t-actions pt-6 text-right flex flex-row flex-wrap items-center justify-end gap-md">
                        <x-button type="submit" 
                                  variant="primary" 
                                  ::disabled="submitting" 
                                  x-tooltip="'{{ addslashes(lexicon('tooltip_Story_SaveStatus', 'Save publication state')) }}'"
                                  aria-label="{{ lexicon('tooltip_Story_SaveStatus', 'Save publication state') }}">
                            <span x-text="submitting ? '{{ addslashes(lexicon('msg_common_Saving', 'Saving...')) }}' : '{{ addslashes(lexicon('btn_Story_SaveStatus', 'Save State')) }}'">
                                {{ lexicon('btn_Story_SaveStatus', 'Save State') }}
                            </span>
                        </x-button>
                    </div>
                </form>
            </x-modal>

            {{-- MODAL 5: End-of-Volume Service Record Review Modal --}}
            @include('admin.volume_reviews')
        </x-slot:bottom>
        
    </x-admin-grid-wrapper>
</div>
@endsection