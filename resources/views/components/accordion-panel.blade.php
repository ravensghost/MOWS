@props([
    'title',
    'expanded' => false,
    'headerClass' => '',
])

<div {{ $attributes->except('headerClass')->merge(['class' => 'bg-panel border-panel rounded-panel shadow-panel flex flex-col overflow-hidden']) }} x-data="{ expanded: @js($expanded) }">
    <div class="bg-panel-header text-h border-b-panel p-panel-header font-bold flex flex-row flex-wrap gap-md justify-between items-center cursor-pointer select-none {{ $headerClass }}"
         @click="expanded = !expanded"
         x-tooltip="'{{ addslashes(lexicon('tooltip_common_TogglePanel', 'Click to expand or collapse panel')) }}'">
        <div class="flex flex-row flex-wrap items-center gap-sm">
            <span class="text-xs text-muted" x-text="expanded ? '▼' : '▶'"></span>
            <span class="font-bold text-h">{{ $title }}</span>
        </div>
        @if(isset($top))
            <div class="flex flex-row flex-wrap items-center gap-sm" @click.stop>
                {{ $top }}
            </div>
        @endif
    </div>
    <div x-show="expanded" x-collapse x-cloak class="p-panel flex flex-col gap-md flex-grow">
        {{ $slot }}
    </div>
</div>