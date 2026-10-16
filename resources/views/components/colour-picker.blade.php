@props([
    'name',
    'value' => '#1a1a1a',
    'label' => null,
    'badgeKey' => null,
    'xModel' => null
])

<div class="flex flex-col gap-xs"
     x-data="colourPicker('{{ $value }}')"
     @if($xModel)
         x-modelable="value"
         x-model="{{ $xModel }}"
     @endif>

    @if($label)
        <div class="text-xs font-bold text-input-muted flex flex-row flex-wrap items-center justify-between gap-xs">
            <span>{{ $label }}</span>
            @if($badgeKey)
                <button type="button"
                        class="bg-transparent text-muted border-none hover-text-accent cursor-pointer p-0 font-mono text-xs opacity-75 flex items-center gap-xs"
                        x-clipboard="'{{ addslashes($badgeKey) }}'"
                        aria-label="{{ lexicon('tooltip_common_CopyToken', 'Copy token name to clipboard') }}"
                        x-tooltip="'{{ addslashes(lexicon('tooltip_common_CopyToken', 'Copy token name to clipboard')) }}'">
                    <span>({{ $badgeKey }})</span>
                    <i class="bi bi-clipboard" aria-hidden="true"></i>
                </button>
            @endif
        </div>
    @endif

    <div class="flex flex-col gap-xs">
        <div class="flex flex-row flex-wrap items-center gap-sm">
            <input type="color"
                   :value="colorOnly"
                   @input="updateColor($event)"
                   class="w-12 h-input rounded-input cursor-pointer border-input p-0 bg-transparent flex-shrink-0"
                   x-tooltip="'{{ addslashes(lexicon('tooltip_common_SelectColor', 'Select Base Color')) }}'"
                   aria-label="{{ lexicon('tooltip_common_SelectColor', 'Select Base Color') }}">

            <input type="text"
                   name="{{ $name }}"
                   x-model="value"
                   class="bg-input text-input border-input rounded-input p-input outline-none h-input box-border focus-border-accent focus-shadow-accent w-full flex-grow font-mono text-sm shadow-inner"
                   x-tooltip="'{{ addslashes(lexicon('tooltip_common_HexColorInput', 'Enter 6 or 8-digit hex colour code')) }}'"
                   aria-label="{{ $label ?? lexicon('tooltip_common_HexColorInput', 'Enter 6 or 8-digit hex colour code') }}"
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
                   class="w-full cursor-pointer accent-accent"
                   x-tooltip="'{{ addslashes(lexicon('tooltip_common_AdjustOpacity', 'Adjust colour opacity')) }}'"
                   aria-label="{{ lexicon('tooltip_common_AdjustOpacity', 'Adjust colour opacity') }}">
            <span class="font-mono w-8 text-right" x-text="Math.round((alphaOnly / 255) * 100) + '%'"></span>
        </div>
    </div>
</div>