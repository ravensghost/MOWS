{{-- 
@WidgetManifest({
    "name": "Story Folios",
    "description": "Displays the latest published story folios.",
    "icon": "bi-journal-text"
})
--}}
<div class="flex flex-col gap-md w-full mb-lg" x-data>
    <span class="text-2xl font-bold text-h block pb-sm">{{ $widget->module_title ?? $widget->name ?? 'Story Folios' }}</span>

    @forelse($storyPosts ?? [] as $folio)
        @php
            $isMature = ($folio->content_tier_l >= 3 || $folio->content_tier_s >= 3 || $folio->content_tier_v >= 3);
            $bypassMode = \App\Models\Configuration::where('configuration_token', 'content_shield_bypass')->value('configuration_payload') ?? 'locked';
            $userBypassed = auth()->check() && $bypassMode === 'user_bypass' && auth()->user()->content_shield_bypass;
            $showGate = $isMature && !$userBypassed;
        @endphp
        <div class="bg-panel border-panel rounded-panel shadow-panel flex flex-col overflow-hidden p-panel gap-sm hover-border-accent transition-colors"
             x-data="{ revealed: {{ $showGate ? 'false' : 'true' }} }">
            <div class="flex flex-row flex-wrap justify-between items-center gap-sm text-sm text-muted pb-sm">
                <span class="font-bold text-p">
                    {{ $folio->activePersona->persona_name ?? lexicon('label_common_UnknownPersona', 'Unknown Persona') }}
                </span>
                <div class="flex flex-row flex-wrap items-center gap-sm">
                    <span class="text-xs">
                        Day {{ $folio->ic_day }}, {{ $folio->ic_time }} &bull; {{ $folio->scene_location ?? 'Unknown Location' }}
                    </span>
                    <x-badge type="{{ $isMature ? 'danger' : 'info' }}">
                        {{ $folio->content_tier_l ?? 0 }}-{{ $folio->content_tier_s ?? 0 }}-{{ $folio->content_tier_v ?? 0 }}
                    </x-badge>
                </div>
            </div>
            
            <template x-if="!revealed">
                <div class="bg-panel-sub border-danger p-panel rounded-panel flex flex-col items-center justify-center gap-sm text-center">
                    <i class="bi bi-shield-exclamation text-danger text-2xl"></i>
                    <span class="font-bold text-main">Mature Content Notice</span>
                    <span class="text-xs text-muted">This folio contains mature Content Tier ratings.</span>
                    <x-button variant="danger" size="sm" @click="revealed = true">Reveal Content</x-button>
                </div>
            </template>

            <template x-if="revealed">
                <span class="text-muted block">
                    {{ \Illuminate\Support\Str::limit(strip_tags($folio->folio_text), 300) }}
                </span>
            </template>
            
            <div class="pt-sm" x-show="revealed">
                <a href="/folio/{{ $folio->id }}" class="text-accent hover-text-h text-sm font-bold inline-block transition-colors no-underline">
                    Read Full Folio &rarr;
                </a>
            </div>
        </div>
    @empty
        <div class="bg-panel-sub border-dashed-panel rounded-panel p-panel text-center">
            <span class="text-muted italic">No recent public transmissions detected.</span>
        </div>
    @endforelse
</div>