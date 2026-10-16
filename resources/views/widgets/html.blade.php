{{-- 
@WidgetManifest({
    "name": "Raw HTML Embed",
    "description": "Used internally. Hidden from page builder.",
    "hidden": true,
    "fields": {
        "content": { "type": "textarea", "label": "HTML Code", "default": "" }
    }
})
--}}
@php
    $options = is_string($widget->pivot->instance_options) ? json_decode($widget->pivot->instance_options, true) : ($widget->pivot->instance_options ?? []);
    $rawContent = trim($options['content'] ?? '');
@endphp

<div class="bg-panel-sub border-dashed-panel rounded-panel p-panel text-center shadow-panel flex flex-col overflow-hidden w-full mb-6" x-data>
    @if($rawContent)
        {!! \Stevebauman\Purify\Facades\Purify::clean($rawContent) !!}
    @else
        <span class="text-muted italic">{{ lexicon('msg_common_HtmlEmpty', 'No HTML content provided.') }}</span>
    @endif
</div>