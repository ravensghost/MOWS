{{-- resources/views/modules/encyclopedia_codex.blade.php --}}
{{-- @WidgetManifest({ "name": "Encyclopedia Codex", "description": "Displays world-building lore articles, technical manuals, and location guides with instant search and category filtering.", "icon": "bi-journal-bookmark-fill", "fields": { "title": { "type": "text", "label": "Encyclopedia Title", "default": "Encyclopedia Codex" }, "category": { "type": "text", "label": "Default Category Filter (Optional)", "default": "" } } }) --}}
@php
    $rawOptions = $moduleSettings
        ?? $module?->pivot?->book_config
        ?? $module?->pivot?->instance_options
        ?? $widget?->pivot?->instance_options
        ?? [];
    $options = is_string($rawOptions) ? (json_decode($rawOptions, true) ?: []) : (is_array($rawOptions) ? $rawOptions : []);

    $rawTitle = trim($options['title'] ?? '');
    $title = $rawTitle !== '' ? $rawTitle : lexicon('label_Wiki_DefaultWidgetTitle', 'Encyclopedia Codex');
    $defaultCategory = trim($options['category'] ?? '');

    $articles = $codexArticles
        ?? \App\Models\EncyclopediaCodex::where('is_visible', true)
            ->orderBy('article_category')
            ->orderBy('article_title')
            ->get();

    $categories = $articles->pluck('article_category')
        ->filter()
        ->unique()
        ->values();
@endphp

<div x-data="encyclopediaCodexManager()"
     class="w-full">
    <x-tab type="accordion"
           :title="$title"
           icon="bi-journal-bookmark-fill"
           :open="true">
        <div x-data="{ activeCategory: @js($defaultCategory) }"
             class="flex flex-col gap-md w-full">

            {{-- Search & Category Filter Bar --}}
            <div class="bg-panel-sub border-panel border-rounded p-3 flex flex-col gap-sm">
                <x-metadata type="search"
                            name="codex_search"
                            xModel="searchQuery"
                            :placeholder="lexicon('placeholder_Wiki_SearchArticles', 'Search lore articles, locations, or technology...')"
                            :tooltip="lexicon('tooltip_Wiki_SearchArticles', 'Filter encyclopedia articles by title, category, or content')" />

                @if($categories->isNotEmpty())
                    <div class="flex flex-row flex-wrap items-center gap-xs">
                        <x-button type="button"
                                  size="sm"
                                  variant="ghost"
                                  ::class="activeCategory === '' ? 'bg-primary text-h border-accent' : 'bg-transparent text-muted hover-bg-panel-hover hover-text-h'"
                                  @click="activeCategory = ''"
                                  :tooltip="lexicon('tooltip_Wiki_ShowAllCategories', 'Show articles from all categories')">
                            {{ lexicon('btn_common_All', 'All') }}
                        </x-button>

                        @foreach($categories as $categoryName)
                            <x-button type="button"
                                      size="sm"
                                      variant="ghost"
                                      ::class="activeCategory === @js($categoryName) ? 'bg-primary text-h border-accent' : 'bg-transparent text-muted hover-bg-panel-hover hover-text-h'"
                                      @click="activeCategory = @js($categoryName)"
                                      :tooltip="lexicon('tooltip_Wiki_FilterByCategory', 'Filter by category') . ': ' . $categoryName">
                                {{ $categoryName }}
                            </x-button>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Articles List --}}
            @if($articles->isEmpty())
                <div class="bg-panel-sub border-dashed-panel border-rounded p-panel-body text-center">
                    <span class="text-sm text-muted font-italic">{{ lexicon('msg_Wiki_NoArticles', 'No encyclopedia codex entries have been published yet.') }}</span>
                </div>
            @else
                <div class="flex flex-col gap-sm">
                    @foreach($articles as $article)
                        <div data-search-item
                             data-id="{{ $article->id }}"
                             data-article-title="{{ $article->article_title }}"
                             data-article-category="{{ $article->article_category }}"
                             data-article-text="{{ strip_tags((string) $article->article_text) }}"
                             x-show="isItemVisible('{{ $article->id }}') && (activeCategory === '' || activeCategory === @js($article->article_category))"
                             x-cloak>
                            <x-tab type="accordion"
                                   :title="$article->article_title"
                                   icon="bi-file-earmark-richtext"
                                   :open="false">
                                <x-slot:top>
                                    @if(!empty($article->article_category))
                                        <x-badge type="pill"
                                                 variant="info"
                                                 :tooltip="lexicon('tooltip_Wiki_CategoryBadge', 'Codex Category')">
                                            {{ $article->article_category }}
                                        </x-badge>
                                    @endif
                                </x-slot:top>

                                <div class="text-sm text-p leading-relaxed tiptap-content">
                                    {!! \Stevebauman\Purify\Facades\Purify::clean($article->article_text) !!}
                                </div>
                            </x-tab>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </x-tab>
</div>