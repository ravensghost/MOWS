{{-- resources/views/modules/html.blade.php --}}
{{-- @WidgetManifest({ "name": "Raw HTML Embed", "description": "Used internally. Hidden from page builder.", "hidden": true, "fields": { "content": { "type": "textarea", "label": "HTML Code", "default": "" } } }) --}}
@php
    $rawOptions =$moduleSettings
        ?? $module?->pivot?->book_config
        ?? $module?->pivot?->instance_options
        ?? $widget?->pivot?->instance_options
        ?? [];
    $options = is_string($rawOptions) ? (json_decode($rawOptions, true) ?: []) : (is_array($rawOptions) ?$rawOptions : []);

    $rawContent = trim((string) ($options['content'] ?? ''));
@endphp

<x-tab type="panel">
    @if($rawContent !== '')
        <div class="text-sm text-p leading-relaxed tiptap-content w-full overflow-x-auto">
            {!! \Stevebauman\Purify\Facades\Purify::clean($rawContent) !!}
        </div>
    @else
        <div class="bg-panel-sub border-dashed-panel border-rounded p-panel-body text-center">
            <span class="text-sm text-muted font-italic">{{ lexicon('msg_common_HtmlEmpty', 'No HTML content configured.') }}</span>
        </div>
    @endif
</x-tab>