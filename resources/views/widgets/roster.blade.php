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
        }
    }
})
--}}
@php
    // 1. Cache the 5-table query as a plain array to prevent Incomplete Class errors
    $sectionsArray = \Illuminate\Support\Facades\Cache::rememberForever('widget_roster_sections', function() {
        return \App\Models\Section::with(['positions.characters' => function($q) {
            $q->where('status', 'active');
        }, 'positions.characters.rank.rankSet'])->orderBy('order')->get()->toArray();
    });
    
    // Re-wrap the array into standard objects so Blade syntax ($section->name) works
    $sections = json_decode(json_encode($sectionsArray));
    
    // 2. Reuse the settings cache from AppServiceProvider (0 database queries)
    $skinSettings = \Illuminate\Support\Facades\Cache::get('skin_settings', []);
    
    // Cache the folder name string
    $activeSkin = \Illuminate\Support\Facades\Cache::rememberForever('widget_active_skin_folder', function() {
        return \App\Models\Skin::where('is_active', 1)->first()->folder_name ?? 'default';
    });
    
    // 3. Extract instance options
    $options = is_string($widget->pivot->instance_options) ? json_decode($widget->pivot->instance_options, true) : ($widget->pivot->instance_options ?? []);
    $showVacant = $options['show_vacant'] ?? 'yes';
@endphp

<div class="flex flex-col gap-lg w-full mb-6" x-data>
    @forelse($sections as $section)
        <x-accordion-panel :title="$section->name" :expanded="true">
            {{-- 3-column macro grid --}}
            <div class="grid grid-cols-1 lg-grid-cols-3 gap-md w-full">
                @foreach($section->positions as $position)
                    @forelse($position->characters as $character)
                        <div class="bg-panel border border-panel rounded-input shadow-panel overflow-hidden hover-bg-panel-hover flex flex-row flex-wrap items-center p-3 gap-md {{ strtolower($character->character_type ?? 'pc') === 'pc' ? 'border-pc shadow-pc' : (in_array(strtolower($character->character_type ?? ''), ['sc', 'snpc']) ? 'border-success shadow-success' : 'border-warning shadow-warning') }}">
                            
                            <div class="bg-panel-header rounded-input flex-shrink-0 flex items-center justify-center overflow-hidden w-16 h-16">
                                @if(!empty($character->avatar))
                                    <a href="{{ asset('storage/'.$character->avatar) }}" 
                                       class="glightbox block w-full h-full"
                                       data-glightbox="title: {{ $character->name }}"
                                       aria-label="{{ $character->name }}"
                                       x-tooltip="'{{ addslashes($character->name) }}'">
                                        <img src="{{ asset('storage/'.$character->avatar) }}" alt="{{ $character->name }}" class="w-full h-full object-cover">
                                    </a>
                                @else
                                    <i class="bi bi-person text-2xl text-muted" aria-hidden="true"></i>
                                @endif
                            </div>
                            
                            <div class="flex flex-col flex-grow gap-xs">
                                @php
                                    $rank = $character->rank ?? null;
                                    $rankSetObj = $rank->rank_set ?? $rank->rankSet ?? null;
                                    $rankSetName = $rankSetObj->name ?? 'default';
                                @endphp
                            
                                <a href="/bio/{{ $character->id }}" 
                                   class="font-bold text-lg text-p hover-text-h block transition-colors no-underline"
                                   aria-label="{{ lexicon('tooltip_Roster_ViewCharacterBio', 'View character biography') }}"
                                   x-tooltip="'{{ addslashes(lexicon('tooltip_Roster_ViewCharacterBio', 'View character biography')) }}'">
                                    @if(!empty($rank->name))
                                        <span class="block text-sm">{{ $rank->name }}</span> 
                                    @endif
                                    <span>{{ $character->name }}</span>
                                </a>
                                <span class="block text-sm text-muted">{{ $position->name }}</span>
                                
                                <div class="inline-block"
                                     aria-label="{{ !empty($rank->name) ? $rank->name : lexicon('tooltip_common_CharacterRank', 'Character Rank') }}"
                                     x-tooltip="'{{ addslashes(!empty($rank->name) ? $rank->name : lexicon('tooltip_common_CharacterRank', 'Character Rank')) }}'">
                                    <x-rank-badge 
                                        :rankSet="$rankSetName" 
                                        baseLayer="{{ $skinSettings['theme_base_color'] ?? '#1a1a1a' }}" 
                                        departmentColor="{{ $section->color ?? '' }}" 
                                        :midType="$rank->mid_type ?? 'css'"
                                        :midImage="$rank->mid_image ?? null"
                                        :midCss="$rank->mid_css ?? ''"
                                        :topType="$rank->top_type ?? 'image'"
                                        :topImage="$rank->top_image ?? null"
                                        :topCss="$rank->top_css ?? ''"
                                        skin="{{ $activeSkin }}" 
                                    />
                                </div>
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
                                    <span class="block text-sm text-muted">{{ $position->name }}</span>
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
            <span class="text-muted italic">{{ lexicon('msg_common_NoManifestEntries', 'No sections or characters found in the manifest.') }}</span>
        </div>
    @endforelse
</div>