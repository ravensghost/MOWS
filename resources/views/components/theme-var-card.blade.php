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
     x-data="themeVarCard(@js($value), @js($varKey))"
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
                        x-init="initStructuralTomSelect($el)"
                        class="w-full"
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

            {{-- 1. Font Stack Multi-Select UI --}}
            <template x-if="isFontStack">
                <div class="w-full"
                     x-tooltip="'{{ addslashes(lexicon('tooltip_Themes_SelectFonts', 'Add, remove, or reorder font family stack')) }}'">
                    <input type="hidden" name="theme_vars[{{ $varKey }}]" :value="val">
                    <select multiple
                            aria-label="{{ $label }}"
                            x-init="initFontStackTomSelect($el)"
                            class="w-full font-mono text-sm">
                    </select>
                </div>
            </template>

            {{-- 2. Split Number + Unit / Transition Property / Easing UI --}}
            <template x-if="!isFontStack && isStructured">
                <div class="flex flex-col gap-xs w-full">
                    <input type="hidden" name="theme_vars[{{ $varKey }}]" :value="val">

                    <template x-for="(part, index) in parts" :key="index">
                        <div class="w-full flex flex-col gap-2xs">
                            {{-- Directional/Side Label Header (Switches dynamically between All, Top/Bottom, and Top/Right/Bottom/Left) --}}
                            <template x-if="isPureDimensions()">
                                <div class="flex flex-row items-center justify-between text-xs text-muted px-0.5">
                                    <span class="font-bold uppercase tracking-wider text-[10px]" x-text="getSideLabel(index)"></span>
                                    <template x-if="parts.length > 1">
                                        <button type="button"
                                                @click="removePart(index)"
                                                class="bg-transparent text-muted border-none hover-text-accent cursor-pointer p-0 text-xs flex items-center gap-2xs"
                                                aria-label="Remove side">
                                            <i class="bi bi-x-circle" aria-hidden="true"></i>
                                            <span>Remove</span>
                                        </button>
                                    </template>
                                </div>
                            </template>

                            {{-- Transition Property Token --}}
                            <template x-if="part.type === 'transitionProp'">
                                <div class="w-full"
                                     x-tooltip="'{{ addslashes(lexicon('tooltip_Themes_SelectTransitionProp', 'Select CSS transition property')) }}'">
                                    <select :aria-label="`{{ addslashes($label) }} ({{ lexicon('label_Themes_Property', 'Property') }})`"
                                            x-init="initTransitionPropTomSelect($el, index)"
                                            class="w-full font-mono text-sm">
                                    </select>
                                </div>
                            </template>

                            {{-- Dimension Token: Number Input + Unit TomSelect --}}
                            <template x-if="part.type === 'dimension'">
                                <div class="flex flex-row items-center gap-xs w-full">
                                    <div class="flex-grow">
                                        <input type="number"
                                               step="any"
                                               x-model="part.num"
                                               @input="updateValue()"
                                               :aria-label="`{{ addslashes($label) }} (${getSideLabel(index)})`"
                                               x-tooltip="'{{ addslashes(lexicon('tooltip_Themes_EnterNumericValue', 'Enter numeric value')) }}'"
                                               class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent font-mono text-sm"
                                               required>
                                    </div>

                                    <div class="w-1-3"
                                         x-tooltip="'{{ addslashes(lexicon('tooltip_Themes_SelectUnit', 'Select CSS unit')) }}'">
                                        <select :aria-label="`{{ addslashes($label) }} (Unit)`"
                                                x-init="initUnitTomSelect($el, index)"
                                                class="w-full font-mono text-sm">
                                        </select>
                                    </div>
                                </div>
                            </template>

                            {{-- Easing Token --}}
                            <template x-if="part.type === 'easing'">
                                <div class="w-full"
                                     x-tooltip="'{{ addslashes(lexicon('tooltip_Themes_SelectEasing', 'Select transition timing function')) }}'">
                                    <select :aria-label="`{{ addslashes($label) }} (Easing)`"
                                            x-init="initEasingTomSelect($el, index)"
                                            class="w-full font-mono text-sm">
                                    </select>
                                </div>
                            </template>
                        </div>
                    </template>

                    {{-- Add Side Button --}}
                    <template x-if="isPureDimensions() && parts.length < 4">
                        <div class="pt-1">
                            <button type="button"
                                    @click="addDimensionPart()"
                                    class="bg-transparent text-accent border border-dashed border-accent/50 rounded-input hover-bg-panel hover-border-accent cursor-pointer w-full py-1.5 px-3 text-xs font-bold flex items-center justify-center gap-xs transition-colors"
                                    aria-label="Add side">
                                <i class="bi bi-plus-circle" aria-hidden="true"></i>
                                <span>{{ lexicon('btn_Themes_AddSide', 'Add Side') }}</span>
                            </button>
                        </div>
                    </template>
                </div>
            </template>

            {{-- 3. Fallback standard text input --}}
            <template x-if="!isFontStack && !isStructured">
                <input type="text"
                       name="theme_vars[{{ $varKey }}]"
                       x-model="val"
                       aria-label="{{ $label }}"
                       x-tooltip="'{{ addslashes(lexicon('tooltip_Themes_EnterVarValue', 'Enter CSS property value')) }}'"
                       class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent font-mono text-sm"
                       required>
            </template>
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