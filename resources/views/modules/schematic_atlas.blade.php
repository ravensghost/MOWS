{{-- resources/views/modules/schematic_atlas.blade.php --}}
{{-- @WidgetManifest({ "name": "Interactive MSD & Cutaway Schematic", "description": "Displays multi-layer cutaway schematics with clickable compartment pins, deck-level persona rollups, and alert telemetry.", "icon": "bi-Bounding-box-circles", "fields": { "title": { "type": "text", "label": "Module Title", "default": "Master Systems Display" }, "default_schematic": { "type": "text", "label": "Default Schematic Key (Optional)", "default": "main" } } }) --}}
@php
    $rawOptions = $moduleSettings
        ?? $module?->pivot?->book_config
        ?? $module?->pivot?->instance_options
        ?? $widget?->pivot?->instance_options
        ?? [];
    $options = is_string($rawOptions) ? (json_decode($rawOptions, true) ?: []) : (is_array($rawOptions) ? $rawOptions : []);

    $rawTitle = trim((string) ($options['title'] ?? ''));
    $title = $rawTitle !== '' ? $rawTitle : lexicon('label_Schematic_DefaultTitle', 'Master Systems Display');
    $defaultKey = trim((string) ($options['default_schematic'] ?? 'main')) ?: 'main';

    $schematicsLedger = \Illuminate\Support\Facades\Cache::rememberForever('schematic_atlases', function () {
        $path = storage_path('app/public/schematic_atlases.json');
        if (!file_exists($path)) {
            return [];
        }
        return json_decode((string) file_get_contents($path), true) ?: [];
    });

    $canEditSchematic = auth()->check() && (
        auth()->user()->hasRole('webmaster') ||
        auth()->user()->can('manage_pages') ||
        auth()->user()->can('manage_wiki')
    );

    $atlasEntries = \App\Models\EncyclopediaCodex::where('is_visible', true)
        ->orderBy('article_title')
        ->get(['id', 'article_title', 'article_category', 'article_text']);

    $activePersonae = \App\Models\Persona::where('persona_status', 'active')
        ->whereNotNull('last_logged_location')
        ->get(['id', 'persona_name', 'last_logged_location', 'primary_portrait_url']);
@endphp

<div x-data="typeof schematicAtlasManager === 'function' ? schematicAtlasManager(@js($schematicsLedger), @js($defaultKey)) : {
         modals: { node: { open: false, x_percent: 50, y_percent: 50, codex_id: '', node_state: 'nominal', status_note: '', ambient_lighting_hex: '#0ea5e9FF', target_schematic_key: '' } },
         schematics: @js($schematicsLedger),
         activeKey: @js($defaultKey),
         editMode: false
     }"
     class="w-full">
    <x-tab type="accordion"
           :title="$title"
           icon="bi-bounding-box-circles"
           :open="true">
        @if($canEditSchematic)
            <x-slot:top>
                <x-button type="button"
                          variant="secondary"
                          size="sm"
                          icon="bi-pin-map"
                          :tooltip="lexicon('tooltip_Schematic_ToggleEditPins', 'Toggle click-to-pin compartment editor')"
                          @click="editMode = !editMode">
                    <span x-text="editMode ? '{{ addslashes(lexicon('btn_Schematic_FinishEditing', 'Lock Pins')) }}' : '{{ addslashes(lexicon('btn_Schematic_EditPins', 'Edit Schematic Nodes')) }}'"></span>
                </x-button>

                <x-button type="button"
                          variant="primary"
                          size="sm"
                          icon="bi-stars"
                          :tooltip="lexicon('tooltip_Schematic_AutoMap', 'Auto-plot compartment pins from Codex Atlas entries')"
                          @click="typeof autoPlotNodes === 'function' && autoPlotNodes()">
                    {{ lexicon('btn_Schematic_AutoMap', 'Auto-Map Compartments') }}
                </x-button>
            </x-slot:top>
        @endif

        @if(empty($schematicsLedger))
            <div class="bg-panel-sub border-dashed-panel border-rounded p-panel-body text-center">
                <span class="text-sm text-muted font-italic">{{ lexicon('msg_Schematic_NoneConfigured', 'No cutaway schematics have been configured in schematic_atlases.json yet.') }}</span>
            </div>
        @else
            @php
                $schematicTabs = [];
                foreach ($schematicsLedger as $sKey => $sData) {
                    $schematicTabs[$sKey] = $sData['title'] ?? \Illuminate\Support\Str::headline((string) $sKey);
                }
                $firstSchematicKey = (string) array_key_first($schematicTabs);
            @endphp

            <x-tab type="tabs"
                   :activeTab="isset($schematicTabs[$defaultKey]) ? $defaultKey : $firstSchematicKey"
                   :tabs="$schematicTabs">
                @foreach($schematicsLedger as $sKey => $schematic)
                    @php
                        $imageUrl = $schematic['external_schematic_url'] ?? $schematic['image_url'] ?? '';
                        $pins = $schematic['pins'] ?? [];
                    @endphp

                    <div x-show="activeTab === @js($sKey)"
                         class="flex flex-col gap-md w-full">
                        <x-preview type="schematic-msd" :label="$schematic['title'] ?? $title">
                            <div class="relative w-full overflow-hidden border-rounded border-panel">
                                @if($imageUrl !== '')
                                    <img src="{{ $imageUrl }}"
                                         alt="{{ $schematic['title'] ?? $title }}"
                                         class="w-full h-auto block select-none"
                                         @if($canEditSchematic)
                                             @click="if (editMode) {
                                                 const rect = $el.getBoundingClientRect();
                                                 modals.node.x_percent = Number((((event.clientX - rect.left) / rect.width) * 100).toFixed(2));
                                                 modals.node.y_percent = Number((((event.clientY - rect.top) / rect.height) * 100).toFixed(2));
                                                 modals.node.open = true;
                                             }"
                                         @endif>

                                    @foreach($pins as $pin)
                                        @php
                                            $xPct = (float) ($pin['x_percent'] ?? 50);
                                            $yPct = (float) ($pin['y_percent'] ?? 50);
                                            $pinTitle = $pin['label'] ?? lexicon('label_Schematic_Node', 'Compartment');
                                            $nodeState = $pin['node_state'] ?? 'nominal';
                                            $pinVariant = match($nodeState) {
                                                'alert_danger'   => 'danger',
                                                'alert_warning'  => 'warning',
                                                'offline_locked' => 'muted',
                                                default          => 'success',
                                            };
                                            $occupants = $activePersonae->filter(function ($p) use ($pinTitle) {
                                                return str_starts_with(strtolower((string) $p->last_logged_location), strtolower($pinTitle));
                                            });
                                            $occupantNames = $occupants->pluck('persona_name')->implode(', ');
                                            $tooltipText = $pinTitle
                                                . (!empty($pin['status_note']) ? ' [' . $pin['status_note'] . ']' : '')
                                                . ($occupantNames !== '' ? ' — Occupants: ' . $occupantNames : '');
                                        @endphp

                                        <div class="absolute z-20 cursor-pointer"
                                             style="left: {{ $xPct }}%; top: {{ $yPct }}%; transform: translate(-50%, -50%);"
                                             @if(!empty($pin['target_schematic_key']))
                                                 @click="activeTab = @js($pin['target_schematic_key'])"
                                             @endif>
                                            <x-badge type="pill"
                                                     :variant="$pinVariant"
                                                     icon="bi-geo-alt-fill"
                                                     :tooltip="$tooltipText">
                                                {{ $pinTitle }}
                                            </x-badge>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                        </x-preview>
                    </div>
                @endforeach
            </x-tab>
        @endif
    </x-tab>

    @if($canEditSchematic)
        <x-modal show="modals.node.open" onClose="modals.node.open = false">
            <x-slot:top>
                <span class="font-bold text-h">{{ lexicon('label_Schematic_ConfigurePin', 'Configure Compartment Pin') }}</span>
            </x-slot:top>

            <x-meta type="modal" action="/admin/modules/schematic-pin" method="POST">
                <div class="grid grid-cols-1 md-grid-cols-2 gap-md">
                    <x-metadata type="number"
                                name="x_percent"
                                :label="lexicon('label_Schematic_XPercent', 'X Coordinate (%)')"
                                xModel="modals.node.x_percent"
                                :required="true" />

                    <x-metadata type="number"
                                name="y_percent"
                                :label="lexicon('label_Schematic_YPercent', 'Y Coordinate (%)')"
                                xModel="modals.node.y_percent"
                                :required="true" />
                </div>

                <x-metadata type="select"
                            name="codex_id"
                            :label="lexicon('label_Schematic_LinkedCodex', 'Linked Atlas / Technology Entry')"
                            xModel="modals.node.codex_id"
                            x-init="window.Select && window.Select.init($el)">
                    <option value="">{{ lexicon('label_common_SelectAnOption', '-- Select an Option --') }}</option>
                    @foreach($atlasEntries as $entry)
                        <option value="{{ $entry->id }}">{{ $entry->article_title }} ({{ $entry->article_category }})</option>
                    @endforeach
                </x-metadata>

                <x-metadata type="select"
                            name="node_state"
                            :label="lexicon('label_Schematic_AlertState', 'Compartment Alert State')"
                            xModel="modals.node.node_state">
                    <option value="nominal">{{ lexicon('label_Schematic_StateNominal', 'Nominal (Green)') }}</option>
                    <option value="alert_warning">{{ lexicon('label_Schematic_StateWarning', 'Yellow Alert / Warning') }}</option>
                    <option value="alert_danger">{{ lexicon('label_Schematic_StateDanger', 'Red Alert / Critical') }}</option>
                    <option value="offline_locked">{{ lexicon('label_Schematic_StateOffline', 'Offline / Locked') }}</option>
                </x-metadata>

                <x-metadata type="text"
                            name="status_note"
                            :label="lexicon('label_Schematic_StatusNote', 'Status Telemetry Note')"
                            xModel="modals.node.status_note" />

                <x-colour-picker name="ambient_lighting_hex"
                                 :label="lexicon('label_Schematic_AmbientLighting', 'Ambient Lighting Tint (#RRGGBBAA)')"
                                 xModel="modals.node.ambient_lighting_hex" />

                <x-slot:bottom>
                    <x-button type="button"
                              variant="secondary"
                              :tooltip="lexicon('tooltip_common_Cancel', 'Cancel')"
                              @click="modals.node.open = false">
                        {{ lexicon('btn_common_Cancel', 'Cancel') }}
                    </x-button>
                    <x-button type="submit"
                              variant="primary"
                              icon="bi-check-lg"
                              :tooltip="lexicon('tooltip_Schematic_SavePin', 'Save compartment pin')">
                        {{ lexicon('btn_common_Save', 'Save Pin') }}
                    </x-button>
                </x-slot:bottom>
            </x-meta>
        </x-modal>
    @endif
</div>