{{-- 
@WidgetManifest({
    "name": "Rich Text Document",
    "description": "A standard formatted text block.",
    "icon": "bi-file-text",
    "fields": {
        "title": {
            "type": "text",
            "label": "Document Title (Leave blank to hide header)",
            "default": ""
        },
        "content": {
            "type": "richtext",
            "label": "Document Content",
            "default": "<p>Start typing here...</p>"
        }
    }
})
--}}
@php
    $options = is_string($widget->pivot->instance_options) ? json_decode($widget->pivot->instance_options, true) : ($widget->pivot->instance_options ?? []);
    $title = trim($options['title'] ?? '');
    $content = trim($options['content'] ?? '');
@endphp

<div class="bg-panel border border-panel rounded-panel shadow-panel flex flex-col overflow-hidden w-full mb-6" x-data>
    @if($title)
        <div class="bg-panel-header text-h border-b border-panel p-panel-header font-bold">
            <span class="text-xl font-bold text-accent">
                {{ $title }}
            </span>
        </div>
    @endif
    
    <div class="p-panel flex flex-col flex-grow gap-md text-muted leading-relaxed tiptap-content">
        @if($content)
            {!! \Stevebauman\Purify\Facades\Purify::clean($content) !!}
        @else
            <span class="text-muted italic">{{ lexicon('msg_common_DocumentEmpty', 'Document content is empty.') }}</span>
        @endif
    </div>
</div>