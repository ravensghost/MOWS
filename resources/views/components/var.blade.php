{{-- resources/views/components/var.blade.php --}}
@props([
    'varKey' => '',
    'varData' => [],
    'token' => null,
    'label' => null,
    'value' => null,
    'category' => null,
    'structuralOptions' => [],
])

@php
    $resolvedKey      = $varKey ?: ($token ?? '');
    $resolvedValue    = $value ?? ($varData['value'] ?? '');
    $resolvedLabel    = $label ?? ($varData['label'] ?? $resolvedKey);
    $isHex            = $varData['isHex'] ?? false;
    $isUsed           = $varData['isUsed'] ?? true;
    $shortUsageText   = $varData['shortUsageText'] ?? '';
    $fullUsageTooltip = $varData['fullUsageTooltip'] ?? '';
@endphp

<div class="bg-panel-sub border-panel border-rounded p-3 flex flex-col gap-xs w-full hover-border-accent transition-colors"
     x-data="thematicBindingCard(@js($resolvedValue), @js($resolvedKey))"
     x-show="typeof matchesVarSearch === 'function' ? matchesVarSearch('{{ addslashes($resolvedLabel) }}', '{{ addslashes($resolvedKey) }}', '{{ addslashes($shortUsageText) }}') : true">

    <div class="text-sm font-bold text-h flex flex-row justify-between items-center">
        <span>{{ $resolvedLabel }}</span>
        <button type="button"
                class="bg-transparent text-accent border-none hover-text-h cursor-pointer p-1 font-mono text-xs flex flex-row items-center gap-xs"
                x-clipboard="'{{ addslashes($resolvedKey) }}'"
                aria-label="{{ lexicon('tooltip_Themes_CopyVarKey', 'Copy CSS variable name') }}"
                title="{{ lexicon('tooltip_Themes_CopyVarKey', 'Copy CSS variable name') }}"
                x-tooltip="'{{ addslashes(lexicon('tooltip_Themes_CopyVarKey', 'Copy CSS variable name')) }}'">
            <span>({{ $resolvedKey }})</span>
            <i class="bi bi-clipboard" aria-hidden="true"></i>
        </button>
    </div>

    @if(array_key_exists($resolvedKey, $structuralOptions))
        <div class="w-full"
             x-tooltip="'{{ addslashes(lexicon('tooltip_Themes_SelectStructuralOption', 'Select preset structural dimension')) }}'">
            <select name="theme_vars[{{ $resolvedKey }}]"
                    x-model="val"
                    aria-label="{{ $resolvedLabel }}"
                    x-init="initStructuralTomSelect($el)"
                    class="w-full h-input p-input bg-input text-input border-input border-rounded text-sm transition-colors hover-border-accent"
                    required>
                @foreach($structuralOptions[$resolvedKey] as $optValue => $optLabel)
                    <option value="{{ $optValue }}" @selected($resolvedValue === $optValue)>{{ $optLabel }}</option>
                @endforeach
            </select>
        </div>
    @elseif($isHex)
        <div class="w-full">
            <x-colour-picker name="theme_vars[{{ $resolvedKey }}]"
                             :value="$resolvedValue"
                             :badgeKey="$resolvedKey"
                             xModel="val" />
        </div>
    @else
        <div class="flex flex-col gap-xs w-full">
            <template x-if="isFontStack">
                <div class="w-full"
                     x-tooltip="'{{ addslashes(lexicon('tooltip_Themes_SelectFonts', 'Add, remove, or reorder font family stack')) }}'">
                    <input type="hidden" name="theme_vars[{{ $resolvedKey }}]" :value="val">
                    <select multiple
                            aria-label="{{ $resolvedLabel }}"
                            x-init="initFontStackTomSelect($el)"
                            class="w-full font-mono text-sm">
                    </select>
                </div>
            </template>

            <template x-if="!isFontStack && isStructured">
                <div class="flex flex-col gap-xs w-full">
                    <input type="hidden" name="theme_vars[{{ $resolvedKey }}]" :value="val">

                    <template x-for="(part, index) in parts" :key="index">
                        <div class="w-full flex flex-col gap-xs">
                            <template x-if="isPureDimensions()">
                                <div class="flex flex-row items-center justify-between text-xs text-muted px-1">
                                    <span class="font-bold uppercase text-wider text-xs" x-text="getSideLabel(index)"></span>
                                    <template x-if="parts.length > 1">
                                        <button type="button"
                                                @click="removePart(index)"
                                                class="bg-transparent text-muted border-none hover-text-accent cursor-pointer p-1 text-xs flex flex-row items-center gap-xs"
                                                aria-label="Remove side">
                                            <i class="bi bi-x-circle" aria-hidden="true"></i>
                                            <span>{{ lexicon('btn_common_Remove', 'Remove') }}</span>
                                        </button>
                                    </template>
                                </div>
                            </template>

                            <template x-if="part.type === 'transitionProp'">
                                <div class="w-full"
                                     x-tooltip="'{{ addslashes(lexicon('tooltip_Themes_SelectTransitionProp', 'Select CSS transition property')) }}'">
                                    <select :aria-label="`{{ addslashes($resolvedLabel) }} ({{ lexicon('label_Themes_Property', 'Property') }})`"
                                            x-init="initTransitionPropTomSelect($el, index)"
                                            class="w-full font-mono text-sm">
                                    </select>
                                </div>
                            </template>

                            <template x-if="part.type === 'dimension'">
                                <div class="flex flex-row items-center gap-xs w-full">
                                    <div class="flex-grow">
                                        <input type="number"
                                               step="any"
                                               x-model="part.num"
                                               @input="updateValue()"
                                               :aria-label="`{{ addslashes($resolvedLabel) }} (${getSideLabel(index)})`"
                                               x-tooltip="'{{ addslashes(lexicon('tooltip_Themes_EnterNumericValue', 'Enter numeric value')) }}'"
                                               class="w-full h-input p-input bg-input text-input border-input border-rounded text-sm font-mono transition-colors hover-border-accent"
                                               required>
                                    </div>

                                    <div class="w-24"
                                         x-tooltip="'{{ addslashes(lexicon('tooltip_Themes_SelectUnit', 'Select CSS unit')) }}'">
                                        <select :aria-label="`{{ addslashes($resolvedLabel) }} (Unit)`"
                                                x-init="initUnitTomSelect($el, index)"
                                                class="w-full font-mono text-sm">
                                        </select>
                                    </div>
                                </div>
                            </template>

                            <template x-if="part.type === 'easing'">
                                <div class="w-full"
                                     x-tooltip="'{{ addslashes(lexicon('tooltip_Themes_SelectEasing', 'Select transition timing function')) }}'">
                                    <select :aria-label="`{{ addslashes($resolvedLabel) }} (Easing)`"
                                            x-init="initEasingTomSelect($el, index)"
                                            class="w-full font-mono text-sm">
                                    </select>
                                </div>
                            </template>
                        </div>
                    </template>

                    <template x-if="isPureDimensions() && parts.length < 4">
                        <div class="pt-2">
                            <x-button type="button"
                                      variant="ghost"
                                      size="sm"
                                      icon="bi-plus-circle"
                                      class="w-full"
                                      @click="addDimensionPart()">
                                {{ lexicon('btn_Themes_AddSide', 'Add Side') }}
                            </x-button>
                        </div>
                    </template>
                </div>
            </template>

            <template x-if="!isFontStack && !isStructured">
                <input type="text"
                       name="theme_vars[{{ $resolvedKey }}]"
                       x-model="val"
                       aria-label="{{ $resolvedLabel }}"
                       x-tooltip="'{{ addslashes(lexicon('tooltip_Themes_EnterVarValue', 'Enter CSS property value')) }}'"
                       class="w-full h-input p-input bg-input text-input border-input border-rounded text-sm font-mono transition-colors hover-border-accent"
                       required>
            </template>
        </div>
    @endif

    @if($shortUsageText)
        <div class="border-t-panel pt-2 mt-1"
             @if($fullUsageTooltip)
                 x-tooltip="'{{ addslashes($fullUsageTooltip) }}'"
                 title="{{ $fullUsageTooltip }}"
             @endif>
            @if($isUsed)
                <span class="flex flex-row items-center gap-xs text-xs font-mono text-muted cursor-pointer">
                    <i class="bi bi-code-slash" aria-hidden="true"></i>
                    <span>{{ $shortUsageText }}</span>
                </span>
            @else
                <span class="flex flex-row items-center gap-xs text-xs font-mono text-warning">
                    <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
                    <span>{{ $shortUsageText }}</span>
                </span>
            @endif
        </div>
    @endif
</div>