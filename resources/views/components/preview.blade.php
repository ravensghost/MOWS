{{-- resources/views/components/preview.blade.php --}}
@props([
    'type' => null,
    'label' => null,
    'baseLayer' => '#1a1a1aFF',
    'departmentColor' => '#ffffffFF',
    'url' => null,
    'alt' => '',
    'gallery' => null,
])

@php
    if ($type === null) {
        if ($url !== null) {
            $type = 'image';
        } elseif ($attributes->has('baseLayer') || func_num_args() > 0) {
            $type = 'insignia';
        } else {
            $type = 'badge';
        }
    }
    $fallbackAlt = $alt ?: lexicon('label_common_Image', 'Image');
@endphp

<div {{ $attributes->merge(['class' => 'bg-panel-sub border-dashed-panel border-rounded p-4 flex flex-col items-center justify-center gap-sm min-h-48 w-full']) }}>
    @if($label)
        <span class="text-xs font-bold text-muted uppercase text-wider">{{ $label }}</span>
    @endif

    @switch($type)
        @case('insignia')
        @case('insignia-preview')
            @if(!$label)
                <span class="text-xs font-bold text-muted uppercase text-wider">
                    {{ lexicon('label_ranks_LivePreview', 'Live Insignia Preview') }}
                </span>
            @endif
            <div class="flex items-center justify-center p-3 drop-shadow-badge w-full">
                <x-badge type="insignia"
                         :baseLayer="$baseLayer"
                         :departmentColour="$departmentColor"
                         :reactive="true" />
            </div>
            <div class="flex flex-col items-center gap-xs text-center">
                <span class="font-bold text-h text-sm" x-text="modals.insignia.insignia_title || '{{ addslashes(lexicon('label_ranks_PreviewTitlePlaceholder', 'Insignia Title')) }}'"></span>
                <span class="text-xs text-muted" x-text="modals.insignia.abbreviated_title ? '(' + modals.insignia.abbreviated_title + ')' : ''"></span>
            </div>
            @break

        @case('distinction')
            @if(!$label)
                <span class="text-xs font-bold text-muted uppercase text-wider">
                    {{ lexicon('label_awards_LivePreview', 'Live Distinction Preview') }}
                </span>
            @endif
            <div class="flex items-center justify-center p-3 drop-shadow-badge w-full">
                {{ $slot }}
            </div>
            @break

        @case('image')
            @if($url)
                <a href="{{ $url }}"
                   class="glightbox block w-full overflow-hidden border-rounded relative flex items-center justify-center p-3 drop-shadow-badge"
                   @if($gallery) data-gallery="{{ $gallery }}" @endif
                   data-glightbox="title: {{ $fallbackAlt }}"
                   aria-label="{{ $fallbackAlt }}"
                   title="{{ $fallbackAlt }}"
                   x-tooltip="'{{ addslashes($fallbackAlt) }}'">
                    <img src="{{ $url }}"
                         alt="{{ $fallbackAlt }}"
                         class="w-full h-auto object-cover border-rounded border-panel block">
                </a>
            @else
                <div class="flex items-center justify-center p-3 drop-shadow-badge text-center w-full">
                    <span class="text-muted font-italic text-sm">{{ lexicon('msg_common_ImageNotConfigured', 'No image URL has been configured.') }}</span>
                </div>
            @endif
            @break

        @case('portrait-canvas')
        @case('pov-scene')
        @case('schematic-msd')
        @case('relationship-web')
        @case('richtext')
        @case('badge')
        @default
            <div class="flex items-center justify-center p-3 drop-shadow-badge w-full">
                {{ $slot }}
            </div>
    @endswitch
</div>