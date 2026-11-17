{{-- resources/views/modules/compendium_exporter.blade.php --}}
{{-- @WidgetManifest({ "name": "Starter-Pack Compendium Exporter", "description": "Compiles lean starter-pack .zip compendiums and granular JSON/ZIP asset manifests for MOWS deployments.", "icon": "bi-box-seam", "hidden": true, "fields": { "title": { "type": "text", "label": "Module Title", "default": "Starter-Pack Compendium Exporter" } } }) --}}
@php
    $rawOptions = $moduleSettings
        ?? $module?->pivot?->book_config
        ?? $module?->pivot?->instance_options
        ?? $widget?->pivot?->instance_options
        ?? [];
    $options = is_string($rawOptions) ? (json_decode($rawOptions, true) ?: []) : (is_array($rawOptions) ? $rawOptions : []);

    $rawTitle = trim((string) ($options['title'] ?? ''));
    $title = $rawTitle !== '' ? $rawTitle : lexicon('label_Compendium_DefaultTitle', 'Starter-Pack Compendium Exporter');

    $canExport = auth()->check() && (
        auth()->user()->hasRole('webmaster') ||
        auth()->user()->can('manage_settings')
    );

    $presets = [
        'blank'         => lexicon('label_Compendium_PresetBlank', 'Blank Core Engine (Default Binding)'),
        'star_trek_tos' => lexicon('label_Compendium_PresetTos', 'Star Trek: The Original Series (TOS)'),
        'star_trek_tng' => lexicon('label_Compendium_PresetTng', 'Star Trek: The Next Generation (TNG / 24th Century)'),
        'babylon_5'     => lexicon('label_Compendium_PresetB5', 'Babylon 5 ( Diplomatic Station )'),
        'stargate'      => lexicon('label_Compendium_PresetStargate', 'Stargate Command (SGC)'),
        'harry_potter'  => lexicon('label_Compendium_PresetPotter', 'Wizarding Academy (Harry Potter)'),
    ];
@endphp

@if($canExport)
    <div x-data="typeof compendiumExporterManager === 'function' ? compendiumExporterManager() : { preset: 'blank', isBuilding: false }"
         class="w-full">
        <x-tab type="accordion"
               :title="$title"
               icon="bi-box-seam"
               :open="true">
            <x-slot:top>
                <x-badge type="pill"
                         variant="info"
                         icon="bi-hdd-network"
                         :tooltip="lexicon('tooltip_Compendium_InodeOptimised', 'Strips dev directories and non-essential widgets for 30,000 inode compliance')">
                    {{ lexicon('label_Compendium_InodeSafe', '30k Inode Optimised') }}
                </x-badge>
            </x-slot:top>

            <div class="grid grid-cols-1 md-grid-cols-2 gap-md w-full">
                {{-- Full Starter-Pack .ZIP Compiler --}}
                <x-tab type="sub"
                       :title="lexicon('label_Compendium_FullPackageHeading', 'Compile Starter-Pack Compendium (.zip)')"
                       icon="bi-file-earmark-zip">
                    <x-meta action="/admin/compendium/export"
                            method="POST"
                            :ajax="false">
                        <x-metadata type="select"
                                    name="preset"
                                    :label="lexicon('label_Compendium_SelectPreset', 'Franchise Starter-Pack Preset')"
                                    xModel="preset"
                                    :tooltip="lexicon('tooltip_Compendium_SelectPreset', 'Choose the franchise skin, ranks, divisions, and lore seed manifests to bundle')">
                            @foreach($presets as $presetKey => $presetLabel)
                                <option value="{{ $presetKey }}">{{ $presetLabel }}</option>
                            @endforeach
                        </x-metadata>

                        <x-slot:bottom>
                            <x-button type="submit"
                                      variant="primary"
                                      icon="bi-download"
                                      :tooltip="lexicon('tooltip_Compendium_CompileZip', 'Build and download ready-to-deploy MOWS starter pack')">
                                {{ lexicon('btn_Compendium_CompileZip', 'Compile & Download .ZIP') }}
                            </x-button>
                        </x-slot:bottom>
                    </x-meta>
                </x-tab>

                {{-- Granular Asset Export Toolbox --}}
                <x-tab type="sub"
                       :title="lexicon('label_Compendium_GranularToolbox', 'Granular Asset Export Toolbox')"
                       icon="bi-tools">
                    <span class="text-xs text-muted block">
                        {{ lexicon('msg_Compendium_GranularHint', 'Download standalone JSON or ZIP manifests for cross-site migration.') }}
                    </span>

                    <div class="flex flex-row flex-wrap items-center gap-sm">
                        <x-button href="/admin/divisions/export"
                                  variant="secondary"
                                  size="sm"
                                  icon="bi-diagram-3"
                                  :tooltip="lexicon('tooltip_Compendium_ExportDivisions', 'Export Divisions & Assignments JSON')">
                            {{ lexicon('btn_Compendium_Divisions', 'Divisions JSON') }}
                        </x-button>

                        <x-button href="/admin/insignia/export"
                                  variant="secondary"
                                  size="sm"
                                  icon="bi-shield-check"
                                  :tooltip="lexicon('tooltip_Compendium_ExportInsignia', 'Export Insignia Catalogue JSON')">
                            {{ lexicon('btn_Compendium_Insignia', 'Insignia JSON') }}
                        </x-button>

                        <x-button href="/admin/lexicon-codex/export"
                                  variant="secondary"
                                  size="sm"
                                  icon="bi-translate"
                                  :tooltip="lexicon('tooltip_Compendium_ExportLexicon', 'Export Lexicon & Conlang Profiles JSON')">
                            {{ lexicon('btn_Compendium_Conlangs', 'Lexicon & Conlangs') }}
                        </x-button>
                    </div>
                </x-tab>
            </div>
        </x-tab>
    </div>
@endif