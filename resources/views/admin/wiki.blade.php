@extends('layouts.app')

@section('title', lexicon('admin_label_WikiManager', 'Wiki Encyclopedia Manager'))

@section('content')
<div x-data="wikiManager" @ajax-success.window="refreshDOM()">
    <x-admin-grid-wrapper :page="$systemPage">
        
        <x-slot:top>
            <x-page-header :title="lexicon('admin_label_WikiManager', 'Wiki Encyclopedia Manager')">
                <x-slot:actions>
                    @can('manage_pages')
                        <x-button type="button"
                                  variant="success"
                                  class="p-btn-sm text-sm"
                                  @click="openWikiModal('create')"
                                  aria-label="{{ lexicon('tooltip_Wiki_CreateArticle', 'Create a new wiki lore article') }}"
                                  x-tooltip="'{{ addslashes(lexicon('tooltip_Wiki_CreateArticle', 'Create a new wiki lore article')) }}'">
                            <i class="bi bi-plus-lg"></i> {{ lexicon('btn_Wiki_CreateArticle', 'Create Article') }}
                        </x-button>
                    @endcan
                </x-slot:actions>
            </x-page-header>

            <!-- Instant Fuzzy Search Filter Bar -->
            <div class="bg-panel border border-panel rounded-panel shadow-panel p-4 mb-lg">
                <x-form-input name="wiki_search"
                              id="wiki_search"
                              type="text"
                              :label="lexicon('label_Wiki_FilterArticles', 'Filter Wiki Articles')"
                              :placeholder="lexicon('label_Wiki_SearchPlaceholder', 'Search by title, category, or content...')"
                              :tooltip="lexicon('tooltip_Wiki_SearchInput', 'Instantaneously filter wiki articles using fuzzy search')"
                              xModel="searchQuery" />
            </div>
        </x-slot:top>

        <x-slot:middle>
            <div id="wiki-table-content" x-ref="wikiTableContent">
                <x-table>
                    <x-slot:headers>
                        <th class="p-4 text-left">{{ lexicon('label_common_Title', 'Title') }}</th>
                        <th class="p-4 text-left">{{ lexicon('label_common_Category', 'Category') }}</th>
                        <th class="p-4 text-center w-32">{{ lexicon('label_common_Status', 'Status') }}</th>
                        <th class="p-4 text-right w-48">{{ lexicon('label_common_Actions', 'Actions') }}</th>
                    </x-slot:headers>

                    @forelse($articles as $article)
                        <tr class="table-row"
                            data-wiki-search-item
                            data-title="{{ addslashes($article->title) }}"
                            data-category="{{ addslashes($article->category) }}"
                            data-content="{{ addslashes(strip_tags($article->content)) }}"
                            x-show="isArticleVisible({{ $article->id }})"
                            x-cloak>
                            <td class="p-4 font-bold text-p">{{ $article->title }}</td>
                            <td class="p-4 text-muted">
                                <x-badge type="primary">{{ $article->category }}</x-badge>
                            </td>
                            <td class="p-4 text-center">
                                <x-badge :variant="$article->is_visible ? 'success' : 'warning'"
                                         x-tooltip="'{{ addslashes($article->is_visible ? lexicon('tooltip_Wiki_StatusPublished', 'This article is live and visible') : lexicon('tooltip_Wiki_StatusHidden', 'This article is hidden from view')) }}'">
                                    {{ $article->is_visible ? lexicon('label_common_Published', 'Published') : lexicon('label_common_Hidden', 'Hidden') }}
                                </x-badge>
                            </td>
                            <td class="p-4 text-right">
                                @can('manage_pages')
                                    <div class="flex flex-row justify-end items-center gap-sm text-sm">
                                        <x-button type="button"
                                                  variant="text"
                                                  class="text-warning hover-warning"
                                                  @click="openWikiModal('edit', {
                                                      id: {{ $article->id }},
                                                      title: '{{ addslashes($article->title) }}',
                                                      category: '{{ addslashes($article->category) }}',
                                                      content: `{!! addslashes($article->content) !!}`,
                                                      is_visible: {{ $article->is_visible ? 'true' : 'false' }}
                                                  })"
                                                  aria-label="{{ lexicon('tooltip_common_Edit', 'Edit this wiki article') }}"
                                                  x-tooltip="'{{ addslashes(lexicon('tooltip_common_Edit', 'Edit this wiki article')) }}'">
                                            <i class="bi bi-pencil"></i> {{ lexicon('btn_common_Edit', 'Edit') }}
                                        </x-button>

                                        <x-button type="button"
                                                  variant="text"
                                                  class="text-danger hover-danger"
                                                  x-data="deleteAction(`/admin/wiki/{{ $article->id }}`, 'DELETE', '{{ addslashes($article->title) }}')"
                                                  @click="confirmDelete"
                                                  aria-label="{{ lexicon('tooltip_common_Delete', 'Delete this article permanently') }}"
                                                  x-tooltip="'{{ addslashes(lexicon('tooltip_common_Delete', 'Delete this article permanently')) }}'">
                                            <i class="bi bi-trash"></i> {{ lexicon('btn_common_Delete', 'Delete') }}
                                        </x-button>
                                    </div>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="p-6 text-center text-muted italic">
                                {{ lexicon('msg_Wiki_NoArticles', 'No wiki articles found.') }}
                            </td>
                        </tr>
                    @endforelse
                </x-table>
            </div>
        </x-slot:middle>

        <x-slot:bottom>
            <!-- MODAL: CREATE / EDIT WIKI ARTICLE -->
            @can('manage_pages')
                <x-modal show="wikiModal.open" maxWidth="max-w-4xl" onClose="closeWikiModal()">
                    <x-slot:top>
                        <span x-text="wikiModal.mode === 'create' ? '{{ addslashes(lexicon('label_Wiki_CreateTitle', 'Create Wiki Article')) }}' : '{{ addslashes(lexicon('label_Wiki_EditTitle', 'Edit Wiki Article')) }}'"></span>
                    </x-slot:top>

                    <form :action="wikiModal.formAction"
                          method="POST"
                          class="flex flex-col gap-md"
                          x-data="ajaxForm"
                          @submit.prevent="submit"
                          data-loading-title="{{ lexicon('msg_common_Saving', 'Saving...') }}">
                        @csrf
                        <input type="hidden" name="_method" :value="wikiModal.method">

                        <div class="grid grid-cols-1 md-grid-cols-2 gap-md bg-panel-sub border border-panel rounded-panel p-3">
                            <x-form-input name="title"
                                          id="wiki_title"
                                          type="text"
                                          :label="lexicon('label_common_Title', 'Title')"
                                          :tooltip="lexicon('tooltip_Wiki_TitleInput', 'Enter article title')"
                                          xModel="wikiModal.title"
                                          :required="true" />

                            <x-form-input name="category"
                                          id="wiki_category"
                                          type="text"
                                          :label="lexicon('label_common_Category', 'Category')"
                                          :tooltip="lexicon('tooltip_Wiki_CategoryInput', 'Enter category namespace (e.g. Technology, Species, History)')"
                                          xModel="wikiModal.category"
                                          :required="true" />
                        </div>

                        <div class="flex flex-col gap-xs mt-2">
                            <label class="text-xs font-bold text-input-muted">{{ lexicon('label_common_Content', 'Content') }}</label>
                            <div class="bg-panel border border-panel rounded-panel flex flex-col overflow-hidden"
                                 x-data="tiptapEditor(wikiModal.content)"
                                 @tiptap-update="wikiModal.content = $event.detail">
                                <x-tiptap-toolbar />
                                <div x-ref="editorContainer" class="p-3 flex-grow tiptap-content min-h-[250px]"></div>
                                <input type="hidden" name="content" :value="wikiModal.content">
                            </div>
                        </div>

                        <div class="flex flex-row items-center gap-sm mt-3">
                            <input type="hidden" name="is_visible" value="0">
                            <input type="checkbox"
                                   id="wiki_is_visible"
                                   name="is_visible"
                                   value="1"
                                   x-model="wikiModal.is_visible"
                                   class="appearance-none bg-input m-0 w-4 h-4 border-input rounded-input-sm grid place-content-center checkbox-engine cursor-pointer">
                            <label for="wiki_is_visible" class="text-sm font-bold text-muted cursor-pointer">
                                {{ lexicon('label_common_ShowOnSite', 'Show on Site') }}
                            </label>
                        </div>

                        <div class="border-t-actions border-panel pt-6 text-right mt-4 flex flex-row justify-end gap-sm">
                            <x-button type="button"
                                      variant="secondary"
                                      @click="closeWikiModal()"
                                      x-tooltip="'{{ addslashes(lexicon('tooltip_common_Cancel', 'Cancel and close modal')) }}'">
                                {{ lexicon('btn_common_Cancel', 'Cancel') }}
                            </x-button>

                            <x-button type="submit"
                                      variant="primary"
                                      x-tooltip="'{{ addslashes(lexicon('tooltip_Wiki_SaveArticle', 'Save wiki article configuration')) }}'">
                                <span x-text="wikiModal.mode === 'create' ? '{{ addslashes(lexicon('btn_Wiki_SaveArticle', 'Save Article')) }}' : '{{ addslashes(lexicon('btn_Wiki_UpdateArticle', 'Update Article')) }}'"></span>
                            </x-button>
                        </div>
                    </form>
                </x-modal>
            @endcan
        </x-slot:bottom>

    </x-admin-grid-wrapper>
</div>
@endsection