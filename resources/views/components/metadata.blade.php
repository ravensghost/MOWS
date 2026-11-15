{{-- resources/views/components/metadata.blade.php --}}
@props([
    'type' => null,
    'inputType' => null,
    'name' => null,
    'label' => null,
    'id' => null,
    'value' => '',
    'placeholder' => '',
    'required' => false,
    'autocomplete' => '',
    'prefix' => null,
    'tooltip' => null,
    'xModel' => null,
    'bare' => false,
    'detailedLabels' => false,
    'midShapes' => null,
    'topShapes' => null,
    'field' => null,
])

@php
    $polymorphicTypes = ['input', 'form-input', 'context', 'context-select', 'insignia', 'insignia-layer', 'dynamic', 'dynamic-metadata'];
    
    if (in_array($type, $polymorphicTypes, true)) {
        $resolvedType = $type;
        $resolvedInputType = $inputType ?? 'text';
    } else {
        $resolvedInputType = $inputType ?? ($type ?? 'text');
        if ($field !== null) {
            $resolvedType = 'dynamic';
        } elseif ($midShapes !== null || $topShapes !== null) {
            $resolvedType = 'insignia';
        } elseif ($name === 'display_target' || $bare || $detailedLabels) {
            $resolvedType = 'context';
        } else {
            $resolvedType = 'input';
        }
    }
@endphp

@switch($resolvedType)
    @case('context')
    @case('context-select')
        @php
            $resolvedName = $name ?? 'display_target';
            $selectId = $id ?? $resolvedName;
            $resolvedValue = $value !== '' ? $value : 'BOTH';
            $resolvedLabel = $label ?? lexicon('label_ServiceRecords_IcOoc', 'Display Target (IC/OOC)');
            $resolvedTooltip = $tooltip ?? lexicon('tooltip_ServiceRecords_SelectTargetScope', 'Select display target scope');
        @endphp

        @if(!$bare)
        <div class="flex flex-col gap-xs">
            <label for="{{ $selectId }}" class="text-xs font-bold text-input-muted">
                <span>{{ $resolvedLabel }}</span>
                @if($required)
                    <span class="text-required">*</span>
                @endif
            </label>
        @endif

            <select @if(!$bare) id="{{ $selectId }}" @endif
                    name="{{ $resolvedName }}"
                    aria-label="{{ $resolvedLabel }}"
                    x-tooltip="'{{ addslashes($resolvedTooltip) }}'"
                    @if($xModel) x-model="{{ $xModel }}" @endif
                    {{ $required ? 'required' : '' }}
                    {{ $attributes->merge(['class' => 'bg-input text-input border-input border-rounded rounded-input p-input outline-none h-input box-border focus-border-accent focus-shadow-accent' . ($bare ? '' : ' w-full')]) }}>
                @if($detailedLabels)
                    <option value="BOTH" @selected($resolvedValue === 'BOTH')>{{ lexicon('label_awards_OptBoth', 'BOTH (User & Character)') }}</option>
                    <option value="IC" @selected($resolvedValue === 'IC')>{{ lexicon('label_awards_OptIC', 'IC (Character Only)') }}</option>
                    <option value="OOC" @selected($resolvedValue === 'OOC')>{{ lexicon('label_awards_OptOOC', 'OOC (User Only)') }}</option>
                @else
                    <option value="BOTH" @selected($resolvedValue === 'BOTH')>{{ lexicon('label_ServiceRecords_OptBoth', 'BOTH') }}</option>
                    <option value="IC" @selected($resolvedValue === 'IC')>{{ lexicon('label_ServiceRecords_OptIcOnly', 'IC Only') }}</option>
                    <option value="OOC" @selected($resolvedValue === 'OOC')>{{ lexicon('label_ServiceRecords_OptOocOnly', 'OOC Only') }}</option>
                @endif
            </select>

        @if(!$bare)
        </div>
        @endif
        @break

    @case('insignia')
    @case('insignia-layer')
        <div class="grid grid-cols-1 md-grid-cols-2 gap-md w-full">
            <!-- Middle Layer Configuration -->
            <div class="bg-panel-sub border border-panel border-rounded rounded-panel p-4 flex flex-col gap-sm">
                <span class="text-sm font-bold text-h border-b border-panel pb-2">
                    {{ lexicon('label_ranks_MidLayerConfig', 'Middle Layer (Background / Trim)') }}
                </span>

                <div class="flex flex-col gap-xs">
                    <label class="text-xs font-bold text-input-muted">
                        {{ lexicon('label_ranks_MidLayerMode', 'Layer Mode') }}
                    </label>
                    <select name="mid_layer_mode" 
                            x-model="modals.insignia.mid_layer_mode" 
                            class="bg-input text-input border-input border-rounded rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent">
                        <option value="css">{{ lexicon('label_ranks_ModeCss', 'CSS Shape / Modifier') }}</option>
                        <option value="image">{{ lexicon('label_ranks_ModeImage', 'Image Asset') }}</option>
                        <option value="none">{{ lexicon('label_common_None', 'None') }}</option>
                    </select>
                </div>

                <div x-show="modals.insignia.mid_layer_mode === 'css'" class="flex flex-col gap-sm">
                    <div class="flex flex-col gap-xs">
                        <label class="text-xs font-bold text-input-muted">
                            {{ lexicon('label_ranks_MidLayerShape', 'CSS Shape Modifier') }}
                        </label>
                        <select name="mid_layer_modifier" 
                                x-model="modals.insignia.mid_layer_modifier" 
                                class="bg-input text-input border-input border-rounded rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent">
                            <option value="">{{ lexicon('label_common_SelectOption', 'Select Shape...') }}</option>
                            @foreach($midShapes ?? [] as $shapeKey => $shapeLabel)
                                <option value="{{ $shapeKey }}">{{ $shapeLabel }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex flex-col gap-xs">
                        <label for="mid_layer_hue" class="text-xs font-bold text-input-muted">
                            <span>{{ lexicon('label_ranks_MidLayerHue', 'Layer Hue (8-Digit Hex)') }}</span>
                        </label>
                        <input id="mid_layer_hue"
                               name="mid_layer_hue"
                               type="text"
                               x-model="modals.insignia.mid_layer_hue"
                               placeholder="#1a1a1aFF"
                               class="bg-input text-input border-input border-rounded rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent">
                    </div>
                </div>

                <div x-show="modals.insignia.mid_layer_mode === 'image'" class="flex flex-col gap-xs">
                    <label class="text-xs font-bold text-input-muted">
                        {{ lexicon('label_ranks_MidLayerAsset', 'Middle Layer Image') }}
                    </label>
                    <select name="mid_layer_asset" 
                            x-model="modals.insignia.mid_layer_asset" 
                            class="bg-input text-input border-input border-rounded rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent">
                        <option value="">{{ lexicon('label_common_SelectImage', 'Select Image...') }}</option>
                        <template x-for="img in modals.insignia.available_images" :key="'mid_' + img">
                            <option :value="img" x-text="img"></option>
                        </template>
                    </select>
                </div>
            </div>

            <!-- Top Layer Configuration -->
            <div class="bg-panel-sub border border-panel border-rounded rounded-panel p-4 flex flex-col gap-sm">
                <span class="text-sm font-bold text-h border-b border-panel pb-2">
                    {{ lexicon('label_ranks_TopLayerConfig', 'Top Layer (Pips / Bars / Insignia)') }}
                </span>

                <div class="flex flex-col gap-xs">
                    <label class="text-xs font-bold text-input-muted">
                        {{ lexicon('label_ranks_TopLayerMode', 'Layer Mode') }}
                    </label>
                    <select name="top_layer_mode" 
                            x-model="modals.insignia.top_layer_mode" 
                            class="bg-input text-input border-input border-rounded rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent">
                        <option value="image">{{ lexicon('label_ranks_ModeImage', 'Image Asset') }}</option>
                        <option value="css">{{ lexicon('label_ranks_ModeCss', 'CSS Shape / Modifier') }}</option>
                        <option value="none">{{ lexicon('label_common_None', 'None') }}</option>
                    </select>
                </div>

                <div x-show="modals.insignia.top_layer_mode === 'image'" class="flex flex-col gap-xs">
                    <label class="text-xs font-bold text-input-muted">
                        {{ lexicon('label_ranks_TopLayerAsset', 'Top Layer Image') }}
                    </label>
                    <select name="top_layer_asset" 
                            x-model="modals.insignia.top_layer_asset" 
                            class="bg-input text-input border-input border-rounded rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent">
                        <option value="">{{ lexicon('label_common_SelectImage', 'Select Image...') }}</option>
                        <template x-for="img in modals.insignia.available_images" :key="'top_' + img">
                            <option :value="img" x-text="img"></option>
                        </template>
                    </select>
                </div>

                <div x-show="modals.insignia.top_layer_mode === 'css'" class="flex flex-col gap-sm">
                    <div class="flex flex-col gap-xs">
                        <label class="text-xs font-bold text-input-muted">
                            {{ lexicon('label_ranks_TopLayerShape', 'CSS Shape Modifier') }}
                        </label>
                        <select name="top_layer_modifier" 
                                x-model="modals.insignia.top_layer_modifier" 
                                class="bg-input text-input border-input border-rounded rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent">
                            <option value="">{{ lexicon('label_common_SelectOption', 'Select Shape...') }}</option>
                            @foreach($topShapes ?? [] as $shapeKey => $shapeLabel)
                                <option value="{{ $shapeKey }}">{{ $shapeLabel }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex flex-col gap-xs">
                        <label for="top_layer_hue" class="text-xs font-bold text-input-muted">
                            <span>{{ lexicon('label_ranks_TopLayerHue', 'Layer Hue (8-Digit Hex)') }}</span>
                        </label>
                        <input id="top_layer_hue"
                               name="top_layer_hue"
                               type="text"
                               x-model="modals.insignia.top_layer_hue"
                               placeholder="#ffffffFF"
                               class="bg-input text-input border-input border-rounded rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent">
                    </div>
                </div>
            </div>
        </div>
        @break

    @case('dynamic')
    @case('dynamic-metadata')
        @php
            $fieldTermName = \Illuminate\Support\Str::studly($field->metadata_title);
            $dynamicLabel = lexicon('label_FormsHub_' . $fieldTermName, $field->metadata_title);
            $fieldInputKey = "field_{$field->id}";
        @endphp

        <div class="flex flex-col gap-xs w-full">
            @if($field->metadata_mode === 'text')
                <div class="flex flex-col gap-xs">
                    <label for="{{ $fieldInputKey }}" class="text-xs font-bold text-input-muted">
                        <span>{{ $dynamicLabel }}</span>
                        @if($field->is_required)
                            <span class="text-required">*</span>
                        @endif
                    </label>
                    <input id="{{ $fieldInputKey }}"
                           name="{{ $fieldInputKey }}"
                           type="text"
                           class="bg-input text-input border-input border-rounded rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent"
                           {{ $field->is_required ? 'required' : '' }}>
                </div>
                
            @elseif($field->metadata_mode === 'textarea')
                <label for="{{ $fieldInputKey }}" class="text-xs font-bold text-input-muted">
                    {{ $dynamicLabel }} @if($field->is_required)<span class="text-required">*</span>@endif
                </label>
                <textarea id="{{ $fieldInputKey }}"
                          name="{{ $fieldInputKey }}"
                          rows="3"
                          class="bg-input text-input border-input border-rounded rounded-input p-input outline-none w-full box-border focus-border-accent focus-shadow-accent"
                          x-init="window.autosize && window.autosize($el)"
                          {{ $field->is_required ? 'required' : '' }}></textarea>
                          
            @elseif($field->metadata_mode === 'richtext')
                <label class="text-xs font-bold text-input-muted">
                    {{ $dynamicLabel }} @if($field->is_required)<span class="text-required">*</span>@endif
                </label>
                <div class="bg-panel border-panel border-rounded rounded-panel flex flex-col overflow-hidden"
                     x-data="tiptapEditor(@js($value ?? ''))"
                     @tiptap-update="$refs.hiddenInput.value = $event.detail">
                    <x-tt-tb />
                    <div x-ref="editorContainer" class="tiptap-content p-3 flex-grow"></div>
                    <input type="hidden"
                           x-ref="hiddenInput"
                           name="{{ $fieldInputKey }}"
                           value="{{ $value ?? '' }}"
                           {{ $field->is_required ? 'required' : '' }}>
                </div>
                
            @elseif($field->metadata_mode === 'dropdown')
                <label for="{{ $fieldInputKey }}" class="text-xs font-bold text-input-muted">
                    {{ $dynamicLabel }} @if($field->is_required)<span class="text-required">*</span>@endif
                </label>
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
                        class="bg-input text-input border-input border-rounded rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent"
                        x-init="window.TomSelect && new window.TomSelect($el)"
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
                <label class="text-xs font-bold text-input-muted">
                    {{ $dynamicLabel }} @if($field->is_required)<span class="text-required">*</span>@endif
                </label>
                <div x-data="{ entries: [{ era: '', register_event_mode: '{{ $firstEventCategory }}', chronicle_text: '' }] }" class="flex flex-col gap-md">
                    <template x-for="(entry, idx) in entries" :key="idx">
                        <div class="grid grid-cols-1 lg-grid-cols-3 gap-md items-end bg-panel-sub border-panel border-rounded rounded-panel p-3">
                            <div class="flex flex-col gap-xs">
                                <label class="text-xs font-bold text-input-muted">{{ lexicon('label_ServiceRecords_EraDate', 'Era / Year') }}</label>
                                <input type="text"
                                       :name="'{{ $fieldInputKey }}[' + idx + '][era]'"
                                       x-model="entry.era"
                                       class="bg-input text-input border-input border-rounded rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent"
                                       placeholder="{{ lexicon('label_ServiceRecords_EraPlaceholder', 'e.g., 2380') }}">
                            </div>
                            <div class="flex flex-col gap-xs">
                                <label class="text-xs font-bold text-input-muted">{{ lexicon('label_ServiceRecords_EventType', 'Event Type') }}</label>
                                <select :name="'{{ $fieldInputKey }}[' + idx + '][register_event_mode]'"
                                        x-model="entry.register_event_mode"
                                        class="bg-input text-input border-input border-rounded rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent"
                                        aria-label="{{ lexicon('tooltip_ServiceRecords_SelectEventType', 'Select service record event category') }}"
                                        x-tooltip="'{{ addslashes(lexicon('tooltip_ServiceRecords_SelectEventType', 'Select service record event category')) }}'">
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
                                <label class="text-xs font-bold text-input-muted">{{ lexicon('label_common_Description', 'Description') }}</label>
                                <div class="flex flex-row flex-nowrap gap-sm items-center">
                                    <input type="text"
                                           :name="'{{ $fieldInputKey }}[' + idx + '][chronicle_text]'"
                                           x-model="entry.chronicle_text"
                                           class="bg-input text-input border-input border-rounded rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent flex-grow">
                                    <x-button type="button"
                                              variant="text" 
                                              class="text-danger hover-text-h p-0"
                                              x-show="entries.length > 1"
                                              @click="entries.splice(idx, 1)"
                                              x-tooltip="'{{ addslashes(lexicon('tooltip_ServiceRecords_RemoveTimelineEntry', 'Remove this timeline entry')) }}'"
                                              aria-label="{{ lexicon('tooltip_ServiceRecords_RemoveTimelineEntry', 'Remove this timeline entry') }}">
                                        🗑️
                                    </x-button>
                                </div>
                            </div>
                        </div>
                    </template>
                    <div>
                        <x-button type="button"
                                  variant="secondary"
                                  class="text-sm p-btn-sm"
                                  @click="entries.push({ era: '', register_event_mode: '{{ $firstEventCategory }}', chronicle_text: '' })"
                                  x-tooltip="'{{ addslashes(lexicon('tooltip_ServiceRecords_AddTimelineEntry', 'Add another service record timeline entry')) }}'">
                            + {{ lexicon('btn_ServiceRecords_AddTimelineEntry', 'Add Timeline Entry') }}
                        </x-button>
                    </div>
                </div>
            @endif
        </div>
        @break

    @default
        @php
            $inputId = $id ?? $name;
        @endphp

        <div class="flex flex-col gap-xs">
            @if($label)
                <label for="{{ $inputId }}" class="text-xs font-bold text-input-muted">
                    <span>{{ $label }}</span>
                    @if($required)
                        <span class="text-required">*</span>
                    @endif
                </label>
            @endif
            
            @if($prefix)
                <div class="flex flex-row flex-nowrap items-center bg-input border-input border-rounded rounded-input overflow-hidden focus-within-border-accent focus-within-shadow-accent"
                     @if($tooltip) x-tooltip="'{{ addslashes($tooltip) }}'" @endif>
                    <span class="px-2 text-sm text-muted bg-panel-header h-input flex items-center justify-center select-none">{{ $prefix }}</span>
                    <input id="{{ $inputId }}" name="{{ $name }}" type="{{ $resolvedInputType }}" 
                           {{ $attributes->merge(['class' => 'bg-transparent text-input border-none p-input outline-none h-input w-full box-border']) }}
                           value="{{ $value }}" placeholder="{{ $placeholder }}" {{ $required ? 'required' : '' }}
                           @if($xModel) x-model="{{ $xModel }}" @endif
                           @if($autocomplete) autocomplete="{{ $autocomplete }}" @endif>
                </div>
            @else
                <input id="{{ $inputId }}" name="{{ $name }}" type="{{ $resolvedInputType }}" 
                       {{ $attributes->merge(['class' => 'bg-input text-input border-input border-rounded rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent']) }}
                       value="{{ $value }}" placeholder="{{ $placeholder }}" {{ $required ? 'required' : '' }}
                       @if($xModel) x-model="{{ $xModel }}" @endif
                       @if($autocomplete) autocomplete="{{ $autocomplete }}" @endif
                       @if($tooltip) x-tooltip="'{{ addslashes($tooltip) }}'" @endif>
            @endif
        </div>
@endswitch