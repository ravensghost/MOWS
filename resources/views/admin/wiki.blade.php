{{-- resources/views/admin/wiki.blade.php --}}
@extends('layouts.app')

@section('title', lexicon('admin_label_WikiManager', 'Encyclopedia Codex Manager'))

@section('content')
<div x-data="encyclopediaCodexManager()"
     @ajax-success.window="refreshDOM()"
     x-ref="codexTableContent"
     class="flex flex-col gap-lg">

    <x-grid-wrapper :book="$systemBook">
        <x-slot:top>
            <x-page-header :title="lexicon('admin_label_WikiManager', 'Encyclopedia Codex Manager')"
                           :subtitle="lexicon('desc_admin_WikiManager', 'Author and categorise in-universe lore entries, technical schematics, species dossiers, and historical archives.')"
                           icon="bi-journal-bookmark">
                <x-slot:actions>
                    <x-metadata type="search"
                                name="wiki_search"
                                id="wiki_search"
                                :bare="true"
                                xModel="searchQuery"
                                :placeholder="lexicon('label_Wiki_SearchPlaceholder', 'Search by title, category, or lore text...')"
                                :tooltip="lexicon('tooltip_Wiki_SearchInput', 'Instantaneously filter codex articles using fuzzy search')" />

                    @can('manage_pages')
                        <x-button type="button"
                                  variant="success"
                                  size="sm"
                                  icon="bi-plus-lg"
                                  :tooltip="lexicon('tooltip_Wiki_CreateArticle', 'Create a new encyclopedia codex article')"
                                  @click="openModal('create')">
                            + {{ lexicon('btn_Wiki_CreateArticle', 'Create Article') }}
                        </x-button>
                    @endcan
                </x-slot:actions>
            </x-page-header>
        </x-slot:top>

        <x-slot:middle>
            <x-tab type="accordion"
                   :title="lexicon('heading_Wiki_Articles', 'Encyclopedia Codex Entries')"
                   icon="bi-collection"
                   :open="true">
                <div id="wiki-table-content" x-ref="wikiTableContent">
                    <x-table type="admin"
                             :headers="[
                                 lexicon('label_common_Title', 'Article Title'),
                                 lexicon('label_common_Category', 'Category'),
                                 lexicon('label_common_Status', 'Status'),
                                 lexicon('label_common_Actions', 'Actions')
                             ]"
                             :emptyText="lexicon('msg_Wiki_NoArticles', 'No encyclopedia codex articles found.')">
                        @foreach($articles ?? [] as $article)
                            <tr class="border-b-table-row hover-bg-panel-hover transition-colors"
                                data-search-item
                                data-wiki-search-item
                                data-id="{{ $article->id }}"
                                data-article-title="{{ $article->article_title }}"
                                data-article_title="{{ $article->article_title }}"
                                data-article_category="{{ $article->article_category }}"
                                data-article_text="{{ strip_tags($article->article_text) }}"
                                x-show="isArticleVisible({{ $article->id }})"
                                x-cloak>
                                <td class="p-4 font-bold text-h">{{ $article->article_title }}</td>
                                <td class="p-4">
                                    <x-badge type="pill" variant="primary">{{ $article->article_category }}</x-badge>
                                </td>
                                <td class="p-4">
                                    <x-badge type="pill"
                                             :variant="$article->is_visible ? 'success' : 'warning'"
                                             :tooltip="$article->is_visible ? lexicon('tooltip_Wiki_StatusPublished', 'This article is live and visible') : lexicon('tooltip_Wiki_StatusHidden', 'This article is hidden from view')">
                                        {{ $article->is_visible ? lexicon('label_common_Published', 'Published') : lexicon('label_common_Hidden', 'Hidden') }}
                                    </x-badge>
                                </td>
                                <td class="p-4 text-right">
                                    @can('manage_pages')
                                        <div class="flex flex-row flex-wrap justify-end items-center gap-xs">
                                            <x-button type="button"
                                                      variant="ghost"
                                                      size="sm"
                                                      icon="bi-pencil"
                                                      :tooltip="lexicon('tooltip_common_Edit', 'Edit this codex article')"
                                                      @click="openModal('edit', {{ Js::from([
                                                          'id' => $article->id,
                                                          'article_title' => $article->article_title,
                                                          'article_category' => $article->article_category,
                                                          'article_text' => $article->article_text,
                                                          'is_visible' => (bool) $article->is_visible,
                                                      ]) }})">
                                                {{ lexicon('btn_common_Edit', 'Edit') }}
                                            </x-button>

                                            <x-button type="button"
                                                      variant="danger"
                                                      size="sm"
                                                      icon="bi-trash"
                                                      :tooltip="lexicon('tooltip_common_Delete', 'Delete this article permanently')"
                                                      x-data="deleteAction('/admin/encyclopedia-codex/{{ $article->id }}', 'DELETE', '{{ addslashes($article->article_title) }}')"
                                                      @click="confirmDelete()">
                                                {{ lexicon('btn_common_Delete', 'Delete') }}
                                            </x-button>
                                        </div>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </x-table>
                </div>
            </x-tab>
        </x-slot:middle>

        <x-slot:bottom>
            {{-- MODAL: CREATE / EDIT ENCYCLOPEDIA CODEX ARTICLE --}}
            @can('manage_pages')
                <x-modal show="modals.article.open" onClose="closeModal()" maxWidth="4xl">
                    <x-slot:top>
                        <span x-text="modals.article.mode === 'create'
                            ? '{{ addslashes(lexicon('label_Wiki_CreateTitle', 'Create Encyclopedia Article')) }}'
                            : '{{ addslashes(lexicon('label_Wiki_EditTitle', 'Edit Encyclopedia Article')) }}'"></span>
                    </x-slot:top>

                    <x-meta type="modal" xAction="modals.article.action" xMethod="modals.article.method">
                        <div class="grid grid-cols-1 md-grid-cols-2 gap-md">
                            <x-metadata type="text"
                                        name="article_title"
                                        id="wiki_article_title"
                                        xModel="modals.article.article_title"
                                        :label="lexicon('label_common_Title', 'Article Title')"
                                        :tooltip="lexicon('tooltip_Wiki_TitleInput', 'Enter article title')"
                                        :required="true" />

                            <x-metadata type="text"
                                        name="article_category"
                                        id="wiki_article_category"
                                        xModel="modals.article.article_category"
                                        :label="lexicon('label_common_Category', 'Category Namespace')"
                                        :tooltip="lexicon('tooltip_Wiki_CategoryInput', 'Enter category namespace (e.g. Technology, Species, History)')"
                                        :required="true" />
                        </div>

                        <div class="flex flex-col gap-xs">
                            <label class="text-xs font-bold text-input-muted uppercase text-wider">
                                {{ lexicon('label_common_Content', 'Article Content') }}
                            </label>
                            <div class="editor-container bg-panel-sub border-panel border-rounded flex flex-col overflow-hidden"
                                 x-data="tiptapHelper(modals.article.article_text)"
                                 @tiptap-update="modals.article.article_text = $event.detail"
                                 @set-editor-content.window="setEditorContent($event.detail.content)">
                                <x-tt-tb name="article_text" />
                            </div>
                        </div>

                        <input type="hidden" name="is_visible" value="0">
                        <x-metadata type="checkbox"
                                    id="wiki_is_visible"
                                    name="is_visible"
                                    value="1"
                                    xModel="modals.article.is_visible"
                                    :label="lexicon('label_common_ShowOnSite', 'Show on Site (Published)')" />

                        <x-slot:footer>
                            <x-button type="button"
                                      variant="secondary"
                                      :tooltip="lexicon('tooltip_common_Cancel', 'Cancel and close modal')"
                                      @click="closeModal()">
                                {{ lexicon('btn_common_Cancel', 'Cancel') }}
                            </x-button>

                            <x-button type="submit"
                                      variant="primary"
                                      icon="bi-save"
                                      :tooltip="lexicon('tooltip_Wiki_SaveArticle', 'Save encyclopedia article')">
                                <span x-text="modals.article.mode === 'create'
                                    ? '{{ addslashes(lexicon('btn_Wiki_SaveArticle', 'Save Article')) }}'
                                    : '{{ addslashes(lexicon('btn_Wiki_UpdateArticle', 'Update Article')) }}'"></span>
                            </x-button>
                        </x-slot:footer>
                    </x-meta>
                </x-modal>
            @endcan
        </x-slot:bottom>
    </x-grid-wrapper>
</div>
@endsection