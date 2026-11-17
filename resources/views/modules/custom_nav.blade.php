{{-- 
@WidgetManifest({
    "name": "Custom Navigation",
    "description": "Displays a custom list of links.",
    "icon": "bi-link-45deg",
    "fields": {
        "Navtitle": {
            "type": "text",
            "label": "Document Title (Leave blank to hide header)",
            "default": ""
        },
        "links": { "type": "textarea", "label": "Links (Format: Title | /url, Title 2 | /url2)", "default": "" }
    }
})
--}}
@php
    $options = is_string($widget->pivot->instance_options) ? json_decode($widget->pivot->instance_options, true) : ($widget->pivot->instance_options ?? []);
    $Navtitle = trim($options['Navtitle'] ?? '');
    $rawConfig = $options['links'] ?? '';
    
    // Split the string by comma to isolate individual link pairs
    $linkItems = array_filter(array_map('trim', explode(',', $rawConfig)));
@endphp

<div class="bg-panel border-panel rounded-panel shadow-panel flex flex-col overflow-hidden w-full mb-6 widget-custom-nav" x-data>
    @if($Navtitle)
        <div class="bg-panel-header text-h border-b-panel p-panel-header font-bold flex flex-row flex-wrap justify-between items-center gap-md">
            <span class="text-xl font-bold text-accent">
                {{ $Navtitle }}
            </span>
            <i class="bi bi-link-45deg text-accent"
               x-tooltip="'{{ addslashes(lexicon('tooltip_CustomNav_HeaderIcon', 'Custom navigation links')) }}'"
               aria-label="{{ lexicon('tooltip_CustomNav_HeaderIcon', 'Custom navigation links') }}"></i>
        </div>
    @endif

    <div class="flex flex-col">
        @forelse($linkItems as $item)
            @php
                // Split the individual item by the pipe character to separate Title and URL
                $parts = explode('|', $item);
                $title = trim($parts[0] ?? lexicon('label_CustomNav_UnnamedLink', 'Unnamed Link'));
                $url = trim($parts[1] ?? '/');
            @endphp
            <a href="{{ $url }}" 
               class="p-4 border-b-panel text-p hover-text-accent transition-colors flex flex-row flex-wrap items-center justify-between no-underline"
               x-tooltip="'{{ addslashes($title) }}'"
               aria-label="{{ $title }}">
                <span class="font-bold text-sm">{{ $title }}</span>
                <i class="bi bi-arrow-right text-muted transition-colors" aria-hidden="true"></i>
            </a>
        @empty
            <div class="text-muted italic p-4 text-sm text-center">
                <span>{{ lexicon('msg_common_NoNavigationLinks', 'No navigation links configured.') }}</span>
            </div>
        @endforelse
    </div>
</div>