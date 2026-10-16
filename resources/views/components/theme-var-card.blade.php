@props([
    'varKey',
    'varData',
    'structuralOptions' => [],
])

@php
    $value            = $varData['value'] ?? '';
    $label            = $varData['label'] ?? $varKey;
    $isHex            = $varData['isHex'] ?? false;
    $isUsed           = $varData['isUsed'] ?? false;
    $shortUsageText   = $varData['shortUsageText'] ?? '';
    $fullUsageTooltip = $varData['fullUsageTooltip'] ?? '';
@endphp

<div class="flex flex-col gap-xs bg-panel-sub border border-panel rounded-panel p-3"
     x-data="{ val: @js($value) }"
     x-show="matchesVarSearch('{{ addslashes($label) }}', '{{ addslashes($varKey) }}', '{{ addslashes($shortUsageText) }}')">

    @if(array_key_exists($varKey, $structuralOptions))
        <div class="flex flex-col gap-xs w-full">
            <div class="text-xs font-bold text-input-muted flex flex-row flex-wrap items-center justify-between gap-xs">
                <span>{{ $label }}</span>
                <button type="button"
                        class="bg-transparent text-muted border-none hover-text-accent cursor-pointer p-0 font-mono text-xs opacity-75 flex items-center gap-xs"
                        x-clipboard="'{{ addslashes($varKey) }}'"
                        aria-label="{{ lexicon('tooltip_Themes_CopyVarKey', 'Copy CSS variable name') }}"
                        x-tooltip="'{{ addslashes(lexicon('tooltip_Themes_CopyVarKey', 'Copy CSS variable name')) }}'">
                    <span>({{ $varKey }})</span>
                    <i class="bi bi-clipboard" aria-hidden="true"></i>
                </button>
            </div>
            <div class="w-full"
                 x-tooltip="'{{ addslashes(lexicon('tooltip_Themes_SelectStructuralOption', 'Select preset structural dimension')) }}'">
                <select name="theme_vars[{{ $varKey }}]"
                        x-model="val"
                        aria-label="{{ $label }}"
                        x-init="
                            let ts = new window.TomSelect($el, {
                                create: true,
                                controlInput: null,
                                onChange: (v) => { val = v; }
                            });
                            $watch('val', (v) => {
                                if (ts.getValue() !== v) ts.setValue(v, true);
                            });
                            $cleanup(() => ts.destroy());
                        "
                        class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent"
                        required>
                    @foreach($structuralOptions[$varKey] as $optValue => $optLabel)
                        <option value="{{ $optValue }}" @selected($value === $optValue)>{{ $optLabel }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    @elseif($isHex)
        <div class="w-full">
            <x-colour-picker name="theme_vars[{{ $varKey }}]"
                             :value="$value"
                             :label="$label"
                             :badgeKey="$varKey"
                             xModel="val" />
        </div>
    @else
        <div class="flex flex-col gap-xs w-full">
            <div class="text-xs font-bold text-input-muted flex flex-row flex-wrap items-center justify-between gap-xs">
                <span>{{ $label }}</span>
                <button type="button"
                        class="bg-transparent text-muted border-none hover-text-accent cursor-pointer p-0 font-mono text-xs opacity-75 flex items-center gap-xs"
                        x-clipboard="'{{ addslashes($varKey) }}'"
                        aria-label="{{ lexicon('tooltip_Themes_CopyVarKey', 'Copy CSS variable name') }}"
                        x-tooltip="'{{ addslashes(lexicon('tooltip_Themes_CopyVarKey', 'Copy CSS variable name')) }}'">
                    <span>({{ $varKey }})</span>
                    <i class="bi bi-clipboard" aria-hidden="true"></i>
                </button>
            </div>
            <input type="text"
                   name="theme_vars[{{ $varKey }}]"
                   x-model="val"
                   aria-label="{{ $label }}"
                   x-tooltip="'{{ addslashes(lexicon('tooltip_Themes_EnterVarValue', 'Enter CSS property value')) }}'"
                   class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent font-mono text-sm"
                   required>
        </div>
    @endif

    {{-- Live CSS Usage & Selector Readout --}}
    <div class="border-t pt-1 mt-1"
         x-tooltip="'{{ addslashes($fullUsageTooltip) }}'">
        @if($isUsed)
            <span class="block text-xs font-mono text-muted truncate cursor-pointer">
                <i class="bi bi-code-slash mr-1" aria-hidden="true"></i>{{ $shortUsageText }}
            </span>
        @else
            <span class="block text-xs font-mono text-warning">
                <i class="bi bi-exclamation-triangle mr-1" aria-hidden="true"></i>{{ $shortUsageText }}
            </span>
        @endif
    </div>
</div>