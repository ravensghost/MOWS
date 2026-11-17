{{-- resources/views/modules/stream_link.blade.php --}}
{{-- @WidgetManifest({ "name": "Stream Link", "description": "Displays a link to a live broadcast.", "icon": "bi-broadcast", "fields": { "platform": { "type": "text", "label": "Platform (e.g. Twitch)", "default": "Twitch" }, "url": { "type": "text", "label": "Channel URL", "default": "/" } } }) --}}
@php
    $rawOptions =$moduleSettings
        ?? $module?->pivot?->book_config
        ?? $module?->pivot?->instance_options
        ?? $widget?->pivot?->instance_options
        ?? [];
    $options = is_string($rawOptions) ? (json_decode($rawOptions, true) ?: []) : (is_array($rawOptions) ?$rawOptions : []);

    $platform = trim((string) ($options['platform'] ?? 'Stream')) ?: 'Stream';
    $url = trim((string) ($options['url'] ?? '/')) ?: '/';
    if ($url === '#') {
        $url = '/';
    }
    $streamTooltip = lexicon('tooltip_common_WatchStream', 'Open live stream in a new tab');
@endphp

<x-tab type="panel">
    <a href="{{ $url }}"
       target="_blank"
       rel="noopener noreferrer"
       x-data
       x-tooltip="'{{ addslashes($streamTooltip) }}'"
       title="{{ $streamTooltip }}"
       aria-label="{{ $streamTooltip }}"
       class="bg-panel-sub border-panel border-rounded p-4 hover-bg-panel-hover hover-border-accent transition-colors flex flex-row flex-wrap items-center justify-between gap-md no-underline w-full">
        <div class="flex flex-col gap-xs">
            <span class="text-xs font-bold text-muted uppercase text-wider flex flex-row items-center gap-xs">
                <i class="bi bi-broadcast text-accent" aria-hidden="true"></i>
                <span>{{ lexicon('label_common_LiveBroadcast', 'Live Broadcast') }}</span>
            </span>
            <span class="text-lg font-bold text-accent hover-text-h transition-colors">
                {{ lexicon('label_common_WatchOn', 'Watch on') }} {{ $platform }}
            </span>
        </div>
        <i class="bi bi-arrow-right text-xl text-muted" aria-hidden="true"></i>
    </a>
</x-tab>