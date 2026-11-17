{{-- resources/views/modules/document.blade.php --}}
{{-- @WidgetManifest({ "name": "Rich Text Document", "description": "A standard formatted text block.", "icon": "bi-file-text", "fields": { "title": { "type": "text", "label": "Document Title (Leave blank to hide header)", "default": "" }, "content": { "type": "richtext", "label": "Document Content", "default": "<p>Start typing here...</p>" } } }) --}}
@php
    $rawOptions =$moduleSettings
        ?? $module?->pivot?->book_config
        ?? $module?->pivot?->instance_options
        ?? $widget?->pivot?->instance_options
        ?? [];
    $options = is_string($rawOptions) ? (json_decode($rawOptions, true) ?: []) : (is_array($rawOptions) ?$rawOptions : []);

    $title = trim($options['title'] ?? '');
    $content = trim($options['content'] ?? '');
@endphp

<x-tab :type="$title !== '' ? 'accordion' : 'panel'"
       :title="$title"
       :icon="$title !== '' ? 'bi-file-text' : null"
       :open="true">
    @if($content !== '')
        <div class="text-sm text-p leading-relaxed tiptap-content">
            {!! \Stevebauman\Purify\Facades\Purify::clean($content) !!}
        </div>
    @else
        <div class="bg-panel-sub border-dashed-panel border-rounded p-panel-body text-center">
            <span class="text-sm text-muted font-italic">{{ lexicon('msg_common_DocumentEmpty', 'Document content is empty.') }}</span>
        </div>
    @endif
</x-tab>