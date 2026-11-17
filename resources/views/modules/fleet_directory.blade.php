{{-- resources/views/modules/fleet_directory.blade.php --}}
{{-- @WidgetManifest({ "name": "Allied Sim Docking Directory", "description": "Polls satellite MOWS sites via /api/engine/telemetry to display a live allied network directory.", "icon": "bi-globe2", "fields": { "title": { "type": "text", "label": "Module Title", "default": "Allied Fleet & Sim Directory" }, "satellite_urls": { "type": "textarea", "label": "Satellite Site URLs (Comma-separated)", "default": "" } } }) --}}
@php
    $rawOptions = $moduleSettings
        ?? $module?->pivot?->book_config
        ?? $module?->pivot?->instance_options
        ?? $widget?->pivot?->instance_options
        ?? [];
    $options = is_string($rawOptions) ? (json_decode($rawOptions, true) ?: []) : (is_array($rawOptions) ? $rawOptions : []);

    $rawTitle = trim((string) ($options['title'] ?? ''));
    $title = $rawTitle !== '' ? $rawTitle : lexicon('label_FleetDirectory_DefaultTitle', 'Allied Fleet & Sim Directory');
    $satellites = $fleetDirectory ?? [];
@endphp

<x-tab type="accordion"
       :title="$title"
       icon="bi-globe2"
       :open="true">
    @if(empty($satellites))
        <div class="bg-panel-sub border-dashed-panel border-rounded p-panel-body text-center">
            <span class="text-sm text-muted font-italic">{{ lexicon('msg_FleetDirectory_Empty', 'No allied satellite telemetry stations are currently docked.') }}</span>
        </div>
    @else
        <div class="grid grid-cols-1 md-grid-cols-2 gap-md w-full">
            @foreach($satellites as $sim)
                <x-tab type="sub"
                       :title="$sim['unit_designation'] ?? lexicon('label_common_UnknownUnit', 'Allied Unit')"
                       icon="bi-broadcast-pin">
                    <x-slot:top>
                        <x-badge type="ai-rating"
                                 :rating="$sim['ai_rating'] ?? 'R0-S0-V0'"
                                 :tooltip="lexicon('tooltip_FleetDirectory_AiRating', 'Satellite AI & Content Rating')" />
                    </x-slot:top>

                    <div class="flex flex-col gap-xs text-xs text-muted">
                        @if(!empty($sim['unit_class']))
                            <span><strong class="text-h">{{ lexicon('label_FleetDirectory_Class', 'Class:') }}</strong> {{ $sim['unit_class'] }}</span>
                        @endif
                        @if(!empty($sim['era_setting']))
                            <span><strong class="text-h">{{ lexicon('label_FleetDirectory_Era', 'Era / Setting:') }}</strong> {{ $sim['era_setting'] }}</span>
                        @endif
                    </div>

                    @if(!empty($sim['url']))
                        <div class="border-t-actions pt-2 flex flex-row justify-end">
                            <x-button :href="$sim['url']"
                                      target="_blank"
                                      rel="noopener noreferrer"
                                      variant="ghost"
                                      size="sm"
                                      icon="bi-box-arrow-up-right"
                                      :tooltip="lexicon('tooltip_FleetDirectory_VisitSim', 'Open allied simulation in a new tab')">
                                {{ lexicon('btn_FleetDirectory_HailUnit', 'Hail Simulation') }}
                            </x-button>
                        </div>
                    @endif
                </x-tab>
            @endforeach
        </div>
    @endif
</x-tab>