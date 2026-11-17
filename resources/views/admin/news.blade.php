{{-- resources/views/admin/news.blade.php --}}
@extends('layouts.app')

@php
    $bulletinsList = $bulletins ?? $news ?? collect();
    $autoSaveInterval = (int) (\App\Models\Configuration::where('configuration_token', 'draft_autosave_interval')->value('configuration_payload') ?: 30000);
@endphp

@section('title', lexicon('admin_label_news', 'Bulletins & Announcements'))

@section('content')
<div x-data="bulletinManager()"
     @ajax-success.window="refreshDOM()"
     class="flex flex-col gap-lg">

    <x-grid-wrapper :book="$systemBook">
        <x-slot:top>
            <x-page-header :title="lexicon('admin_label_news', 'Bulletins & Announcements')"
                           :subtitle="lexicon('desc_admin_news', 'Broadcast official portal bulletins, command dispatches, and community announcements.')"
                           icon="bi-megaphone">
                <x-slot:actions>
                    <x-metadata type="search"
                                name="search_news"
                                id="search-news"
                                :bare="true"
                                xModel="searchQuery"
                                :placeholder="lexicon('label_News_FilterPlaceholder', 'Search bulletins by headline or date...')"
                                :tooltip="lexicon('tooltip_News_FilterInput', 'Instantaneously filter bulletins using fuzzy search')" />

                    @can('manage_news')
                        <x-button type="button"
                                  variant="success"
                                  size="sm"
                                  icon="bi-megaphone"
                                  :tooltip="lexicon('tooltip_News_CreateAnnouncement', 'Broadcast a new bulletin announcement')"
                                  @click="openModal('create', {}, '{{ route('admin.bulletins.store') }}')">
                            + {{ lexicon('btn_News_CreateAnnouncement', 'Create Bulletin') }}
                        </x-button>
                    @endcan
                </x-slot:actions>
            </x-page-header>
        </x-slot:top>

        <x-slot:bottom>
            <x-tab type="accordion"
                   :title="lexicon('label_News_AllAnnouncements', 'Published Bulletins')"
                   icon="bi-broadcast"
                   :open="true">

                <div id="news-table-content" x-ref="newsTableContent">
                    <div id="news-fresh-data"
                         class="hidden"
                         data-news="{{ json_encode(collect($bulletinsList)->map(fn($n) => [
                             'id' => $n->id,
                             'bulletin_title' => $n->bulletin_title,
                             'date' => $n->created_at?->format('M d, Y'),
                         ])) }}"></div>

                    <x-table type="admin"
                             :headers="[
                                 lexicon('label_News_Headline', 'Headline'),
                                 lexicon('label_News_DatePublished', 'Date Published'),
                                 lexicon('label_common_Actions', 'Actions')
                             ]"
                             :emptyText="lexicon('msg_News_NoRecords', 'No bulletin announcements found.')">
                        @foreach($bulletinsList as $item)
                            @php
                                $pubDate = $item->published_at?->format('M d, Y') ?? $item->created_at?->format('M d, Y') ?? '—';
                            @endphp
                            <tr id="news-row-{{ $item->id }}"
                                class="border-b-table-row hover-bg-panel-hover transition-colors"
                                data-search-item
                                data-news-item
                                data-id="{{ $item->id }}"
                                data-bulletin_title="{{ $item->bulletin_title }}"
                                data-date="{{ $pubDate }}"
                                x-show="isItemVisible({{ $item->id }})">
                                <td class="p-4 font-bold text-h">{{ $item->bulletin_title }}</td>
                                <td class="p-4">
                                    <x-badge type="pill"
                                             variant="info"
                                             :tooltip="lexicon('tooltip_News_PublishedDate', 'Date this bulletin was broadcast')">
                                        {{ $pubDate }}
                                    </x-badge>
                                </td>
                                <td class="p-4 text-right">
                                    <div class="flex flex-row flex-wrap justify-end items-center gap-xs">
                                        <x-button type="button"
                                                  variant="ghost"
                                                  size="sm"
                                                  icon="bi-pencil"
                                                  :tooltip="lexicon('tooltip_News_EditAnnouncement', 'Edit this bulletin')"
                                                  @click="openModal('edit', {{ Js::from([
                                                      'id' => $item->id,
                                                      'bulletin_title' => $item->bulletin_title,
                                                      'bulletin_text' => $item->bulletin_text,
                                                  ]) }}, '/admin/bulletins')">
                                            {{ lexicon('btn_common_Edit', 'Edit') }}
                                        </x-button>

                                        <x-button type="button"
                                                  variant="danger"
                                                  size="sm"
                                                  icon="bi-trash"
                                                  :tooltip="lexicon('tooltip_News_DeleteAnnouncement', 'Delete this bulletin permanently')"
                                                  x-data="deleteAction('{{ route('admin.bulletins.destroy', $item->id) }}', 'DELETE', '{{ addslashes($item->bulletin_title) }}')"
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

            {{-- MODAL: CREATE / EDIT BULLETIN --}}
            <x-modal show="modals.bulletin.open" onClose="closeModal()" maxWidth="4xl">
                <x-slot:top>
                    <span x-text="modals.bulletin.mode === 'create'
                        ? '{{ addslashes(lexicon('label_News_CreateAnnouncementTitle', 'Broadcast Bulletin')) }}'
                        : '{{ addslashes(lexicon('label_News_EditAnnouncementTitle', 'Edit Bulletin')) }}'"></span>
                </x-slot:top>

                <div x-data="autoSave({{ $autoSaveInterval }})" class="flex flex-col gap-md">
                    <x-meta type="modal"
                            xAction="modals.bulletin.action"
                            xMethod="modals.bulletin.method"
                            @update-content.window="modals.bulletin.bulletin_text = $event.detail">

                        <x-metadata type="text"
                                    name="bulletin_title"
                                    id="news-title"
                                    xModel="modals.bulletin.bulletin_title"
                                    :label="lexicon('label_News_Headline', 'Bulletin Headline')"
                                    :tooltip="lexicon('tooltip_News_HeadlineInput', 'Enter the headline for this bulletin')"
                                    :required="true" />

                        <div class="flex flex-col gap-xs">
                            <label class="text-xs font-bold text-input-muted uppercase text-wider">
                                <span>{{ lexicon('label_News_AnnouncementBody', 'Bulletin Content') }}</span>
                                <span class="text-required">*</span>
                            </label>
                            <span class="text-xs text-muted">{{ lexicon('msg_News_SanitisedHint', 'Use the rich text editor below. Content is automatically sanitised.') }}</span>

                            {{-- Canonical TipTap Editor + Toolbar --}}
                            <div class="editor-container bg-panel-sub border-panel border-rounded flex flex-col overflow-hidden"
                                 x-data="tiptapHelper(modals.bulletin.bulletin_text)"
                                 @tiptap-update="modals.bulletin.bulletin_text = $event.detail; $dispatch('update-content', $event.detail)"
                                 @set-editor-content.window="setEditorContent($event.detail.content)">
                                <x-tt-tb name="bulletin_text" />
                            </div>
                        </div>

                        <x-slot:footer>
                            <div class="flex flex-row flex-wrap items-center justify-between w-full gap-sm"
                                 x-data="{ autoSaved: false, savingNow: false }"
                                 @autosave-success.window="savingNow = false; autoSaved = true"
                                 @autosave-start.window="savingNow = true">

                                <div class="flex flex-row items-center gap-xs">
                                    <template x-if="savingNow">
                                        <x-badge type="pill" variant="warning" icon="bi-arrow-repeat">
                                            {{ lexicon('msg_common_Saving', 'Saving...') }}
                                        </x-badge>
                                    </template>
                                    <template x-if="!savingNow && autoSaved">
                                        <x-badge type="pill" variant="success" icon="bi-check-lg">
                                            {{ lexicon('status_common_AutoSaved', 'Auto-Saved') }}
                                        </x-badge>
                                    </template>
                                    <template x-if="!savingNow && !autoSaved">
                                        <x-badge type="pill" variant="muted">
                                            {{ lexicon('status_common_Draft', 'Draft') }}
                                        </x-badge>
                                    </template>
                                </div>

                                <div class="flex flex-row items-center gap-sm">
                                    <x-button type="button"
                                              variant="secondary"
                                              :tooltip="lexicon('tooltip_common_Cancel', 'Cancel and close modal')"
                                              @click="closeModal()">
                                        {{ lexicon('btn_common_Cancel', 'Cancel') }}
                                    </x-button>

                                    <x-button type="submit"
                                              variant="primary"
                                              icon="bi-send"
                                              :tooltip="lexicon('tooltip_News_SubmitAnnouncement', 'Publish or update this bulletin')">
                                        <span x-text="modals.bulletin.mode === 'create'
                                            ? '{{ addslashes(lexicon('btn_News_Broadcast', 'Broadcast Bulletin')) }}'
                                            : '{{ addslashes(lexicon('btn_common_Update', 'Update Bulletin')) }}'"></span>
                                    </x-button>
                                </div>
                            </div>
                        </x-slot:footer>
                    </x-meta>
                </div>
            </x-modal>
        </x-slot:bottom>
    </x-grid-wrapper>
</div>
@endsection