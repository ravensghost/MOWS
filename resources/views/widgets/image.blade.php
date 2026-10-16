{{-- 
@WidgetManifest({
    "name": "Single Image",
    "description": "Displays a single image with an optional caption.",
    "icon": "bi-image",
    "fields": {
        "url": { "type": "text", "label": "Image URL", "default": "" },
        "alt": { "type": "text", "label": "Alt Text / Caption", "default": "" }
    }
})
--}}
@php
    $options = is_string($widget->pivot->instance_options) ? json_decode($widget->pivot->instance_options, true) : ($widget->pivot->instance_options ?? []);
    $url = trim($options['url'] ?? '');
    $alt = trim($options['alt'] ?? '');
@endphp

<div class="bg-panel border border-panel rounded-panel shadow-panel flex flex-col overflow-hidden w-full mb-6 p-panel gap-sm" x-data>
    @if($url)
        <x-image-card :url="$url" :alt="$alt" />
        @if($alt)
            <span class="block text-sm text-muted text-center italic">{{ $alt }}</span>
        @endif
    @else
        <div class="bg-panel-sub border-dashed-panel rounded-panel p-panel text-center">
            <span class="text-muted italic">{{ lexicon('msg_common_ImageNotConfigured', 'No image URL has been configured.') }}</span>
        </div>
    @endif
</div>