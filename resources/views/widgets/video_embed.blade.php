{{-- 
@WidgetManifest({
    "name": "Video Player",
    "description": "Embeds an iframe for external videos (e.g. YouTube).",
    "icon": "bi-play-btn",
    "fields": {
        "title": { "type": "text", "label": "Video Title", "default": "Video Player" },
        "url": { "type": "text", "label": "Iframe Embed URL", "default": "" }
    }
})
--}}
@php
    $options = is_string($widget->pivot->instance_options) ? json_decode($widget->pivot->instance_options, true) : ($widget->pivot->instance_options ?? []);
    $title = trim($options['title'] ?? 'Video Player');
    $url = trim($options['url'] ?? '');
    $videoLabel = $title ?: lexicon('label_common_VideoPlayer', 'Video Player');
@endphp

<div class="bg-panel border border-panel rounded-panel shadow-panel flex flex-col overflow-hidden w-full mb-6" x-data>
    @if($title)
        <div class="bg-panel-header text-h border-b border-panel p-panel-header font-bold">
            <span class="text-xl font-bold text-accent block">{{ $title }}</span>
        </div>
    @endif
    
    <div class="p-panel flex items-center justify-center">
        @if($url)
            <iframe src="{{ $url }}" 
                    width="100%" 
                    height="400" 
                    aria-label="{{ $videoLabel }}"
                    class="w-full border-none rounded-input" 
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                    allowfullscreen></iframe>
        @else
            <div class="bg-panel-sub border-dashed-panel rounded-panel p-panel text-center w-full">
                <span class="text-muted italic">{{ lexicon('msg_common_VideoNotConfigured', 'No video embed URL has been configured.') }}</span>
            </div>
        @endif
    </div>
</div>