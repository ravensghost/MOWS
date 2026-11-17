{{-- resources/views/modules/image.blade.php --}}
{{-- @WidgetManifest({ "name": "Single Image", "description": "Displays a single image with an optional caption.", "icon": "bi-image", "fields": { "title": { "type": "text", "label": "Image Title (Leave blank to hide header)", "default": "" }, "url": { "type": "text", "label": "Image URL", "default": "" }, "alt": { "type": "text", "label": "Alt Text / Caption", "default": "" } } }) --}}
@php
    $rawOptions = $moduleSettings
        ?? $module?->pivot?->book_config
        ?? $module?->pivot?->instance_options
        ?? $widget?->pivot?->instance_options
        ?? [];
    $options = is_string($rawOptions) ? (json_decode($rawOptions, true) ?: []) : (is_array($rawOptions) ? $rawOptions : []);

    $title = trim((string) ($options['title'] ?? ''));
    $url = trim((string) ($options['url'] ?? ''));
    $alt = trim((string) ($options['alt'] ?? ''));
@endphp

<x-tab :type="$title !== '' ? 'accordion' : 'panel'"
       :title="$title"
       :icon="$title !== '' ? 'bi-image' : null"
       :open="true">
    <div class="flex flex-col items-center justify-center gap-sm w-full">
        <x-preview type="image"
                   :url="$url !== '' ? $url : null"
                   :alt="$alt" />

        @if($url !== '' && $alt !== '')
            <span class="text-xs text-muted text-center font-italic block">{{ $alt }}</span>
        @endif
    </div>
</x-tab>