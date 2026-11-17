{{-- resources/views/modules/custom_nav.blade.php --}}
{{-- @WidgetManifest({ "name": "Custom Navigation", "description": "Displays a custom list of links.", "icon": "bi-link-45deg", "fields": { "Navtitle": { "type": "text", "label": "Document Title (Leave blank to hide header)", "default": "" }, "links": { "type": "textarea", "label": "Links (Format: Title | /url, Title 2 | /url2)", "default": "" } } }) --}}
@php
    $rawOptions = $moduleSettings
        ?? $module?->pivot?->book_config
        ?? $module?->pivot?->instance_options
        ?? $widget?->pivot?->instance_options
        ?? [];
    $options = is_string($rawOptions) ? (json_decode($rawOptions, true) ?: []) : (is_array($rawOptions) ? $rawOptions : []);

    $navTitle = trim($options['Navtitle'] ?? $options['title'] ?? '');
    $rawConfig = (string) ($options['links'] ?? '');
    $linkItems = array_filter(array_map('trim', explode(',', $rawConfig)));
@endphp

<x-tab :type="$navTitle !== '' ? 'accordion' : 'panel'"
       :title="$navTitle"
       :icon="$navTitle !== '' ? 'bi-link-45deg' : null"
       :open="true">
    <div class="flex flex-col">
        @forelse($linkItems as $item)
            @php
                $parts = explode('|', $item, 2);
                $linkTitle = trim($parts[0] ?? '') ?: lexicon('label_CustomNav_UnnamedLink', 'Unnamed Link');
                $linkUrl = trim($parts[1] ?? '') ?: '/';
            @endphp

            <a href="{{ $linkUrl }}"
               class="p-3 border-b-table-row text-p hover-bg-panel-hover hover-text-accent transition-colors flex flex-row flex-wrap items-center justify-between no-underline"
               x-tooltip="'{{ addslashes($linkTitle) }}'"
               title="{{ $linkTitle }}"
               aria-label="{{ $linkTitle }}">
                <span class="font-bold text-sm">{{ $linkTitle }}</span>
                <i class="bi bi-arrow-right text-muted transition-colors" aria-hidden="true"></i>
            </a>
        @empty
            <div class="bg-panel-sub border-dashed-panel border-rounded p-panel-body text-center">
                <span class="text-sm text-muted font-italic">{{ lexicon('msg_common_NoNavigationLinks', 'No navigation links configured.') }}</span>
            </div>
        @endforelse
    </div>
</x-tab>