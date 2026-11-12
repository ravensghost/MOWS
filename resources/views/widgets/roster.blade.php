{{-- 
@WidgetManifest({
    "name": "Character Roster",
    "description": "Displays the active character manifest.",
    "icon": "bi-people",
    "fields": {
        "show_vacant": {
            "type": "dropdown",
            "label": "Show Vacant Positions?",
            "options": {"yes": "Yes", "no": "No"},
            "default": "yes"
        },
        "division_catalogue_id": {
            "type": "number",
            "label": "Division Catalogue ID (Optional)",
            "default": ""
        }
    }
})
--}}
@php
    // 1. Extract instance options
    $options = is_string($widget->pivot->instance_options) ? json_decode($widget->pivot->instance_options, true) : ($widget->pivot->instance_options ?? []);
    $showVacant = $options['show_vacant'] ?? 'yes';
    $divisionCatalogueId = !empty($options['division_catalogue_id']) ? (int) $options['division_catalogue_id'] : null;

    // 2. Cache the query as a plain array to prevent Incomplete Class errors
    $divisionsArray = \Illuminate\Support\Facades\Cache::rememberForever('widget_roster_sections', function() {
        return \App\Models\Division::where('is_visible', true)
            ->with([
                'assignments' => function($q) {
                    $q->where('is_visible', true);
                },
                'assignments.characters' => function($q) {
                    $q->where('status', 'active');
                },
                'assignments.characters.insignia' => function($q) {
                    $q->where('is_visible', true);
                },
                'assignments.characters.insignia.insigniaCatalogue' => function($q) {
                    $q->where('is_visible', true);
                },
            ])
            ->orderBy('division_sort_order')
            ->get()
            ->toArray();
    });
    
    // Re-wrap the array into standard objects and filter by visibility / division_catalogue_id
    $divisions = collect(json_decode(json_encode($divisionsArray)))
        ->filter(fn($d) => (bool) ($d->is_visible ?? true))
        ->when($divisionCatalogueId, fn($c) => $c->where('division_catalogue_id', $divisionCatalogueId))
        ->values();
    
    // 3. Reuse the settings cache from AppServiceProvider (0 database queries)
    $skinSettings = \Illuminate\Support\Facades\Cache::get('skin_settings', []);
    
    // Cache the folder name string
    $activeSkin = \Illuminate\Support\Facades\Cache::rememberForever('widget_active_skin_folder', function() {
        return \App\Models\Skin::where('is_active', 1)->first()->folder_name ?? 'default';
    });
@endphp

<div class="flex flex-col gap-lg w-full mb-6" x-data>
    @forelse($divisions as $division)
    <x-accordion-panel :title="$division->division_title" :expanded="true" headerClass="panel-l-accent section-theme-{{ $division->id }}">
            {{-- 3-column macro grid --}}
            <div class="grid grid-cols-1 lg-grid-cols-3 gap-md w-full ">
                @foreach($division->assignments ?? [] as $assignment)
                    @continue(!($assignment->is_visible ?? true))
                    @forelse($assignment->characters ?? [] as $character)
                        <div class="bg-panel border border-panel rounded-input shadow-panel overflow-hidden hover-bg-panel-hover flex flex-row flex-wrap items-center p-3 gap-md {{ strtolower($character->persona_type ?? 'pc') === 'pc' ? 'border-pc shadow-pc' : (in_array(strtolower($character->persona_type ?? ''), ['sc', 'snpc']) ? 'border-success shadow-success' : 'border-warning shadow-warning') }}">
                            
                            <div class="bg-panel-header rounded-input flex-shrink-0 flex items-center justify-center overflow-hidden w-16 h-16">
                                @if(!empty($character->portrait_image))
                                    <a href="{{ asset('images/characters/'.$character->portrait_image) }}" 
                                       class="glightbox block w-full h-full"
                                       data-glightbox="title: {{ $character->persona_designation }}"
                                       aria-label="{{ $character->persona_designation }}"
                                       x-tooltip="'{{ addslashes($character->persona_designation) }}'">
                                        <img src="{{ asset('images/characters/'.$character->portrait_image) }}" alt="{{ $character->persona_designation }}" class="w-full h-full object-cover">
                                    </a>
                                @else
                                    <i class="bi bi-person text-2xl text-muted" aria-hidden="true"></i>
                                @endif
                            </div>
                            
                            <div class="flex flex-col flex-grow gap-xs">
                                @php
                                    $rawInsignia = $character->insignia ?? null;
                                    $rawCatalogue = $rawInsignia ? ($rawInsignia->insignia_catalogue ?? $rawInsignia->insigniaCatalogue ?? null) : null;
                                    $insigniaVisible = $rawInsignia && ($rawInsignia->is_visible ?? true) && (!$rawCatalogue || ($rawCatalogue->is_visible ?? true));
                                    $insignia = $insigniaVisible ? $rawInsignia : null;
                                    $catalogueCode = ($insignia && $rawCatalogue) ? ($rawCatalogue->catalogue_code ?? 'default') : 'default';
                                @endphp
                            
                                <a href="/bio/{{ $character->id }}" 
                                   class="font-bold text-lg text-p hover-text-h block transition-colors no-underline"
                                   aria-label="{{ lexicon('tooltip_Roster_ViewCharacterBio', 'View character biography') }}"
                                   x-tooltip="'{{ addslashes(lexicon('tooltip_Roster_ViewCharacterBio', 'View character biography')) }}'">
                                    @if(!empty($insignia->insignia_title))
                                        <span class="block text-sm">{{ $insignia->insignia_title }}</span> 
                                    @endif
                                    <span>{{ $character->persona_designation }}</span>
                                </a>
                                <span class="block text-sm text-muted">{{ $assignment->assignment_title }}</span>
                                
                                @if($insignia)
                                    <div class="inline-block"
                                         aria-label="{{ !empty($insignia->insignia_title) ? $insignia->insignia_title : lexicon('tooltip_common_CharacterRank', 'Character Rank') }}"
                                         x-tooltip="'{{ addslashes(!empty($insignia->insignia_title) ? $insignia->insignia_title : lexicon('tooltip_common_CharacterRank', 'Character Rank')) }}'">
                                        <x-rank-badge 
                                            :rankSet="$catalogueCode" 
                                            baseLayer="{{ $skinSettings['theme_base_colour'] ?? '#1a1a1a' }}" 
                                            departmentColor="{{ $division->division_base_colour ?? '#374151' }}" 
                                            :midType="$insignia->mid_type ?? 'css'"
                                            :midImage="$insignia->mid_image ?? null"
                                            :midCss="$insignia->mid_css ?? ''"
                                            :topType="$insignia->top_type ?? 'image'"
                                            :topImage="$insignia->top_image ?? null"
                                            :topCss="$insignia->top_css ?? ''"
                                            skin="{{ $activeSkin }}" 
                                        />
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        @if($showVacant === 'yes')
                            {{-- Vacant Position State --}}
                            <div class="bg-panel border border-panel rounded-input shadow-panel overflow-hidden border-dashed opacity-75 flex flex-row flex-wrap items-center p-3 gap-md">
                                <div class="bg-panel-header rounded-input flex-shrink-0 flex items-center justify-center overflow-hidden w-16 h-16">
                                    <i class="bi bi-person-plus text-2xl text-muted" aria-hidden="true"></i>
                                </div>
                                <div class="flex flex-col flex-grow gap-xs">
                                    <span class="block font-bold text-lg text-muted">{{ lexicon('label_common_Vacant', 'Vacant') }}</span>
                                    <span class="block text-sm text-muted">{{ $assignment->assignment_title }}</span>
                                    <a href="/apply" 
                                       class="block text-xs text-accent hover-text-h transition-colors no-underline"
                                       aria-label="{{ lexicon('tooltip_Roster_ApplyForPosition', 'Submit a character application for this role') }}"
                                       x-tooltip="'{{ addslashes(lexicon('tooltip_Roster_ApplyForPosition', 'Submit a character application for this role')) }}'">
                                        {{ lexicon('btn_common_ApplyForRole', 'Apply for this role') }} &rarr;
                                    </a>
                                </div>
                            </div>
                        @endif
                    @endforelse
                @endforeach
            </div>
        </x-accordion-panel>
    @empty
        <div class="bg-panel border border-panel rounded-panel shadow-panel flex flex-col overflow-hidden p-panel text-center">
            <span class="text-muted italic">{{ lexicon('msg_common_NoManifestEntries', 'No divisions or characters found in the manifest.') }}</span>
        </div>
    @endforelse
</div>