@props(['field', 'value' => null])

@php
    $fieldTermName = \Illuminate\Support\Str::studly($field->metadata_title);
    $label = lexicon('label_FormsHub_' . $fieldTermName, $field->metadata_title);
    $fieldInputKey = "field_{$field->id}";
@endphp

<div class="flex flex-col gap-xs w-full">
    @if($field->metadata_mode === 'text')
        <x-form-input 
            :name="$fieldInputKey"
            :label="$label"
            :required="$field->is_required"
        />
        
    @elseif($field->metadata_mode === 'textarea')
        <label for="{{ $fieldInputKey }}" class="text-xs font-bold text-input-muted">
            {{ $label }} @if($field->is_required)<span class="text-required">*</span>@endif
        </label>
        <textarea id="{{ $fieldInputKey }}"
                  name="{{ $fieldInputKey }}"
                  rows="3"
                  class="bg-input text-input border-input rounded-input p-input outline-none w-full box-border focus-border-accent focus-shadow-accent"
                  x-init="window.autosize && window.autosize($el)"
                  {{ $field->is_required ? 'required' : '' }}></textarea>
                  
    @elseif($field->metadata_mode === 'richtext')
        <label class="text-xs font-bold text-input-muted">
            {{ $label }} @if($field->is_required)<span class="text-required">*</span>@endif
        </label>
        <div class="bg-panel border-panel rounded-panel flex flex-col overflow-hidden"
             x-data="tiptapEditor(@js($value ?? ''))"
             @tiptap-update="$refs.hiddenInput.value = $event.detail">
            <x-tiptap-toolbar />
            <div x-ref="editorContainer" class="tiptap-content p-3 flex-grow"></div>
            <input type="hidden"
                   x-ref="hiddenInput"
                   name="{{ $fieldInputKey }}"
                   value="{{ $value ?? '' }}"
                   {{ $field->is_required ? 'required' : '' }}>
        </div>
        
    @elseif($field->metadata_mode === 'dropdown')
        <label for="{{ $fieldInputKey }}" class="text-xs font-bold text-input-muted">
            {{ $label }} @if($field->is_required)<span class="text-required">*</span>@endif
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
                class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent"
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
            {{ $label }} @if($field->is_required)<span class="text-required">*</span>@endif
        </label>
        <div x-data="{ entries: [{ era: '', register_event_mode: '{{ $firstEventCategory }}', chronicle_text: '' }] }" class="flex flex-col gap-md">
            <template x-for="(entry, idx) in entries" :key="idx">
                <div class="grid grid-cols-1 lg-grid-cols-3 gap-md items-end bg-panel-sub border-panel rounded-panel p-3">
                    <div class="flex flex-col gap-xs">
                        <label class="text-xs font-bold text-input-muted">{{ lexicon('label_ServiceRecords_EraDate', 'Era / Year') }}</label>
                        <input type="text"
                               :name="'{{ $fieldInputKey }}[' + idx + '][era]'"
                               x-model="entry.era"
                               class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent"
                               placeholder="{{ lexicon('label_ServiceRecords_EraPlaceholder', 'e.g., 2380') }}">
                    </div>
                    <div class="flex flex-col gap-xs">
                        <label class="text-xs font-bold text-input-muted">{{ lexicon('label_ServiceRecords_EventType', 'Event Type') }}</label>
                        <select :name="'{{ $fieldInputKey }}[' + idx + '][register_event_mode]'"
                                x-model="entry.register_event_mode"
                                class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent"
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
                                   class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent flex-grow">
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