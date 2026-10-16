{{-- 
@WidgetManifest({
    "name": "Social Feed Embed",
    "description": "Embeds a raw social media script or iframe.",
    "icon": "bi-chat-quote",
    "fields": {
        "platform": { "type": "text", "label": "Platform Name", "default": "Social" },
        "embed_code": { "type": "textarea", "label": "Native Embed HTML", "default": "" }
    }
})
--}}
@php
    $options = is_string($widget->pivot->instance_options) ? json_decode($widget->pivot->instance_options, true) : ($widget->pivot->instance_options ?? []);
    $platform = trim($options['platform'] ?? 'Social');
    $embedCode = trim($options['embed_code'] ?? '');
@endphp

<div class="bg-panel border border-panel rounded-panel shadow-panel flex flex-col overflow-hidden w-full mb-6" x-data>
    <div class="bg-panel-header text-h border-b border-panel p-panel-header font-bold">
        <span class="text-xl font-bold text-accent">{{ $platform }} {{ lexicon('label_common_Feed', 'Feed') }}</span>
    </div>
    
    {{-- Render the raw script/iframe unescaped or fallback if empty --}}
    <div class="p-panel flex flex-row flex-wrap items-center justify-center overflow-x-auto">
        @if($embedCode)
            {!! $embedCode !!}
        @else
            <span class="text-muted italic">{{ lexicon('msg_common_SocialFeedEmpty', 'No social feed embed code configured.') }}</span>
        @endif
    </div>
</div>