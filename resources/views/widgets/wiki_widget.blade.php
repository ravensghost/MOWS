{{-- 
@WidgetManifest({
    "name": "Wiki Encyclopedia",
    "description": "Displays lore articles, world-building notes, and guides with search and category filtering.",
    "icon": "bi-journal-richtext",
    "fields": {
        "title": {
            "type": "text",
            "label": "Encyclopedia Title (Leave blank to hide header)",
            "default": "Encyclopedia"
        }
    }
})
--}}
@php
    $articles = \App\Models\WikiArticle::where('is_visible', true)->orderBy('category')->orderBy('title')->get();
    $categories = $articles->pluck('category')->unique();
@endphp

<div class="w-full" x-data="{ search: '', selectedCategory: 'all' }">
    <!-- Search & Category Filters -->
    <div class="flex flex-col md:flex-row gap-md mb-4 justify-between items-center">
        <input type="text" x-model="search" placeholder="Search lore & entries..." class="bg-input text-input border-input rounded-input p-2 w-full md:w-1/3">
        
        <div class="flex flex-wrap gap-2">
            <button @click="selectedCategory = 'all'" :class="selectedCategory === 'all' ? 'bg-accent text-white' : 'bg-panel text-muted'" class="px-3 py-1 rounded text-xs font-semibold">All</button>
            @foreach($categories as $cat)
                <button @click="selectedCategory = '{{ $cat }}'" :class="selectedCategory === '{{ $cat }}' ? 'bg-accent text-white' : 'bg-panel text-muted'" class="px-3 py-1 rounded text-xs font-semibold">{{ $cat }}</button>
            @endforeach
        </div>
    </div>

    <!-- Articles Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
        @foreach($articles as $article)
            <div class="bg-panel border-panel rounded-panel shadow-panel p-4 flex flex-col justify-between"
                 x-show="(selectedCategory === 'all' || selectedCategory === '{{ $article->category }}') && (search === '' || '{{ strtolower($article->title) }}'.includes(search.toLowerCase()) || '{{ strtolower($article->content) }}'.includes(search.toLowerCase()))">
                <div>
                    <div class="flex justify-between items-center mb-2">
                        <h3 class="text-lg font-bold text-h">{{ $article->title }}</h3>
                        <span class="text-xs px-2 py-0.5 bg-accent-muted text-accent rounded">{{ $article->category }}</span>
                    </div>
                    <div class="text-sm text-body prose max-none">
                        {!! $article->content !!}
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>