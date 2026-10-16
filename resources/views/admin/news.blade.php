@extends('layouts.app')

@section('title', lexicon('admin_label_news', 'News & Announcements'))

@section('content')
<div x-data="newsManager()"
    @ajax-success.window="
        modalOpen = false;
        window.refreshDOM_US('#news-table-content');
        setTimeout(() => loadNewsData(), 300);
    ">

    <x-admin-grid-wrapper :page="$systemPage">
        
        <x-slot:top>
            <x-page-header :title="lexicon('admin_label_news', 'News & Announcements')">
                <x-slot:actions>
                    <x-button type="button" variant="success" class="text-sm p-btn-sm"
                              @click="openModal('create')"
                              x-tooltip="'{{ addslashes(lexicon('tooltip_News_CreateAnnouncement', 'Broadcast a new station announcement')) }}'">
                        <i class="bi bi-megaphone"></i> {{ lexicon('btn_News_CreateAnnouncement', 'Create Announcement') }}
                    </x-button>
                </x-slot:actions>
            </x-page-header>
        </x-slot:top>

        <x-slot:bottom>
            <x-accordion-panel :title="lexicon('label_News_AllAnnouncements', 'Published Station Announcements')" :expanded="true">
                
                <!-- Fuse.js Instant Search Filter -->
                <div class="bg-panel-sub border border-panel rounded-panel p-3 mb-2">
                    <x-form-input 
                        name="search_news" 
                        id="search-news"
                        :label="lexicon('label_News_FilterAnnouncements', 'Filter Announcements')" 
                        xModel="searchQuery" 
                        :placeholder="lexicon('label_News_FilterPlaceholder', 'Search announcements by headline or date...')"
                        :tooltip="lexicon('tooltip_News_FilterInput', 'Instantaneously filter announcements using fuzzy search')" />
                </div>

                <div id="news-table-content" x-ref="newsTableContent">
                    <div id="news-fresh-data" class="hidden"
                         data-news="{{ json_encode(collect($news ?? [])->map(fn($n) => ['id' =>$n->id, 'title' => $n->title, 'date' =>$n->created_at?->format('M d, Y')])) }}">
                    </div>

                    <x-table>
                        <x-slot:headers>
                            <th class="p-4 text-left">{{ lexicon('label_News_Headline', 'Headline') }}</th>
                            <th class="p-4 text-left">{{ lexicon('label_News_DatePublished', 'Date Published') }}</th>
                            <th class="p-4 text-right">{{ lexicon('label_common_Actions', 'Actions') }}</th>
                        </x-slot:headers>

                        @forelse($news ?? [] as $item)
                            <tr id="news-row-{{ $item->id }}" class="table-row" x-show="isVisible({{ $item->id }})">
                                <td class="p-4 font-bold text-p">{{ $item->title }}</td>
                                <td class="p-4 text-xs text-muted">
                                    <x-badge variant="info" x-tooltip="'{{ addslashes(lexicon('tooltip_News_PublishedDate', 'Date this announcement was broadcast')) }}'">
                                        {{ $item->created_at->format('M d, Y') }}
                                    </x-badge>
                                </td>
                                <td class="p-4 text-right">
                                    <div class="flex flex-row justify-end items-center gap-sm text-sm">
                                        <x-button type="button" variant="text" class="text-warning hover-text-h" 
                                                  @click="openModal('edit', { 
                                                      id: {{ $item->id }}, 
                                                      title: '{{ addslashes($item->title) }}', 
                                                      content: '{{ addslashes($item->content) }}' 
                                                  })"
                                                  x-tooltip="'{{ addslashes(lexicon('tooltip_News_EditAnnouncement', 'Edit this announcement')) }}'">
                                            <i class="bi bi-pencil"></i> {{ lexicon('btn_common_Edit', 'Edit') }}
                                        </x-button>

                                        <x-button type="button" variant="text" class="text-danger hover-text-h" 
                                                  x-data="deleteAction(`/admin/news/{{ $item->id }}`, 'DELETE', '{{ addslashes($item->title) }}')"
                                                  @click="confirmDelete()"
                                                  x-tooltip="'{{ addslashes(lexicon('tooltip_News_DeleteAnnouncement', 'Delete this announcement permanently')) }}'">
                                            <i class="bi bi-trash"></i> {{ lexicon('btn_common_Delete', 'Delete') }}
                                        </x-button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="p-6 text-center text-muted italic">
                                    <span class="block">{{ lexicon('msg_News_NoRecords', 'No announcements found.') }}</span>
                                </td>
                            </tr>
                        @endforelse
                    </x-table>
                </div>
            </x-accordion-panel>

            <!-- Create / Edit News Modal -->
            <x-modal show="modalOpen" maxWidth="max-w-2xl">
                <x-slot:top>
                    <span x-text="mode === 'create' ? '{{ addslashes(lexicon('label_News_CreateAnnouncementTitle', 'Broadcast Announcement')) }}' : '{{ addslashes(lexicon('label_News_EditAnnouncementTitle', 'Edit Announcement')) }}'"></span>
                </x-slot:top>

                <form id="form-news" :action="formAction" method="POST" class="flex flex-col gap-md" x-data="ajaxForm" @submit.prevent="submit">
                    @csrf
                    <input type="hidden" name="_method" :value="method">
                    
                    <x-form-input 
                        name="title" 
                        id="news-title"
                        :label="lexicon('label_News_Headline', 'Headline')" 
                        xModel="title" 
                        :tooltip="lexicon('tooltip_News_HeadlineInput', 'Enter the headline for this announcement')"
                        required />

                    <div class="flex flex-col gap-xs">
                        <label class="text-xs font-bold text-input-muted">
                            <span>{{ lexicon('label_News_AnnouncementBody', 'Announcement Content') }}</span>
                            <span class="text-required">*</span>
                        </label>
                        <span class="text-xs text-muted block">{{ lexicon('msg_News_SanitisedHint', 'Use the rich text editor below. Content is automatically sanitised.') }}</span>
                        
                        <!-- TipTap Editor UI -->
                        <div class="bg-panel-sub border border-panel rounded-panel flex flex-col overflow-hidden" 
                             x-data="tiptapEditor(content)" 
                             @tiptap-update="content = $event.detail"
                             @sync-news-editor.window="content = $event.detail; refreshEditor()">
                            <x-tiptap-toolbar />
                            <div x-ref="editorContainer" class="w-full p-3 flex-grow overflow-y-auto tiptap-content"></div>
                        </div>
                        
                        <input type="hidden" name="content" :value="content" required>
                    </div>

                    <div class="border-t-actions border-panel pt-6 text-right mt-2 flex flex-row flex-wrap items-center justify-end gap-sm">
                        <x-button type="button" variant="secondary" @click="modalOpen = false"
                                  x-tooltip="'{{ addslashes(lexicon('tooltip_common_Cancel', 'Cancel and close modal')) }}'">
                            {{ lexicon('btn_common_Cancel', 'Cancel') }}
                        </x-button>

                        <x-button type="submit" variant="primary"
                                  x-tooltip="'{{ addslashes(lexicon('tooltip_News_SubmitAnnouncement', 'Publish or update this announcement')) }}'">
                            <i class="bi" :class="mode === 'create' ? 'bi-send' : 'bi-check-lg'"></i>
                            <span x-text="mode === 'create' ? '{{ addslashes(lexicon('btn_News_Broadcast', 'Broadcast to Station')) }}' : '{{ addslashes(lexicon('btn_common_Update', 'Update')) }}'"></span>
                        </x-button>
                    </div>
                </form>
            </x-modal>
        </x-slot:bottom>

    </x-admin-grid-wrapper>
</div>
@endsection