{{-- resources/views/modules/folio.blade.php --}}
{{-- @WidgetManifest({ "name": "Story Folios", "description": "Displays the latest published story folios.", "icon": "bi-journal-text", "fields": { "title": { "type": "text", "label": "Module Title", "default": "Recent Story Folios" }, "limit": { "type": "number", "label": "Number of Folios to Show", "default": "5" } } }) --}}
@php
    $rawOptions = $moduleSettings
        ?? $module?->pivot?->book_config
        ?? $module?->pivot?->instance_options
        ?? $widget?->pivot?->instance_options
        ?? [];
    $options = is_string($rawOptions) ? (json_decode($rawOptions, true) ?: []) : (is_array($rawOptions) ? $rawOptions : []);

    $rawTitle = trim($options['title'] ?? '');
    $title = $rawTitle !== '' ? $rawTitle : lexicon('label_Folio_DefaultWidgetTitle', 'Recent Story Folios');
    $limit = max(1, (int) ($options['limit'] ?? 5));

    $foliosList = $storyPosts
        ?? $folios
        ?? \App\Models\Folio::with(['activePersona', 'volume'])
            ->where('folio_status', 'published')
            ->orderByDesc('created_at')
            ->take($limit)
            ->get();

    $bypassMode = \App\Models\Configuration::where('configuration_token', 'content_shield_bypass')
        ->value('configuration_payload') ?? 'locked';
    $userBypassed = auth()->check() && $bypassMode === 'user_bypass' && (bool) (auth()->user()->content_shield_bypass ?? false);
@endphp

<x-tab type="accordion"
       :title="$title"
       icon="bi-journal-text"
       :open="true">
    @forelse($foliosList as $folio)
        @php
            $tierL = (int) ($folio->content_tier_l ?? 0);
            $tierS = (int) ($folio->content_tier_s ?? 0);
            $tierV = (int) ($folio->content_tier_v ?? 0);
            $isMature = ($tierL >= 3 || $tierS >= 3 || $tierV >= 3);
            $showGate = $isMature && !$userBypassed;

            $personaName = $folio->activePersona->persona_name
                ?? lexicon('label_common_Narrator', 'Narrator');
            $sceneLocation = $folio->scene_location
                ?? lexicon('label_common_UnknownLocation', 'Unknown Location');
            $ratingString = "{$tierL}-{$tierS}-{$tierV}";
        @endphp

        <div class="bg-panel-sub border-panel border-rounded p-4 flex flex-col gap-sm hover-border-accent transition-colors"
             x-data="{ revealed: @js(!$showGate) }">
            <div class="flex flex-row flex-wrap justify-between items-center gap-sm text-sm text-muted pb-2 border-b-panel">
                <div class="flex flex-row flex-wrap items-center gap-xs">
                    <i class="bi bi-person-fill text-accent" aria-hidden="true"></i>
                    <span class="font-bold text-h">{{ $personaName }}</span>
                </div>

                <div class="flex flex-row flex-wrap items-center gap-sm">
                    <span class="text-xs text-muted">
                        {{ lexicon('label_common_Day', 'Day') }} {{ $folio->ic_day ?? 1 }}, {{ $folio->ic_time ?? '0800' }} &bull; {{ $sceneLocation }}
                    </span>

                    <x-badge type="pill"
                             :variant="$isMature ? 'danger' : 'info'"
                             :tooltip="lexicon('tooltip_Folio_ContentTierRating', 'Content Tier Rating (L-S-V)')">
                        {{ $ratingString }}
                    </x-badge>
                </div>
            </div>

            <template x-if="!revealed">
                <div class="bg-panel-body border-danger border-rounded p-panel-body flex flex-col items-center justify-center gap-sm text-center">
                    <i class="bi bi-shield-exclamation text-danger text-2xl" aria-hidden="true"></i>
                    <span class="font-bold text-h">{{ lexicon('label_Folio_MatureContentNotice', 'Mature Content Notice') }}</span>
                    <span class="text-xs text-muted">{{ lexicon('msg_Folio_MatureContentWarning', 'This folio contains mature Content Tier ratings.') }}</span>
                    <x-button type="button"
                              variant="danger"
                              size="sm"
                              icon="bi-eye"
                              :tooltip="lexicon('tooltip_Folio_RevealMatureContent', 'Click to reveal mature story content')"
                              @click="revealed = true">
                        {{ lexicon('btn_Folio_RevealContent', 'Reveal Content') }}
                    </x-button>
                </div>
            </template>

            <template x-if="revealed">
                <div class="flex flex-col gap-sm">
                    <span class="text-sm text-p leading-relaxed block">
                        {{ \Illuminate\Support\Str::limit(strip_tags((string) $folio->folio_text), 300) }}
                    </span>

                    <div class="border-t-actions pt-2 flex flex-row items-center justify-end">
                        <a href="/folio/{{ $folio->id }}"
                           class="text-accent hover-text-h text-sm font-bold inline-block transition-colors no-underline"
                           x-tooltip="'{{ addslashes(lexicon('tooltip_Folio_ReadFull', 'Open full story folio')) }}'"
                           title="{{ lexicon('tooltip_Folio_ReadFull', 'Open full story folio') }}">
                            {{ lexicon('btn_Folio_ReadFull', 'Read Full Folio') }} &rarr;
                        </a>
                    </div>
                </div>
            </template>
        </div>
    @empty
        <div class="bg-panel-sub border-dashed-panel border-rounded p-panel-body text-center">
            <span class="text-sm text-muted font-italic">{{ lexicon('msg_Folio_NoPublicTransmissions', 'No recent public transmissions detected.') }}</span>
        </div>
    @endforelse
</x-tab>