{{-- 
@WidgetManifest({
    "name": "Story Chapters",
    "description": "Displays the latest published chapters.",
    "icon": "bi-journal-text"
})
--}}
<div class="flex flex-col gap-md w-full mb-6" x-data>
    <span class="text-2xl font-bold text-h block pb-2">{{ $widget->name }}</span>

    @forelse($storyPosts ?? [] as $chapter)
        <div class="bg-panel border border-panel rounded-panel shadow-panel flex flex-col overflow-hidden p-panel gap-sm hover-border-accent transition-colors">
            <div class="flex flex-row flex-wrap justify-between items-center gap-sm text-sm text-muted pb-2">
                <span class="font-bold text-p">
                    {{ $chapter->activeCharacter->name ?? lexicon('label_common_UnknownCharacter', 'Unknown') }}
                </span>
                <div class="flex flex-row flex-wrap items-center gap-sm">
                    <span class="text-xs">
                        {{ lexicon('label_common_Day', 'Day') }} {{ $chapter->ic_day }}, {{ $chapter->ic_time }} | {{ $chapter->location ?? lexicon('label_common_UnknownLocation', 'Unknown Location') }}
                    </span>
                    <x-badge type="info"
                             aria-label="{{ lexicon('tooltip_Story_ChapterLsvBadge', 'Language, Sex, and Violence content rating score') }}"
                             x-tooltip="'{{ addslashes(lexicon('tooltip_Story_ChapterLsvBadge', 'Language, Sex, and Violence content rating score')) }}'">
                        {{ $chapter->rating_l ?? 0 }}-{{ $chapter->rating_s ?? 0 }}-{{ $chapter->rating_v ?? 0 }}
                    </x-badge>
                </div>
            </div>
            
            <span class="text-muted block">
                {{ \Illuminate\Support\Str::limit(strip_tags($chapter->content), 300) }}
            </span>
            
            <div class="pt-2">
                <a href="/chapter/{{ $chapter->id }}" 
                   class="text-accent hover-text-h text-sm font-bold inline-block transition-colors no-underline"
                   aria-label="{{ lexicon('tooltip_WriteChapter_ReadFullChapter', 'Read the full story chapter') }}"
                   x-tooltip="'{{ addslashes(lexicon('tooltip_WriteChapter_ReadFullChapter', 'Read the full story chapter')) }}'">
                    {{ lexicon('btn_common_ReadFull', 'Read Full') }} {{ lexicon('label_common_Post', 'Post') }} &rarr;
                </a>
            </div>
        </div>
    @empty
        <div class="bg-panel-sub border-dashed-panel rounded-panel p-panel text-center">
            <span class="text-muted italic">{{ lexicon('msg_common_NoRecentTransmissions', 'No recent public transmissions detected.') }}</span>
        </div>
    @endforelse
</div>