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
        @php
            $isMature = ($chapter->rating_l >= 3 || $chapter->rating_s >= 3 || $chapter->rating_v >= 3);
            $bypassMode = \App\Models\Setting::where('key', 'content_shield_bypass')->value('value') ?? 'locked';
            $userBypassed = auth()->check() && $bypassMode === 'user_bypass' && auth()->user()->content_shield_bypass;
            $showGate = $isMature && !$userBypassed;
        @endphp
        <div class="bg-panel border border-panel rounded-panel shadow-panel flex flex-col overflow-hidden p-panel gap-sm hover-border-accent transition-colors"
             x-data="{ revealed: {{ $showGate ? 'false' : 'true' }} }">
            <div class="flex flex-row flex-wrap justify-between items-center gap-sm text-sm text-muted pb-2">
                <span class="font-bold text-p">
                    {{ $chapter->activeCharacter->name ?? lexicon('label_common_UnknownCharacter', 'Unknown') }}
                </span>
                <div class="flex flex-row flex-wrap items-center gap-sm">
                    <span class="text-xs">
                        {{ lexicon('label_common_Day', 'Day') }} {{ $chapter->ic_day }}, {{ $chapter->ic_time }} | {{ $chapter->location ?? lexicon('label_common_UnknownLocation', 'Unknown Location') }}
                    </span>
                    <x-badge type="{{ $isMature ? 'danger' : 'info' }}"
                             aria-label="{{ lexicon('tooltip_Story_ChapterLsvBadge', 'Language, Sex, and Violence content rating score') }}"
                             x-tooltip="'{{ addslashes(lexicon('tooltip_Story_ChapterLsvBadge', 'Language, Sex, and Violence content rating score')) }}'">
                        {{ $chapter->rating_l ?? 0 }}-{{ $chapter->rating_s ?? 0 }}-{{ $chapter->rating_v ?? 0 }}
                    </x-badge>
                </div>
            </div>
            
            <!-- Mature Content Shield Gate -->
            <template x-if="!revealed">
                <div class="panel-sub border-danger p-4 rounded-panel flex flex-col items-center justify-center gap-sm text-center">
                    <i class="bi bi-shield-exclamation text-danger text-2xl"></i>
                    <span class="font-bold text-main">{{ lexicon('label_ContentShield_Warning', 'Mature Content Notice') }}</span>
                    <span class="text-xs text-muted">{{ lexicon('msg_ContentShield_Description', 'This chapter contains mature Language, Sex, or Violence ratings.') }}</span>
                    <x-button variant="danger" size="sm" @click="revealed = true">
                        {{ lexicon('btn_ContentShield_Reveal', 'Reveal Content') }}
                    </x-button>
                </div>
            </template>

            <template x-if="revealed">
                <span class="text-muted block">
                    {{ \Illuminate\Support\Str::limit(strip_tags($chapter->content), 300) }}
                </span>
            </template>
            
            <div class="pt-2" x-show="revealed">
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