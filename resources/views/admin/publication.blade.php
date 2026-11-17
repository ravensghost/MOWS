{{-- resources/views/admin/publication.blade.php --}}
@extends('layouts.app')

@section('title', lexicon('admin_label_story', 'Publication & Story Library Manager'))

@section('content')
<div x-data="publicationManager()"
     @ajax-success.window="refreshDOM($event.detail)"
     class="flex flex-col gap-lg">

    <x-grid-wrapper :book="$systemBook">
        <x-slot:top>
            <script type="application/json" x-ref="publicationSearchData" class="hidden">
                {!! json_encode(($shelves ?? collect())->map(fn($sh) => [
                    'id' => $sh->id,
                    'shelf_title' => $sh->shelf_title,
                    'volumes' => $sh->volumes->map(fn($v) => [
                        'volume_title' => $v->volume_title,
                        'volume_synopsis' => $v->volume_synopsis ?? '',
                    ])->values(),
                ])->values()) !!}
            </script>

            <x-page-header :title="lexicon('admin_label_story', 'Publication & Story Library Manager')"
                           :subtitle="lexicon('desc_admin_story', 'Organise narrative shelves, story volumes, folio ordering, and publication states.')"
                           icon="bi-book-half">
                <x-slot:actions>
                    <x-metadata type="search"
                                name="story_search"
                                id="story-search-input"
                                :bare="true"
                                xModel="searchQuery"
                                :placeholder="lexicon('label_Story_SearchPlaceholder', 'Search shelves or volumes...')"
                                :tooltip="lexicon('tooltip_Story_SearchInput', 'Instantaneously filter shelves and volumes using fuzzy search')" />

                    <x-button type="button"
                              variant="secondary"
                              size="sm"
                              icon="bi-journal-plus"
                              :tooltip="lexicon('tooltip_Story_AddVolumeLoose', 'Create a new story volume')"
                              @click="openModal('create', {}, 'volume')">
                        + {{ lexicon('btn_Story_AddVolume', 'New Volume') }}
                    </x-button>

                    <x-button type="button"
                              variant="success"
                              size="sm"
                              icon="bi-plus-lg"
                              :tooltip="lexicon('tooltip_Story_AddShelf', 'Create a new shelf to organise volumes')"
                              @click="openModal('create', {}, 'shelf')">
                        + {{ lexicon('btn_Story_AddShelf', 'New Shelf') }}
                    </x-button>
                </x-slot:actions>
            </x-page-header>
        </x-slot:top>

        <x-slot:middle>
            <div class="flex flex-col gap-lg">
                {{-- Shelves & Nested Volumes --}}
                <div id="shelves-wrapper" x-ref="shelvesWrapper">
                    <div class="flex flex-col gap-lg"
                         id="sortable-shelves"
                         x-data="sortableHelper('/admin/shelves/reorder', 'shelf_sort_order')">
                        @forelse($shelves ?? [] as $shelf)
                            <div data-id="{{ $shelf->id }}"
                                 x-show="matchesShelf({{ $shelf->id }}, {{ Js::from($shelf->shelf_title) }})">
                                <x-tab type="accordion"
                                       :title="$shelf->shelf_title"
                                       icon="bi-collection"
                                       :open="false">
                                    <x-slot:top>
                                        <div class="flex flex-row flex-wrap items-center gap-xs" @click.stop>
                                            <x-badge type="pill" variant="primary">
                                                {{ count($shelf->volumes) }} {{ lexicon('label_Story_VolumesCount', 'Volumes') }}
                                            </x-badge>

                                            <x-button type="button"
                                                      variant="success"
                                                      size="sm"
                                                      icon="bi-plus-lg"
                                                      :tooltip="lexicon('tooltip_Story_AddVolume', 'Add a new volume to this shelf')"
                                                      @click="openModal('create', { shelf_id: '{{ $shelf->id }}' }, 'volume')">
                                                + {{ lexicon('btn_Story_AddVolume', 'New Volume') }}
                                            </x-button>

                                            <x-button type="button"
                                                      variant="ghost"
                                                      size="sm"
                                                      icon="bi-pencil"
                                                      :tooltip="lexicon('tooltip_Story_EditShelf', 'Rename this shelf')"
                                                      @click="openModal('edit', {{ Js::from(['id' => $shelf->id, 'shelf_title' => $shelf->shelf_title]) }}, 'shelf')">
                                                {{ lexicon('btn_common_Edit', 'Edit') }}
                                            </x-button>

                                            <x-button type="button"
                                                      variant="danger"
                                                      size="sm"
                                                      icon="bi-trash"
                                                      :tooltip="lexicon('tooltip_Story_DeleteShelf', 'Delete this shelf')"
                                                      x-data="deleteAction('/admin/shelves/{{ $shelf->id }}', 'DELETE', '{{ addslashes($shelf->shelf_title) }}')"
                                                      @click="confirmDelete()">
                                                {{ lexicon('btn_common_Delete', 'Delete') }}
                                            </x-button>
                                        </div>
                                    </x-slot:top>

                                    <div class="flex flex-col gap-md sortable-volumes"
                                         id="sortable-volumes-{{ $shelf->id }}"
                                         data-shelf-id="{{ $shelf->id }}"
                                         x-data="sortableHelper('/admin/volumes/reorder', 'volume_sort_order')">
                                        @forelse($shelf->volumes as $volume)
                                            <x-tab type="volume" :volume="$volume" :isLoose="false" />
                                        @empty
                                            <div class="p-4 text-center text-muted font-italic">
                                                <span>{{ lexicon('msg_Story_NoVolumesInShelf', 'No volumes placed on this shelf.') }}</span>
                                            </div>
                                        @endforelse
                                    </div>
                                </x-tab>
                            </div>
                        @empty
                            <x-tab type="panel">
                                <p class="text-center text-muted font-italic m-0">
                                    {{ lexicon('msg_Story_NoShelves', 'No shelves built yet.') }}
                                </p>
                            </x-tab>
                        @endforelse
                    </div>
                </div>

                {{-- Loose / Unassigned Volumes --}}
                <div id="loose-volumes-wrapper" x-ref="looseVolumesWrapper">
                    @if(count($looseVolumes ?? []) > 0)
                        <x-tab type="accordion"
                               :title="lexicon('label_Story_LooseVolumes', 'Loose Volumes (Unassigned)')"
                               icon="bi-journals"
                               :open="false">
                            <div class="flex flex-col gap-md"
                                 id="sortable-loose-volumes"
                                 x-data="sortableHelper('/admin/volumes/reorder', 'volume_sort_order')">
                                @foreach($looseVolumes as $volume)
                                    <x-tab type="volume" :volume="$volume" :isLoose="true" />
                                @endforeach
                            </div>
                        </x-tab>
                    @endif
                </div>

                {{-- Publication States Management --}}
                <div id="statuses-wrapper" x-ref="statusesWrapper">
                    <x-tab type="accordion"
                           :title="lexicon('admin_label_PublicationStates', 'Publication States')"
                           icon="bi-tags"
                           :open="false">
                        <x-slot:top>
                            <div @click.stop>
                                <x-button type="button"
                                          variant="success"
                                          size="sm"
                                          icon="bi-plus-lg"
                                          :tooltip="lexicon('tooltip_Story_AddStatus', 'Create a new publication state')"
                                          @click="openModal('create', {}, 'status')">
                                    + {{ lexicon('btn_Story_AddStatus', 'New State') }}
                                </x-button>
                            </div>
                        </x-slot:top>

                        <x-table type="admin"
                                 :headers="[
                                     lexicon('label_Story_StatusName', 'State Title'),
                                     lexicon('label_common_Actions', 'Actions')
                                 ]"
                                 :emptyText="lexicon('msg_Story_NoStatuses', 'No publication states created yet.')">
                            @foreach($statuses ?? [] as $status)
                                <tr class="border-b-table-row hover-bg-panel-hover transition-colors">
                                    <td class="p-4">
                                        <x-badge type="pill"
                                                 :variant="$status->book_url ?? 'primary'"
                                                 :tooltip="lexicon('tooltip_Story_StatusPreviewBadge', 'State badge preview')">
                                            {{ $status->state_title }}
                                        </x-badge>
                                    </td>
                                    <td class="p-4 text-right">
                                        <div class="flex flex-row flex-wrap items-center justify-end gap-xs">
                                            <x-button type="button"
                                                      variant="ghost"
                                                      size="sm"
                                                      icon="bi-pencil"
                                                      :tooltip="lexicon('tooltip_Story_EditStatus', 'Edit publication state')"
                                                      @click="openModal('edit', {{ Js::from([
                                                          'id' => $status->id,
                                                          'state_title' => $status->state_title,
                                                          'book_url' => $status->book_url ?? 'primary',
                                                          'state_sort_order' => $status->state_sort_order ?? 0,
                                                      ]) }}, 'status')">
                                                {{ lexicon('btn_common_Edit', 'Edit') }}
                                            </x-button>

                                            <x-button type="button"
                                                      variant="danger"
                                                      size="sm"
                                                      icon="bi-trash"
                                                      :tooltip="lexicon('tooltip_Story_DeleteStatus', 'Delete this publication state')"
                                                      x-data="deleteAction('/admin/publication-states/{{ $status->id }}', 'DELETE', '{{ addslashes($status->state_title) }}')"
                                                      @click="confirmDelete()">
                                                {{ lexicon('btn_common_Delete', 'Delete') }}
                                            </x-button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </x-table>
                    </x-tab>
                </div>
            </div>
        </x-slot:middle>

        <x-slot:bottom>
            {{-- MODAL 1: SHELF CREATE / EDIT --}}
            <x-modal show="modals.shelf.open" onClose="closeModal('shelf')" maxWidth="max-w-lg">
                <x-slot:top>
                    <span x-text="modals.shelf.mode === 'create'
                        ? '{{ addslashes(lexicon('label_Story_CreateShelf', 'Build Shelf')) }}'
                        : '{{ addslashes(lexicon('label_Story_EditShelf', 'Edit Shelf')) }}'"></span>
                </x-slot:top>

                <x-meta type="modal" xAction="modals.shelf.action" xMethod="modals.shelf.method">
                    <x-metadata type="text"
                                name="shelf_title"
                                id="shelf-title-input"
                                xModel="modals.shelf.shelf_title"
                                :label="lexicon('label_Story_ShelfName', 'Shelf Title')"
                                :tooltip="lexicon('tooltip_Story_ShelfNameInput', 'Enter the display title for this shelf')"
                                :required="true" />

                    <x-slot:footer>
                        <x-button type="button"
                                  variant="secondary"
                                  @click="closeModal('shelf')">
                            {{ lexicon('btn_common_Cancel', 'Cancel') }}
                        </x-button>

                        <x-button type="submit"
                                  variant="primary"
                                  icon="bi-save"
                                  :tooltip="lexicon('tooltip_Story_SaveShelf', 'Save shelf changes')">
                            {{ lexicon('btn_Story_SaveShelf', 'Save Shelf') }}
                        </x-button>
                    </x-slot:footer>
                </x-meta>
            </x-modal>

            {{-- MODAL 2: VOLUME CREATE / EDIT --}}
            <x-modal show="modals.volume.open" onClose="closeModal('volume')" maxWidth="4xl">
                <x-slot:top>
                    <span x-text="modals.volume.mode === 'create'
                        ? '{{ addslashes(lexicon('label_Story_CreateVolume', 'Write Volume Details')) }}'
                        : '{{ addslashes(lexicon('label_Story_EditVolume', 'Edit Volume Details')) }}'"></span>
                </x-slot:top>

                <x-meta type="modal" xAction="modals.volume.action" xMethod="modals.volume.method">
                    <div class="grid grid-cols-1 md-grid-cols-2 gap-md">
                        <x-metadata type="text"
                                    name="volume_title"
                                    id="volume-title-input"
                                    xModel="modals.volume.volume_title"
                                    :label="lexicon('label_Story_VolumeTitle', 'Volume Title')"
                                    :tooltip="lexicon('tooltip_Story_VolumeTitleInput', 'Enter the title of this story volume')"
                                    :required="true" />

                        <x-metadata type="select"
                                    name="shelf_id"
                                    id="volume-shelf-select"
                                    xModel="modals.volume.shelf_id"
                                    :label="lexicon('label_Story_ShelfLocation', 'Location (Shelf)')"
                                    :tooltip="lexicon('tooltip_Story_ShelfLocationSelect', 'Assign this volume to a shelf or leave unassigned')">
                            <option value="">{{ lexicon('label_Story_OptLooseUnassigned', '— Loose (Unassigned) —') }}</option>
                            @foreach($shelves ?? [] as $sh)
                                <option value="{{ $sh->id }}">{{ $sh->shelf_title }}</option>
                            @endforeach
                        </x-metadata>
                    </div>

                    <x-metadata type="textarea"
                                name="volume_synopsis"
                                id="volume-synopsis-textarea"
                                x-ref="volumeSynopsisTextarea"
                                xModel="modals.volume.volume_synopsis"
                                :label="lexicon('label_Story_VolumeSynopsis', 'Description / Synopsis')"
                                :tooltip="lexicon('tooltip_Story_VolumeSynopsisInput', 'Provide a brief synopsis or overview for this volume')" />

                    <div class="grid grid-cols-1 lg-grid-cols-3 gap-md">
                        <x-metadata type="select"
                                    name="publication_state_id"
                                    id="volume-status-select"
                                    xModel="modals.volume.publication_state_id"
                                    :label="lexicon('label_common_Status', 'Publication State')"
                                    :tooltip="lexicon('tooltip_Story_PublicationStateSelect', 'Select the current publication state of this volume')"
                                    :required="true">
                            <option value="">{{ lexicon('label_Story_OptSelectState', '— Select State —') }}</option>
                            @foreach($statuses ?? [] as $st)
                                <option value="{{ $st->id }}">{{ $st->state_title }}</option>
                            @endforeach
                        </x-metadata>

                        <x-metadata type="text"
                                    name="ic_day"
                                    id="volume-ic-day-input"
                                    xModel="modals.volume.ic_day"
                                    :label="lexicon('label_Story_CurrentIcDay', 'Current IC Day')"
                                    :placeholder="lexicon('label_WriteChapter_PlaceholderDay', 'e.g. 14')"
                                    :tooltip="lexicon('tooltip_Story_CurrentIcDayInput', 'Enter the current in-character day for this volume')" />

                        <x-metadata type="text"
                                    name="ic_time"
                                    id="volume-ic-time-input"
                                    xModel="modals.volume.ic_time"
                                    :label="lexicon('label_Story_CurrentIcTime', 'Current IC Time')"
                                    :placeholder="lexicon('label_WriteChapter_PlaceholderTime', 'e.g. 14:00')"
                                    :tooltip="lexicon('tooltip_Story_CurrentIcTimeInput', 'Enter the current in-character time for this volume')" />
                    </div>

                    <x-slot:footer>
                        <x-button type="button"
                                  variant="secondary"
                                  @click="closeModal('volume')">
                            {{ lexicon('btn_common_Cancel', 'Cancel') }}
                        </x-button>

                        <x-button type="submit"
                                  variant="success"
                                  icon="bi-save"
                                  :tooltip="lexicon('tooltip_Story_SaveVolume', 'Save volume details')">
                            {{ lexicon('btn_Story_SaveVolume', 'Save Volume') }}
                        </x-button>
                    </x-slot:footer>
                </x-meta>
            </x-modal>

            {{-- MODAL 3: RELOCATE FOLIO --}}
            <x-modal show="modals.move.open" onClose="closeModal('move')" maxWidth="max-w-lg">
                <x-slot:top>
                    <span>{{ lexicon('label_Story_RelocateFolio', 'Relocate Folio') }}</span>
                </x-slot:top>

                <x-meta type="modal" xAction="modals.move.action" xMethod="modals.move.method">
                    <x-metadata type="select"
                                name="volume_id"
                                id="move-folio-volume-select"
                                xModel="modals.move.volume_id"
                                :label="lexicon('label_WriteChapter_TargetVolume', 'Target Volume')"
                                :tooltip="lexicon('tooltip_Story_MoveTargetVolumeSelect', 'Choose the destination volume for this folio')"
                                :required="true">
                        @foreach($shelves ?? [] as $sh)
                            <optgroup label="{{ $sh->shelf_title }}">
                                @foreach($sh->volumes as $v)
                                    <option value="{{ $v->id }}">{{ $v->volume_title }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                        @if(count($looseVolumes ?? []) > 0)
                            <optgroup label="{{ lexicon('label_Story_LooseVolumes', 'Loose Volumes (Unassigned)') }}">
                                @foreach($looseVolumes as $v)
                                    <option value="{{ $v->id }}">{{ $v->volume_title }}</option>
                                @endforeach
                            </optgroup>
                        @endif
                    </x-metadata>

                    <x-slot:footer>
                        <x-button type="button"
                                  variant="secondary"
                                  @click="closeModal('move')">
                            {{ lexicon('btn_common_Cancel', 'Cancel') }}
                        </x-button>

                        <x-button type="submit"
                                  variant="primary"
                                  icon="bi-arrow-left-right"
                                  :tooltip="lexicon('tooltip_Story_ConfirmMoveFolio', 'Confirm folio relocation')">
                            {{ lexicon('btn_Story_MoveFolioSubmit', 'Move Folio') }}
                        </x-button>
                    </x-slot:footer>
                </x-meta>
            </x-modal>

            {{-- MODAL 4: PUBLICATION STATE CREATE / EDIT --}}
            <x-modal show="modals.status.open" onClose="closeModal('status')" maxWidth="max-w-lg">
                <x-slot:top>
                    <span x-text="modals.status.mode === 'create'
                        ? '{{ addslashes(lexicon('label_Story_CreateStatus', 'Create Publication State')) }}'
                        : '{{ addslashes(lexicon('label_Story_EditStatus', 'Edit Publication State')) }}'"></span>
                </x-slot:top>

                <x-meta type="modal" xAction="modals.status.action" xMethod="modals.status.method">
                    <x-metadata type="text"
                                name="state_title"
                                id="status-name-input"
                                xModel="modals.status.state_title"
                                :label="lexicon('label_Story_StatusName', 'State Title')"
                                :tooltip="lexicon('tooltip_Story_StatusNameInput', 'Enter the display title for this publication state')"
                                :required="true" />

                    <x-slot:footer>
                        <x-button type="button"
                                  variant="secondary"
                                  @click="closeModal('status')">
                            {{ lexicon('btn_common_Cancel', 'Cancel') }}
                        </x-button>

                        <x-button type="submit"
                                  variant="primary"
                                  icon="bi-save"
                                  :tooltip="lexicon('tooltip_Story_SaveStatus', 'Save publication state')">
                            {{ lexicon('btn_Story_SaveStatus', 'Save State') }}
                        </x-button>
                    </x-slot:footer>
                </x-meta>
            </x-modal>

            {{-- MODAL 5: End-of-Volume Service Record Review Modal --}}
            @include('admin.volume_reviews')
        </x-slot:bottom>
    </x-grid-wrapper>
</div>
@endsection