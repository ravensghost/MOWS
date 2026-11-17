{{-- 
@WidgetManifest({
    "name": "Stream Link",
    "description": "Displays a link to a live broadcast.",
    "icon": "bi-broadcast",
    "fields": {
        "platform": { "type": "text", "label": "Platform (e.g. Twitch)", "default": "Twitch" },
        "url": { "type": "text", "label": "Channel URL", "default": "/" }
    }
})
--}}
@php
    $options = is_string($widget->pivot->instance_options) ? json_decode($widget->pivot->instance_options, true) : ($widget->pivot->instance_options ?? []);
    $platform = trim($options['platform'] ?? 'Stream');
    $url = trim($options['url'] ?? '/');
    if ($url === '#') {
        $url = '/';
    }
@endphp

<a href="{{ $url }}" 
   target="_blank" 
   rel="noopener noreferrer" 
   x-data
   aria-label="{{ lexicon('tooltip_common_WatchStream', 'Open live stream in a new tab') }}"
   x-tooltip="'{{ addslashes(lexicon('tooltip_common_WatchStream', 'Open live stream in a new tab')) }}'"
   class="bg-panel border border-panel rounded-panel shadow-panel flex flex-col overflow-hidden w-full mb-6 p-panel hover-bg-panel-hover transition-colors no-underline">
    <div class="flex flex-row flex-wrap items-center justify-between gap-md">
        <div class="flex flex-col gap-xs">
            <span class="text-xs font-bold text-muted uppercase tracking-wider flex items-center gap-xs">
                <i class="bi bi-broadcast text-accent" aria-hidden="true"></i>
                <span>{{ lexicon('label_common_LiveBroadcast', 'Live Broadcast') }}</span>
            </span>
            <span class="text-lg font-bold text-accent hover-text-h transition-colors">{{ lexicon('label_common_WatchOn', 'Watch on') }} {{ $platform }}</span>
        </div>
        <span class="text-2xl text-muted" aria-hidden="true">&rarr;</span>
    </div>
</a>