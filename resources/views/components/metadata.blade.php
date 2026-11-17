{{-- resources/views/components/metadata.blade.php --}}
@props([
    'type' => null,
    'inputType' => null,
    'name' => '',
    'label' => '',
    'id' => null,
    'value' => '',
    'placeholder' => '',
    'required' => false,
    'autocomplete' => '',
    'prefix' => null,
    'tooltip' => '',
    'xModel' => null,
    'bare' => false,
    'detailedLabels' => false,
    'modalKey' => 'insignia',
    'midShapes' => null,
    'topShapes' => null,
    'field' => null,
    'inUniverseDate' => null,
    'mappedRealDate' => null,
])

@php
    if ($field !== null) {
        $type = 'dynamic';
    } elseif ($midShapes !== null || $topShapes !== null || in_array($type, ['layer', 'insignia', 'insignia-layer'], true)) {
        $type = 'layer';
    } elseif ($type === 'context' || $type === 'context-select' || $name === 'display_target' || $bare || $detailedLabels) {
        $type = 'context-select';
    } else {
        $type = $type ?? ($inputType ?? 'text');
        if (in_array($type, ['input', 'form-input'], true)) {
            $type = $inputType ?? 'text';
        }
    }

    $inputId = $id ?? ($name ?: null);
    $resolvedLabel = $label ?: ($type === 'context-select' && !$bare ? lexicon('label_ServiceRecords_IcOoc', 'Display Target (IC/OOC)') : '');
    $resolvedTooltip = $tooltip ?: ($type === 'context-select' ? lexicon('tooltip_ServiceRecords_SelectTargetScope', 'Select display target scope') : '');
@endphp

<div class="{{ $type === 'checkbox' ? 'flex flex-row items-center gap-sm' : ($bare ? 'inline-block' : 'flex flex-col gap-xs w-full') }}">
    @if($resolvedLabel && !in_array($type, ['checkbox', 'hidden', 'layer', 'dynamic'], true) && !$bare)
        <label @if($inputId) for="{{ $inputId }}" @endif class="text-xs font-bold text-input-muted uppercase text-wider flex flex-row items-center gap-xs">
            <span>{{ $resolvedLabel }}</span>
            @if($required)
                <span class="text-required">*</span>
            @endif
        </label>
    @endif

    @switch($type)
        @case('select')
            <select @if($inputId) id="{{ $inputId }}" @endif
                    name="{{ $name }}"
                    @if($xModel) x-model="{{ $xModel }}" @endif
                    @if($required) required @endif
                    @if($resolvedTooltip)
                        x-tooltip="'{{ addslashes($resolvedTooltip) }}'"
                        title="{{ $resolvedTooltip }}"
                        aria-label="{{ $resolvedTooltip }}"
                    @endif
                    {{ $attributes->merge(['class' => 'w-full h-input p-input bg-input text-input border-input border-rounded text-sm transition-colors hover-border-accent']) }}>
                {{ $slot }}
            </select>
            @break

        @case('context-select')
            @php
                $contextName = $name ?: 'display_target';
                $contextValue = $value !== '' ? $value : 'BOTH';
            @endphp
            <select @if($inputId && !$bare) id="{{ $inputId }}" @endif
                    name="{{ $contextName }}"
                    @if($xModel) x-model="{{ $xModel }}" @endif
                    @if($required) required @endif
                    @if($resolvedTooltip)
                        x-tooltip="'{{ addslashes($resolvedTooltip) }}'"
                        title="{{ $resolvedTooltip }}"
                        aria-label="{{ $resolvedTooltip }}"
                    @endif
                    {{ $attributes->merge(['class' => 'h-input p-input bg-input text-input border-input border-rounded text-sm transition-colors hover-border-accent' . ($bare ? '' : ' w-full')]) }}>
                @if($slot->isNotEmpty())
                    {{ $slot }}
                @elseif($detailedLabels)
                    <option value="BOTH" @selected($contextValue === 'BOTH')>{{ lexicon('label_awards_OptBoth', 'BOTH (User & Character)') }}</option>
                    <option value="IC" @selected($contextValue === 'IC')>{{ lexicon('label_awards_OptIC', 'IC (Character Only)') }}</option>
                    <option value="OOC" @selected($contextValue === 'OOC')>{{ lexicon('label_awards_OptOOC', 'OOC (User Only)') }}</option>
                @else
                    <option value="BOTH" @selected($contextValue === 'BOTH')>{{ lexicon('label_ServiceRecords_OptBoth', 'BOTH') }}</option>
                    <option value="IC" @selected($contextValue === 'IC')>{{ lexicon('label_ServiceRecords_OptIcOnly', 'IC Only') }}</option>
                    <option value="OOC" @selected($contextValue === 'OOC')>{{ lexicon('label_ServiceRecords_OptOocOnly', 'OOC Only') }}</option>
                @endif
            </select>
            @break

        @case('textarea')
            <textarea @if($inputId) id="{{ $inputId }}" @endif
                      name="{{ $name }}"
                      placeholder="{{ $placeholder }}"
                      @if($xModel) x-model="{{ $xModel }}" @endif
                      @if($required) required @endif
                      @if($resolvedTooltip)
                          x-tooltip="'{{ addslashes($resolvedTooltip) }}'"
                          title="{{ $resolvedTooltip }}"
                          aria-label="{{ $resolvedTooltip }}"
                      @endif
                      {{ $attributes->merge(['class' => 'w-full p-input bg-input text-input border-input border-rounded text-sm transition-colors hover-border-accent min-h-48']) }}>{{ $slot->isNotEmpty() ? $slot : $value }}</textarea>
            @break

        @case('checkbox')
            <input @if($inputId) id="{{ $inputId }}" @endif
                   type="checkbox"
                   name="{{ $name }}"
                   value="{{ $value !== '' ? $value : '1' }}"
                   @if($xModel) x-model="{{ $xModel }}" @endif
                   @if($required) required @endif
                   @if($resolvedTooltip)
                       x-tooltip="'{{ addslashes($resolvedTooltip) }}'"
                       title="{{ $resolvedTooltip }}"
                       aria-label="{{ $resolvedTooltip }}"
                   @endif
                   {{ $attributes->merge(['class' => 'w-4 h-4 m-0 bg-input border-input border-rounded-sm grid items-center justify-center checkbox-engine cursor-pointer']) }}>
            @if($resolvedLabel)
                <label @if($inputId) for="{{ $inputId }}" @endif class="text-sm text-p cursor-pointer">{{ $resolvedLabel }}</label>
            @endif
            @break

        @case('file')
            <input @if($inputId) id="{{ $inputId }}" @endif
                   type="file"
                   name="{{ $name }}"
                   @if($required) required @endif
                   @if($resolvedTooltip)
                       x-tooltip="'{{ addslashes($resolvedTooltip) }}'"
                       title="{{ $resolvedTooltip }}"
                       aria-label="{{ $resolvedTooltip }}"
                   @endif
                   {{ $attributes->merge(['class' => 'w-full p-input bg-input text-input-file border-input border-rounded text-sm file-input-engine']) }}>
            @break

        @case('chronological')
            <div class="flex flex-col gap-xs w-full">
                <input @if($inputId) id="{{ $inputId }}" @endif
                       type="text"
                       name="{{ $name }}"
                       value="{{ $value }}"
                       placeholder="{{ $placeholder }}"
                       data-flatpickr
                       @if($xModel) x-model="{{ $xModel }}" @endif
                       @if($required) required @endif
                       @if($resolvedTooltip)
                           x-tooltip="'{{ addslashes($resolvedTooltip) }}'"
                           title="{{ $resolvedTooltip }}"
                           aria-label="{{ $resolvedTooltip }}"
                       @endif
                       {{ $attributes->merge(['class' => 'w-full h-input p-input bg-input text-input border-input border-rounded text-sm transition-colors hover-border-accent']) }}>
                @if($inUniverseDate || $mappedRealDate)
                    <div class="flex flex-row flex-wrap items-center justify-between gap-xs text-xs text-muted">
                        @if($inUniverseDate)
                            <span>{{ $inUniverseDate }}</span>
                        @endif
                        @if($mappedRealDate)
                            <span>{{ $mappedRealDate }}</span>
                        @endif
                    </div>
                @endif
            </div>
            @break

        @case('layer')
            @php
                $resolvedMidShapes = $midShapes ?? [];
                $resolvedTopShapes = $topShapes ?? [];
            @endphp
            <div class="grid grid-cols-1 md-grid-cols-2 gap-md w-full">
                <!-- Middle Layer Configuration -->
                <div class="bg-panel-sub border-panel border-rounded p-4 flex flex-col gap-sm">
                    <span class="text-sm font-bold text-h border-b-panel pb-2">
                        {{ lexicon('label_ranks_MidLayerConfig', 'Middle Layer (Background / Trim)') }}
                    </span>

                    <div class="flex flex-col gap-xs">
                        <label class="text-xs font-bold text-input-muted uppercase text-wider">
                            {{ lexicon('label_ranks_MidLayerMode', 'Layer Mode') }}
                        </label>
                        <select name="mid_layer_mode"
                                x-model="modals.{{ $modalKey }}.mid_layer_mode"
                                class="w-full h-input p-input bg-input text-input border-input border-rounded text-sm transition-colors hover-border-accent">
                            <option value="css">{{ lexicon('label_ranks_ModeCss', 'CSS Shape / Modifier') }}</option>
                            <option value="image">{{ lexicon('label_ranks_ModeImage', 'Image Asset') }}</option>
                            <option value="none">{{ lexicon('label_common_None', 'None') }}</option>
                        </select>
                    </div>

                    <div x-show="modals.{{ $modalKey }}.mid_layer_mode === 'css'" class="flex flex-col gap-sm">
                        <div class="flex flex-col gap-xs">
                            <label class="text-xs font-bold text-input-muted uppercase text-wider">
                                {{ lexicon('label_ranks_MidLayerShape', 'CSS Shape Modifier') }}
                            </label>
                            <select name="mid_layer_modifier"
                                    x-model="modals.{{ $modalKey }}.mid_layer_modifier"
                                    class="w-full h-input p-input bg-input text-input border-input border-rounded text-sm transition-colors hover-border-accent">
                                <option value="">{{ lexicon('label_common_SelectOption', 'Select Shape...') }}</option>
                                @foreach($resolvedMidShapes as $shapeKey => $shapeLabel)
                                    <option value="{{ $shapeKey }}">{{ $shapeLabel }}</option>
                                @endforeach
                            </select>
                        </div>

                        <x-colour-picker name="mid_layer_hue"
                                         :label="lexicon('label_ranks_MidLayerHue', 'Layer Hue (8-Digit Hex)')"
                                         :xModel="'modals.' . $modalKey . '.mid_layer_hue'" />
                    </div>

                    <div x-show="modals.{{ $modalKey }}.mid_layer_mode === 'image'" class="flex flex-col gap-xs">
                        <label class="text-xs font-bold text-input-muted uppercase text-wider">
                            {{ lexicon('label_ranks_MidLayerAsset', 'Middle Layer Image') }}
                        </label>
                        <select name="mid_layer_asset"
                                x-model="modals.{{ $modalKey }}.mid_layer_asset"
                                class="w-full h-input p-input bg-input text-input border-input border-rounded text-sm transition-colors hover-border-accent">
                            <option value="">{{ lexicon('label_common_SelectImage', 'Select Image...') }}</option>
                            <template x-for="img in modals.{{ $modalKey }}.available_images" :key="'mid_' + img">
                                <option :value="img" x-text="img"></option>
                            </template>
                        </select>
                    </div>
                </div>

                <!-- Top Layer Configuration -->
                <div class="bg-panel-sub border-panel border-rounded p-4 flex flex-col gap-sm">
                    <span class="text-sm font-bold text-h border-b-panel pb-2">
                        {{ lexicon('label_ranks_TopLayerConfig', 'Top Layer (Pips / Bars / Insignia)') }}
                    </span>

                    <div class="flex flex-col gap-xs">
                        <label class="text-xs font-bold text-input-muted uppercase text-wider">
                            {{ lexicon('label_ranks_TopLayerMode', 'Layer Mode') }}
                        </label>
                        <select name="top_layer_mode"
                                x-model="modals.{{ $modalKey }}.top_layer_mode"
                                class="w-full h-input p-input bg-input text-input border-input border-rounded text-sm transition-colors hover-border-accent">
                            <option value="image">{{ lexicon('label_ranks_ModeImage', 'Image Asset') }}</option>
                            <option value="css">{{ lexicon('label_ranks_ModeCss', 'CSS Shape / Modifier') }}</option>
                            <option value="none">{{ lexicon('label_common_None', 'None') }}</option>
                        </select>
                    </div>

                    <div x-show="modals.{{ $modalKey }}.top_layer_mode === 'image'" class="flex flex-col gap-xs">
                        <label class="text-xs font-bold text-input-muted uppercase text-wider">
                            {{ lexicon('label_ranks_TopLayerAsset', 'Top Layer Image') }}
                        </label>
                        <select name="top_layer_asset"
                                x-model="modals.{{ $modalKey }}.top_layer_asset"
                                class="w-full h-input p-input bg-input text-input border-input border-rounded text-sm transition-colors hover-border-accent">
                            <option value="">{{ lexicon('label_common_SelectImage', 'Select Image...') }}</option>
                            <template x-for="img in modals.{{ $modalKey }}.available_images" :key="'top_' + img">
                                <option :value="img" x-text="img"></option>
                            </template>
                        </select>
                    </div>

                    <div x-show="modals.{{ $modalKey }}.top_layer_mode === 'css'" class="flex flex-col gap-sm">
                        <div class="flex flex-col gap-xs">
                            <label class="text-xs font-bold text-input-muted uppercase text-wider">
                                {{ lexicon('label_ranks_TopLayerShape', 'CSS Shape Modifier') }}
                            </label>
                            <select name="top_layer_modifier"
                                    x-model="modals.{{ $modalKey }}.top_layer_modifier"
                                    class="w-full h-input p-input bg-input text-input border-input border-rounded text-sm transition-colors hover-border-accent">
                                <option value="">{{ lexicon('label_common_SelectOption', 'Select Shape...') }}</option>
                                @foreach($resolvedTopShapes as $shapeKey => $shapeLabel)
                                    <option value="{{ $shapeKey }}">{{ $shapeLabel }}</option>
                                @endforeach
                            </select>
                        </div>

                        <x-colour-picker name="top_layer_hue"
                                         :label="lexicon('label_ranks_TopLayerHue', 'Layer Hue (8-Digit Hex)')"
                                         :xModel="'modals.' . $modalKey . '.top_layer_hue'" />
                    </div>
                </div>
            </div>
            @break

        @case('dynamic')
            @php
                $fieldTermName = \Illuminate\Support\Str::studly($field->metadata_title);
                $dynamicLabel = lexicon('label_FormsHub_' . $fieldTermName, $field->metadata_title);
                $fieldInputKey = "field_{$field->id}";
            @endphp
            <div class="flex flex-col gap-xs w-full">
                <label for="{{ $fieldInputKey }}" class="text-xs font-bold text-input-muted uppercase text-wider flex flex-row items-center gap-xs">
                    <span>{{ $dynamicLabel }}</span>
                    @if($field->is_required)
                        <span class="text-required">*</span>
                    @endif
                </label>

                @if($field->metadata_mode === 'text')
                    <input id="{{ $fieldInputKey }}"
                           name="{{ $fieldInputKey }}"
                           type="text"
                           class="w-full h-input p-input bg-input text-input border-input border-rounded text-sm transition-colors hover-border-accent"
                           {{ $field->is_required ? 'required' : '' }}>
                @elseif($field->metadata_mode === 'textarea')
                    <textarea id="{{ $fieldInputKey }}"
                              name="{{ $fieldInputKey }}"
                              rows="3"
                              class="w-full p-input bg-input text-input border-input border-rounded text-sm transition-colors hover-border-accent min-h-48"
                              x-init="window.Autosize && window.Autosize.init($el)"
                              {{ $field->is_required ? 'required' : '' }}></textarea>
                @elseif($field->metadata_mode === 'richtext')
                    <div class="bg-panel-body border-panel border-rounded flex flex-col overflow-hidden"
                         x-data="tiptapHelper(@js($value ?? ''))"
                         @tiptap-update="$refs.hiddenInput.value = $event.detail">
                        <x-tt-tb :name="$fieldInputKey" />
                        <input type="hidden"
                               x-ref="hiddenInput"
                               name="{{ $fieldInputKey }}"
                               value="{{ $value ?? '' }}"
                               {{ $field->is_required ? 'required' : '' }}>
                    </div>
                @elseif($field->metadata_mode === 'dropdown')
                    @php
                        $rawPayloads = is_array($field->metadata_payloads) ? $field->metadata_payloads : json_decode($field->metadata_payloads ?? '[]', true);
                        if (!is_array($rawPayloads)) {
                            $rawPayloads = explode(',', trim((string) ($field->metadata_payloads ?? ''), '"'));
                        }
                        if (count($rawPayloads) === 1 && str_contains((string) $rawPayloads[0], ',')) {
                            $rawPayloads = explode(',', (string) $rawPayloads[0]);
                        }
                    @endphp
                    <select id="{{ $fieldInputKey }}"
                            name="{{ $fieldInputKey }}"
                            class="w-full h-input p-input bg-input text-input border-input border-rounded text-sm transition-colors hover-border-accent"
                            x-init="window.Select && window.Select.init($el)"
                            {{ $field->is_required ? 'required' : '' }}>
                        <option value="">{{ lexicon('label_common_SelectAnOption', '-- Select an Option --') }}</option>
                        @foreach($rawPayloads as $payloadItem)
                            @if(trim((string) $payloadItem) !== '')
                                <option value="{{ trim((string) $payloadItem) }}">{{ trim((string) $payloadItem) }}</option>
                            @endif
                        @endforeach
                    </select>
                @elseif($field->metadata_mode === 'service_record')
                    @php
                        $allGroups = \App\Models\ChronologicalRegister::eventTypeGroups();
                        $configuredGroups = is_array($field->metadata_payloads)
                            ? $field->metadata_payloads
                            : (json_decode((string) ($field->metadata_payloads ?? '[]'), true) ?: []);
                        if (is_string($configuredGroups)) {
                            $configuredGroups = json_decode($configuredGroups, true) ?: [];
                        }
                        $activeGroups = [];
                        if (is_array($configuredGroups)) {
                            foreach ($configuredGroups as $grpKey) {
                                if (is_string($grpKey) && isset($allGroups[$grpKey])) {
                                    $activeGroups[$grpKey] = $allGroups[$grpKey];
                                }
                            }
                        }
                        if (empty($activeGroups)) {
                            $activeGroups = $allGroups;
                        }
                        $firstEventCategory = 'background';
                        foreach ($activeGroups as $grpData) {
                            $categories = $grpData['types'] ?? [];
                            if (!empty($categories) && is_array($categories)) {
                                $firstEventCategory = (string) array_key_first($categories);
                                break;
                            }
                        }
                    @endphp
                    <div x-data="{ entries: [{ era: '', register_event_mode: '{{ $firstEventCategory }}', chronicle_text: '' }] }" class="flex flex-col gap-md">
                        <template x-for="(entry, idx) in entries" :key="idx">
                            <div class="grid grid-cols-1 lg-grid-cols-3 gap-md items-end bg-panel-sub border-panel border-rounded p-3">
                                <div class="flex flex-col gap-xs">
                                    <label class="text-xs font-bold text-input-muted uppercase text-wider">{{ lexicon('label_ServiceRecords_EraDate', 'Era / Year') }}</label>
                                    <input type="text"
                                           :name="'{{ $fieldInputKey }}[' + idx + '][era]'"
                                           x-model="entry.era"
                                           class="w-full h-input p-input bg-input text-input border-input border-rounded text-sm transition-colors hover-border-accent"
                                           placeholder="{{ lexicon('label_ServiceRecords_EraPlaceholder', 'e.g., 2380') }}">
                                </div>
                                <div class="flex flex-col gap-xs">
                                    <label class="text-xs font-bold text-input-muted uppercase text-wider">{{ lexicon('label_ServiceRecords_EventType', 'Event Type') }}</label>
                                    <select :name="'{{ $fieldInputKey }}[' + idx + '][register_event_mode]'"
                                            x-model="entry.register_event_mode"
                                            class="w-full h-input p-input bg-input text-input border-input border-rounded text-sm transition-colors hover-border-accent">
                                        @foreach($activeGroups as $grpKey => $groupData)
                                            @php
                                                $groupLabel = is_array($groupData) && isset($groupData['label'])
                                                    ? $groupData['label']
                                                    : ucfirst((string) $grpKey);
                                                $events = is_array($groupData) && isset($groupData['types'])
                                                    ? $groupData['types']
                                                    : (is_array($groupData) ? $groupData : []);
                                            @endphp
                                            <optgroup label="{{ $groupLabel }}">
                                                @foreach($events as $eventKey => $eventLabel)
                                                    @if(is_string($eventLabel))
                                                        <option value="{{ $eventKey }}">{{ $eventLabel }}</option>
                                                    @endif
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="flex flex-col gap-xs">
                                    <label class="text-xs font-bold text-input-muted uppercase text-wider">{{ lexicon('label_common_Description', 'Description') }}</label>
                                    <div class="flex flex-row flex-nowrap gap-sm items-center">
                                        <input type="text"
                                               :name="'{{ $fieldInputKey }}[' + idx + '][chronicle_text]'"
                                               x-model="entry.chronicle_text"
                                               class="w-full h-input p-input bg-input text-input border-input border-rounded text-sm transition-colors hover-border-accent flex-grow">
                                        <x-button type="button"
                                                  variant="danger"
                                                  size="sm"
                                                  icon="bi-trash"
                                                  x-show="entries.length > 1"
                                                  @click="entries.splice(idx, 1)"
                                                  :tooltip="lexicon('tooltip_ServiceRecords_RemoveTimelineEntry', 'Remove this timeline entry')" />
                                    </div>
                                </div>
                            </div>
                        </template>
                        <div>
                            <x-button type="button"
                                      variant="secondary"
                                      size="sm"
                                      icon="bi-plus-lg"
                                      @click="entries.push({ era: '', register_event_mode: '{{ $firstEventCategory }}', chronicle_text: '' })"
                                      :tooltip="lexicon('tooltip_ServiceRecords_AddTimelineEntry', 'Add another service record timeline entry')">
                                {{ lexicon('btn_ServiceRecords_AddTimelineEntry', 'Add Timeline Entry') }}
                            </x-button>
                        </div>
                    </div>
                @endif
            </div>
            @break

        @case('hidden')
            <input @if($inputId) id="{{ $inputId }}" @endif
                   type="hidden"
                   name="{{ $name }}"
                   value="{{ $value }}"
                   @if($xModel) x-model="{{ $xModel }}" @endif
                   {{ $attributes }}>
            @break

        @default
            @if($prefix)
                <div class="flex flex-row flex-nowrap items-center bg-input border-input border-rounded overflow-hidden transition-colors hover-border-accent"
                     @if($resolvedTooltip)
                         x-tooltip="'{{ addslashes($resolvedTooltip) }}'"
                         title="{{ $resolvedTooltip }}"
                         aria-label="{{ $resolvedTooltip }}"
                     @endif>
                    <span class="px-2 text-sm text-muted bg-panel-header h-input flex items-center justify-center select-none">{{ $prefix }}</span>
                    <input @if($inputId) id="{{ $inputId }}" @endif
                           name="{{ $name }}"
                           type="{{ $type }}"
                           value="{{ $value }}"
                           placeholder="{{ $placeholder }}"
                           @if($required) required @endif
                           @if($xModel) x-model="{{ $xModel }}" @endif
                           @if($autocomplete) autocomplete="{{ $autocomplete }}" @endif
                           {{ $attributes->merge(['class' => 'w-full h-input p-input bg-transparent text-input border-none text-sm']) }}>
                </div>
            @else
                <input @if($inputId) id="{{ $inputId }}" @endif
                       name="{{ $name }}"
                       type="{{ $type }}"
                       value="{{ $value }}"
                       placeholder="{{ $placeholder }}"
                       @if($required) required @endif
                       @if($xModel) x-model="{{ $xModel }}" @endif
                       @if($autocomplete) autocomplete="{{ $autocomplete }}" @endif
                       @if($resolvedTooltip)
                           x-tooltip="'{{ addslashes($resolvedTooltip) }}'"
                           title="{{ $resolvedTooltip }}"
                           aria-label="{{ $resolvedTooltip }}"
                       @endif
                       {{ $attributes->merge(['class' => 'w-full h-input p-input bg-input text-input border-input border-rounded text-sm transition-colors hover-border-accent']) }}>
            @endif
    @endswitch
</div>