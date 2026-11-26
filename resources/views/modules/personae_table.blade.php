{{-- resources/views/modules/personae_table.blade.php --}}
{{-- @WidgetManifest({ "name": "Personae Table", "description": "Displays the active personae manifest.", "icon": "bi-people", "fields": { "show_vacant": { "type": "dropdown", "label": "Show Vacant Positions?", "options": {"yes": "Yes", "no": "No"}, "default": "yes" }, "division_catalogue_id": { "type": "number", "label": "Division Catalogue ID (Optional)", "default": "" } } }) --}}
@php
    // 1. Extract instance options
    $rawOptions = $moduleSettings
        ?? $module?->pivot?->book_config
        ?? $module?->pivot?->instance_options
        ?? $widget?->pivot?->instance_options
        ?? [];
    $options = is_string($rawOptions) ? (json_decode($rawOptions, true) ?: []) : (is_array($rawOptions) ? $rawOptions : []);

    $showVacant = $options['show_vacant'] ?? 'yes';
    $divisionCatalogueId = ! empty($options['division_catalogue_id']) ? (int) $options['division_catalogue_id'] : null;

    // 2. Cache the query as a plain array to prevent Incomplete Class errors
    $divisionsArray = \Illuminate\Support\Facades\Cache::rememberForever('widget_personae_table_sections', function () {
        return \App\Models\Division::where('is_visible', true)
            ->with([
                'assignments' => function ($q) {
                    $q->where('is_visible', true)->orderBy('assignment_sort_order');
                },
                'assignments.personae' => function ($q) {
                    $q->where('persona_status', 'active');
                },
                'assignments.personae.insignia' => function ($q) {
                    $q->where('is_visible', true);
                },
                'assignments.personae.insignia.insigniaCatalogue' => function ($q) {
                    $q->where('is_visible', true);
                },
            ])
            ->orderBy('division_sort_order')
            ->get()
            ->toArray();
    });

    // Re-wrap the array into standard objects and filter by visibility / division_catalogue_id
    $divisions = collect(json_decode(json_encode($divisionsArray)))
        ->filter(fn ($d) => (bool) ($d->is_visible ?? true))
        ->when($divisionCatalogueId, fn ($c) => $c->where('division_catalogue_id', $divisionCatalogueId))
        ->values();

    // 3. Reuse the settings cache from AppServiceProvider (0 database queries)
    $skinSettings = \Illuminate\Support\Facades\Cache::get('skin_settings', []);

    // Cache the folder name string
    $activeSkin = \Illuminate\Support\Facades\Cache::rememberForever('widget_active_skin_folder', function () {
        return \App\Models\ThematicBinding::where('is_global_default', true)->value('directory_name') ?? 'default';
    });
@endphp

<div class="flex flex-col gap-md w-full"
     x-data
     x-init="if (window.Lightbox && typeof window.Lightbox.init === 'function') { $nextTick(() => window.Lightbox.init()); }">
    @forelse($divisions as $division)
        @php
            $deptColour = $division->division_base_colour ?? '#374151FF';
            $baseLayerColour = $skinSettings['theme_base_colour'] ?? '#1a1a1aFF';
            $divisionSynopsis = $division->division_synopsis ?? $division->division_description ?? '';
        @endphp

        <x-tab type="accordion"
               :title="$division->division_title"
               icon="bi-people"
               :open="true"
               :style="'--section-color: ' . $deptColour . ';'"
               headerClass="border-l-divisions">
            @if(! empty($divisionSynopsis))
                <p class="text-sm text-muted font-italic m-0 pb-2 border-b-panel">
                    {{ $divisionSynopsis }}
                </p>
            @endif

            <div class="grid grid-cols-1 md-grid-cols-2 lg-grid-cols-3 gap-md w-full">
                @foreach($division->assignments ?? [] as $assignment)
                    @php
                        $activePersonae = collect($assignment->personae ?? []);
                    @endphp

                    @forelse($activePersonae as $persona)
                        @php
                            $personaName = $persona->persona_name ?? lexicon('label_common_UnknownPersona', 'Unknown Persona');
                            $rawPortrait = $persona->primary_portrait_url ?? $persona->primary_portrait_uri ?? null;
                            $portraitUrl = $rawPortrait
                                ? (\Illuminate\Support\Str::startsWith($rawPortrait, ['http://', 'https://', '/']) ? $rawPortrait : asset('images/characters/' . $rawPortrait))
                                : null;

                            $rawInsignia = $persona->insignia ?? null;
                            $rawCatalogue = $rawInsignia ? ($rawInsignia->insignia_catalogue ?? $rawInsignia->insigniaCatalogue ?? null) : null;
                            $insigniaVisible = $rawInsignia && ($rawInsignia->is_visible ?? true) && (! $rawCatalogue || ($rawCatalogue->is_visible ?? true));
                            $insignia = $insigniaVisible ? $rawInsignia : null;
                            $catalogueTitle = ($insignia && $rawCatalogue)
                                ? ($rawCatalogue->catalogue_title ?? $rawCatalogue->catalogue_code ?? 'DefaultSet')
                                : 'DefaultSet';
                        @endphp

                        <div class="bg-panel-sub border-panel border-rounded shadow-panel overflow-hidden flex flex-row flex-wrap items-center p-3 gap-md hover-border-accent transition-colors">
                            <div class="bg-panel-header border-rounded flex-shrink-0 flex items-center justify-center overflow-hidden w-16 h-16">
                                @if($portraitUrl)
                                    <a href="{{ $portraitUrl }}"
                                       class="glightbox block w-full h-full"
                                       data-glightbox="title: {{ $personaName }}"
                                       aria-label="{{ $personaName }}"
                                       title="{{ $personaName }}"
                                       x-tooltip="'{{ addslashes($personaName) }}'">
                                        <img src="{{ $portraitUrl }}"
                                             alt="{{ $personaName }}"
                                             class="w-full h-full object-cover block">
                                    </a>
                                @else
                                    <i class="bi bi-person text-2xl text-muted" aria-hidden="true"></i>
                                @endif
                            </div>

                            <div class="flex flex-col flex-grow gap-xs">
                                <a href="/bio/{{ $persona->id }}"
                                   class="font-bold text-lg text-p hover-text-h block transition-colors no-underline"
                                   x-tooltip="'{{ addslashes(lexicon('tooltip_Roster_ViewPersonaBio', 'View persona biography')) }}'"
                                   title="{{ lexicon('tooltip_Roster_ViewPersonaBio', 'View persona biography') }}"
                                   aria-label="{{ lexicon('tooltip_Roster_ViewPersonaBio', 'View persona biography') }}">
                                    @if(! empty($insignia->insignia_title))
                                        <span class="block text-xs text-accent uppercase text-wider">{{ $insignia->insignia_title }}</span>
                                    @endif
                                    <span>{{ $personaName }}</span>
                                </a>

                                <span class="block text-sm text-muted">{{ $assignment->assignment_title }}</span>

                                @if($insignia)
                                    <div class="inline-block">
                                        <x-badge type="insignia"
                                                 :rank="$insignia"
                                                 :rankSet="$catalogueTitle"
                                                 :baseLayer="$baseLayerColour"
                                                 :departmentColour="$deptColour"
                                                 :midLayerMode="$insignia->mid_layer_mode ?? $insignia->mid_type ?? 'css'"
                                                 :midLayerAsset="$insignia->mid_layer_asset ?? $insignia->mid_image ?? null"
                                                 :midLayerModifier="$insignia->mid_layer_modifier ?? $insignia->mid_css ?? ''"
                                                 :midLayerHue="$insignia->mid_layer_hue ?? null"
                                                 :topLayerMode="$insignia->top_layer_mode ?? $insignia->top_type ?? 'image'"
                                                 :topLayerAsset="$insignia->top_layer_asset ?? $insignia->top_image ?? null"
                                                 :topLayerModifier="$insignia->top_layer_modifier ?? $insignia->top_css ?? ''"
                                                 :topLayerHue="$insignia->top_layer_hue ?? null"
                                                 :skin="$activeSkin"
                                                 :tooltip="$insignia->insignia_title ?? lexicon('tooltip_common_PersonaRank', 'Persona Insignia')" />
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        @if($showVacant === 'yes')
                            {{-- Vacant Position State --}}
                            <div class="bg-panel-sub border-dashed-panel border-rounded p-3 flex flex-row flex-wrap items-center gap-md">
                                <div class="bg-panel-header border-rounded flex-shrink-0 flex items-center justify-center overflow-hidden w-16 h-16">
                                    <i class="bi bi-person-plus text-2xl text-muted" aria-hidden="true"></i>
                                </div>

                                <div class="flex flex-col flex-grow gap-xs">
                                    <span class="block font-bold text-lg text-muted">{{ lexicon('label_common_Vacant', 'Vacant') }}</span>
                                    <span class="block text-sm text-muted">{{ $assignment->assignment_title }}</span>
                                    <a href="/apply"
                                       class="block text-xs font-bold text-accent hover-text-h transition-colors no-underline"
                                       x-tooltip="'{{ addslashes(lexicon('tooltip_Roster_ApplyForPosition', 'Submit a persona petition for this role')) }}'"
                                       title="{{ lexicon('tooltip_Roster_ApplyForPosition', 'Submit a persona petition for this role') }}"
                                       aria-label="{{ lexicon('tooltip_Roster_ApplyForPosition', 'Submit a persona petition for this role') }}">
                                        {{ lexicon('btn_common_ApplyForRole', 'Apply for this role') }} &rarr;
                                    </a>
                                </div>
                            </div>
                        @endif
                    @endforelse
                @endforeach
            </div>
        </x-tab>
    @empty
        <x-tab type="panel">
            <div class="bg-panel-sub border-dashed-panel border-rounded p-panel-body text-center">
                <span class="text-sm text-muted font-italic">{{ lexicon('msg_common_NoManifestEntries', 'No divisions or personae found in the manifest.') }}</span>
            </div>
        </x-tab>
    @endforelse
</div>