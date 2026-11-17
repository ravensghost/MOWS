{{-- resources/views/components/colour-picker.blade.php --}}
@props([
    'name',
    'value' => '#1a1a1aFF',
    'label' => null,
    'badgeKey' => null,
    'model' => null,
    'xModel' => null,
    'tooltip' => null,
])

@php
    $resolvedModel = $model ?? $xModel;
    $hexTooltip = $tooltip ?? lexicon('tooltip_common_HexColorInput', 'Enter 8-digit hex colour code (#RRGGBBAA)');
@endphp

<div class="flex flex-col gap-xs w-full"
     x-data="colourPicker('{{ $value }}')"
     @if($resolvedModel)
         x-modelable="value"
         x-model="{{ $resolvedModel }}"
     @endif>

    @if($label)
        <div class="text-xs font-bold text-input-muted uppercase text-wider flex flex-row flex-wrap items-center justify-between gap-xs">
            <span>{{ $label }}</span>
            @if($badgeKey)
                <button type="button"
                        class="bg-transparent text-accent border-none hover-text-h cursor-pointer p-1 font-mono text-xs flex flex-row items-center gap-xs"
                        x-clipboard="'{{ addslashes($badgeKey) }}'"
                        aria-label="{{ lexicon('tooltip_common_CopyToken', 'Copy token name to clipboard') }}"
                        title="{{ lexicon('tooltip_common_CopyToken', 'Copy token name to clipboard') }}"
                        x-tooltip="'{{ addslashes(lexicon('tooltip_common_CopyToken', 'Copy token name to clipboard')) }}'">
                    <span>({{ $badgeKey }})</span>
                    <i class="bi bi-clipboard" aria-hidden="true"></i>
                </button>
            @endif
        </div>
    @endif

    <div class="flex flex-row items-center gap-sm bg-input border-input border-rounded p-2">
        <input type="color"
               :value="colorOnly"
               @input="updateColor($event)"
               class="w-12 h-input border-rounded-sm cursor-pointer border-input p-1 bg-transparent flex-shrink-0"
               x-tooltip="'{{ addslashes(lexicon('tooltip_common_SelectColor', 'Select Base Colour')) }}'"
               title="{{ lexicon('tooltip_common_SelectColor', 'Select Base Colour') }}"
               aria-label="{{ lexicon('tooltip_common_SelectColor', 'Select Base Colour') }}">

        <input type="text"
               name="{{ $name }}"
               x-model="value"
               class="w-full h-input p-input bg-input text-input border-input border-rounded text-sm font-mono flex-grow shadow-inner"
               x-tooltip="'{{ addslashes($hexTooltip) }}'"
               title="{{ $hexTooltip }}"
               aria-label="{{ $label ?? $hexTooltip }}"
               required>
    </div>

    <div class="flex flex-row flex-wrap items-center gap-sm text-xs text-muted px-1">
        <span class="font-mono">{{ lexicon('label_common_Opacity', 'Opacity:') }}</span>
        <input type="range"
               min="0"
               max="255"
               step="1"
               :value="alphaOnly"
               @input="updateAlpha($event)"
               class="w-full cursor-pointer"
               x-tooltip="'{{ addslashes(lexicon('tooltip_common_AdjustOpacity', 'Adjust colour opacity')) }}'"
               title="{{ lexicon('tooltip_common_AdjustOpacity', 'Adjust colour opacity') }}"
               aria-label="{{ lexicon('tooltip_common_AdjustOpacity', 'Adjust colour opacity') }}">
        <span class="font-mono w-12 text-right" x-text="Math.round((alphaOnly / 255) * 100) + '%'"></span>
    </div>
</div>