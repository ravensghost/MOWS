{{-- 
@WidgetManifest({
    "name": "Persona Metadata Summary",
    "description": "Displays persona identity, insignia, assignment, duty status, owning user details, physical vitals, and psychological profile.",
    "icon": "bi-person-badge",
    "fields": {
        "title": { "type": "text", "label": "Widget Title", "default": "Persona Metadata Summary" }
    }
})
--}}
@php
    $options = is_string($widget->pivot->instance_options) ? json_decode($widget->pivot->instance_options, true) : ($widget->pivot->instance_options ?? []);
    $rawTitle = trim($options['title'] ?? '');$title = $rawTitle !== '' ?$rawTitle : lexicon('label_PersonaSummary_DefaultTitle', 'Persona Metadata Summary');

    // Retrieve eager-loaded candidate petition entries linked to this persona without N+1 queries
    $targetPersona = $persona ?? $character ?? null;
    $petition = null;

    if ($targetPersona) {
        if ($targetPersona->relationLoaded('petition')) {
            $petition =$targetPersona->petition;
        } else {
            $petition = \App\Models\CandidatePetition::with('entries')
                ->where('persona_id', $targetPersona->id)
                ->first();
        }
    }

    // Account, Writer & Duty Status Vitals (Merged from legacy character_sidebar_meta)
    $user =$targetPersona?->user;
    $status = strtolower($targetPersona?->persona_status ?? $targetPersona?->status ?? 'active');

    $statusBadge = match($status) {
        'active'                    => 'success',
        'loa'                       => 'warning',
        'eloa'                      => 'danger',
        'honoured_dead', 'memorial' => 'primary',
        default                     => 'primary'
    };

    $statusLabel = match($status) {
        'active'        => lexicon('label_ServiceRecords_StatusActive', 'Active'),
        'loa'           => lexicon('label_ServiceRecords_StatusLoa', 'Leave of Absence (LOA)'),
        'eloa'          => lexicon('label_ServiceRecords_StatusEloa', 'Extended Leave of Absence (ELOA)'),
        'honoured_dead' => lexicon('label_ServiceRecords_StatusHonouredDead', 'Honoured Dead'),
        'memorial'      => lexicon('label_ServiceRecords_StatusMemorial', 'Memorial'),
        default         => strtoupper(str_replace('_', ' ', $status))
    };

    $twitter =$user?->twitter_handle ?? null;
    $facebook =$user?->facebook_handle ?? null;
    $facebookUrl = $facebook && \Illuminate\Support\Str::startsWith($facebook, ['http://', 'https://'])
        ? $facebook
        : ($facebook ? 'https://facebook.com/' . ltrim($facebook, '/') : null);

    $entriesCollection = collect();
    if ($petition) {
        if ($petition->relationLoaded('entries')) {
            $entriesCollection =$petition->entries;
        } elseif ($petition->relationLoaded('answers')) {
            $entriesCollection =$petition->answers;
        } elseif (method_exists($petition, 'entries')) {
            $entriesCollection =$petition->entries()->get();
        } elseif (method_exists($petition, 'answers')) {
            $entriesCollection =$petition->answers()->get();
        }
    }

    $metadataMap = \App\Models\Metadata::all()->keyBy('id');

    $entriesByKey = $entriesCollection->keyBy(function ($item) use ($metadataMap) {$meta = ($item->relationLoaded('metadata') ?$item->metadata : null)
            ?? $metadataMap->get($item->metadata_id);

        $fieldName =$meta->metadata_title ?? $meta->metadata_label ?? $meta->metadata_key ?? '';
        return strtolower(trim($fieldName));
    });

    $getEntryValue = function ($names) use ($entriesByKey) {
        foreach ((array) $names as$name) {
            $key = strtolower(trim($name));
            if ($entriesByKey->has($key)) {$entry = $entriesByKey[$key];
                $val =$entry->petition_answer ?? null;
                if (filled($val)) {
                    return $val;
                }
            }
        }
        return null;
    };

    $notSpecified = lexicon('label_common_NotSpecified', 'Not Specified');
    $noData = lexicon('msg_common_NoData', 'No information provided.');

    $personaName = $targetPersona?->persona_name ?? $targetPersona?->persona_designation ?? $petition?->proposed_persona_name ?? $getEntryValue(['Persona Name', 'Name']) ?? $notSpecified;
    $insigniaName =$targetPersona?->insignia?->insignia_title ?? lexicon('label_Demographics_Unranked', 'Unranked');
    $assignmentName =$targetPersona?->assignment?->assignment_title ?? lexicon('label_Demographics_Unassigned', 'Unassigned');

    $species =$getEntryValue(['Species / Heritage', 'Species', 'Heritage']) ?? $notSpecified;
    $age =$getEntryValue(['Age']) ?? $notSpecified;
    $gender =$getEntryValue(['Gender']) ?? $notSpecified;
    $origin =$getEntryValue(['Place of Origin', 'Origin']) ?? $notSpecified;
    $height =$getEntryValue(['Height & Build', 'Height']) ?? $notSpecified;
    $appearance =$getEntryValue(['Hair & Eye Colour', 'Hair & Eye Color', 'Hair & Eyes']) ?? $notSpecified;

    $personality = $getEntryValue(['Personality & Traits', 'Personality', 'Traits']) ?? $noData;
    $strengths = $getEntryValue(['Strengths & Weaknesses', 'Strengths', 'Weaknesses']) ?? $noData;
    $hobbies = $getEntryValue(['Interests & Hobbies', 'Interests', 'Hobbies']) ?? $noData;
@endphp

<div class="bg-panel border-panel rounded-panel shadow-panel flex flex-col overflow-hidden mb-6 widget-persona-metadata-summary" x-data="{ activeTab: 'personality' }">
    @if($title)
        <div class="bg-panel-header text-h border-b-panel p-panel-header font-bold flex flex-row flex-wrap justify-between items-center gap-md">
            <span class="text-xl font-bold text-accent">{{ $title }}</span>
            <i class="bi bi-person-badge text-accent"
               x-tooltip="'{{ addslashes(lexicon('tooltip_PersonaSummary_Header', 'Persona identity, physical vitals, and psychological profile')) }}'"
               aria-label="{{ lexicon('tooltip_PersonaSummary_Header', 'Persona identity, physical vitals, and psychological profile') }}"></i>
        </div>
    @endif

    <div class="p-panel flex flex-col gap-md">
        
        {{-- Primary Identity Banner (Persona Name, Insignia, Assignment, Duty Status) --}}
        <div class="bg-panel-sub border-panel rounded-panel p-4 flex flex-row flex-wrap justify-between items-center gap-md">
            <div class="flex flex-col gap-xs">
                <span class="text-xs font-bold text-input-muted uppercase tracking-wider">{{ lexicon('label_Demographics_PersonaName', 'Persona Name') }}</span>
                <span class="text-lg font-bold text-accent">{{ $personaName }}</span>
            </div>
            <div class="flex flex-row flex-wrap gap-md items-center">
                <div class="flex flex-col gap-xs">
                    <span class="text-xs font-bold text-input-muted uppercase tracking-wider">{{ lexicon('label_Demographics_Rank', 'Insignia') }}</span>
                    <span class="text-sm font-bold text-p"
                          x-tooltip="'{{ addslashes(lexicon('tooltip_common_PersonaRank', 'Assigned Insignia')) }}'">{{ $insigniaName }}</span>
                </div>
                <div class="flex flex-col gap-xs">
                    <span class="text-xs font-bold text-input-muted uppercase tracking-wider">{{ lexicon('label_Demographics_Position', 'Assignment') }}</span>
                    <span class="text-sm font-bold text-p"
                          x-tooltip="'{{ addslashes(lexicon('tooltip_Demographics_DutyPosition', 'Assigned position')) }}'">{{ $assignmentName }}</span>
                </div>
                <div class="flex flex-col gap-xs">
                    <span class="text-xs font-bold text-input-muted uppercase tracking-wider">{{ lexicon('label_Sidebar_DutyStatus', 'Duty Status') }}</span>
                    <div>
                        <x-badge :variant="$statusBadge"
                                 x-tooltip="'{{ addslashes(lexicon('tooltip_Sidebar_CurrentDutyStatus', 'Current persona duty status')) }}'"
                                 aria-label="{{ lexicon('tooltip_Sidebar_CurrentDutyStatus', 'Current persona duty status') }}">
                            {{ $statusLabel }}
                        </x-badge>
                    </div>
                </div>
            </div>
        </div>

        {{-- Account Writer & Social Channels Row --}}
        @if($user || $twitter \vert{}\vert{}$facebookUrl)
            <div class="grid grid-cols-1 md-grid-cols-2 gap-md w-full">
                @if($user)
                    <div class="bg-panel-sub border-panel rounded-panel p-3 flex flex-col gap-xs">
                        <span class="text-xs font-bold text-input-muted uppercase tracking-wider">{{ lexicon('label_Sidebar_Writer', 'Account Writer') }}</span>
                        <span class="text-sm font-bold text-p">{{ $user->name }}</span>
                    </div>
                @endif

                @if($twitter \vert{}\vert{}$facebookUrl)
                    <div class="bg-panel-sub border-panel rounded-panel p-3 flex flex-col gap-xs">
                        <span class="text-xs font-bold text-input-muted uppercase tracking-wider">{{ lexicon('label_Sidebar_Socials', 'Social Channels') }}</span>
                        <div class="flex flex-row flex-wrap gap-sm items-center mt-1">
                            @if($twitter)
                                <a href="https://twitter.com/{{ ltrim($twitter, '@') }}"
                                   target="_blank"
                                   rel="noopener"
                                   class="text-xs text-accent hover-underline flex items-center gap-1"
                                   x-tooltip="'{{ addslashes(lexicon('tooltip_Sidebar_TwitterProfile', 'View writer on X (Twitter)')) }}'"
                                   aria-label="{{ lexicon('tooltip_Sidebar_TwitterProfile', 'View writer on X (Twitter)') }}">
                                    <i class="bi bi-twitter-x" aria-hidden="true"></i>
                                    <span>{{ '@' . ltrim($twitter, '@') }}</span>
                                </a>
                            @endif
                            @if($facebookUrl)
                                <a href="{{ $facebookUrl }}"
                                   target="_blank"
                                   rel="noopener"
                                   class="text-xs text-accent hover-underline flex items-center gap-1"
                                   x-tooltip="'{{ addslashes(lexicon('tooltip_Sidebar_FacebookProfile', 'View writer on Facebook')) }}'"
                                   aria-label="{{ lexicon('tooltip_Sidebar_FacebookProfile', 'View writer on Facebook') }}">
                                    <i class="bi bi-facebook" aria-hidden="true"></i>
                                    <span>{{ lexicon('label_Sidebar_FacebookLink', 'Facebook') }}</span>
                                </a>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        @endif

        {{-- Physical Vitals Grid --}}
        <div class="grid grid-cols-1 md-grid-cols-2 lg-grid-cols-3 gap-md w-full">
            
            {{-- Species / Heritage --}}
            <div class="bg-panel-sub border-panel rounded-panel p-3 flex flex-col gap-xs">
                <span class="text-xs font-bold text-input-muted uppercase tracking-wider">{{ lexicon('label_Demographics_Species', 'Species / Heritage') }}</span>
                <span class="text-sm font-bold text-p">{{ $species }}</span>
            </div>

            {{-- Age --}}
            <div class="bg-panel-sub border-panel rounded-panel p-3 flex flex-col gap-xs">
                <span class="text-xs font-bold text-input-muted uppercase tracking-wider">{{ lexicon('label_Demographics_Age', 'Age') }}</span>
                <span class="text-sm font-bold text-p">{{ $age }}</span>
            </div>

            {{-- Gender --}}
            <div class="bg-panel-sub border-panel rounded-panel p-3 flex flex-col gap-xs">
                <span class="text-xs font-bold text-input-muted uppercase tracking-wider">{{ lexicon('label_Demographics_Gender', 'Gender') }}</span>
                <span class="text-sm font-bold text-p">{{ $gender }}</span>
            </div>

            {{-- Place of Origin --}}
            <div class="bg-panel-sub border-panel rounded-panel p-3 flex flex-col gap-xs">
                <span class="text-xs font-bold text-input-muted uppercase tracking-wider">{{ lexicon('label_Demographics_Origin', 'Place of Origin') }}</span>
                <span class="text-sm font-bold text-p">{{ $origin }}</span>
            </div>

            {{-- Height & Build --}}
            <div class="bg-panel-sub border-panel rounded-panel p-3 flex flex-col gap-xs">
                <span class="text-xs font-bold text-input-muted uppercase tracking-wider">{{ lexicon('label_Demographics_HeightBuild', 'Height & Build') }}</span>
                <span class="text-sm font-bold text-p">{{ $height }}</span>
            </div>

            {{-- Hair & Eye Colour --}}
            <div class="bg-panel-sub border-panel rounded-panel p-3 flex flex-col gap-xs">
                <span class="text-xs font-bold text-input-muted uppercase tracking-wider">{{ lexicon('label_Demographics_HairEyeColour', 'Hair & Eye Colour') }}</span>
                <span class="text-sm font-bold text-p">{{ $appearance }}</span>
            </div>

        </div>

        {{-- Psychological Profile Tabs Header --}}
        <div class="flex flex-row flex-wrap gap-xs p-3 bg-panel-sub border-panel rounded-panel mt-2">
            <x-button type="button"
                      size="sm"
                      ::class="activeTab === 'personality' ? 'bg-primary text-input border-transparent shadow-btn hover-brightness-110' : 'bg-secondary text-btn-secondary border-secondary hover-bg-border'"
                      @click="activeTab = 'personality'"
                      x-tooltip="'{{ addslashes(lexicon('tooltip_Personality_TabTraits', 'View personality and behavioural traits')) }}'"
                      aria-label="{{ lexicon('tooltip_Personality_TabTraits', 'View personality and behavioural traits') }}">
                {{ lexicon('label_Personality_TabTraits', 'Personality & Traits') }}
            </x-button>

            <x-button type="button"
                      size="sm"
                      ::class="activeTab === 'strengths' ? 'bg-primary text-input border-transparent shadow-btn hover-brightness-110' : 'bg-secondary text-btn-secondary border-secondary hover-bg-border'"
                      @click="activeTab = 'strengths'"
                      x-tooltip="'{{ addslashes(lexicon('tooltip_Personality_TabStrengths', 'View persona strengths and weaknesses')) }}'"
                      aria-label="{{ lexicon('tooltip_Personality_TabStrengths', 'View persona strengths and weaknesses') }}">
                {{ lexicon('label_Personality_TabStrengths', 'Strengths & Weaknesses') }}
            </x-button>

            <x-button type="button"
                      size="sm"
                      class="flex-grow"
                      ::class="activeTab === 'hobbies' ? 'bg-primary text-input border-transparent shadow-btn hover-brightness-110' : 'bg-secondary text-btn-secondary border-secondary hover-bg-border'"
                      @click="activeTab = 'hobbies'"
                      x-tooltip="'{{ addslashes(lexicon('tooltip_Personality_TabHobbies', 'View interests, hobbies and recreations')) }}'"
                      aria-label="{{ lexicon('tooltip_Personality_TabHobbies', 'View interests, hobbies and recreations') }}">
                {{ lexicon('label_Personality_TabHobbies', 'Interests & Hobbies') }}
            </x-button>
        </div>

        {{-- Psychological Profile Tab Contents --}}
        <div class="flex flex-col gap-md">
            
            {{-- Personality & Traits --}}
            <div x-show="activeTab === 'personality'" class="flex flex-col gap-xs">
                <span class="text-xs font-bold text-input-muted uppercase tracking-wider">{{ lexicon('label_Personality_TraitsHeading', 'Personality & Behavioural Traits') }}</span>
                <div class="p-4 bg-panel-sub border-panel rounded-panel text-sm text-p leading-relaxed whitespace-pre-line">
                    {!! nl2br(e($personality)) !!}
                </div>
            </div>

            {{-- Strengths & Weaknesses --}}
            <div x-cloak x-show="activeTab === 'strengths'" class="flex flex-col gap-xs">
                <span class="text-xs font-bold text-input-muted uppercase tracking-wider">{{ lexicon('label_Personality_StrengthsHeading', 'Strengths & Weaknesses') }}</span>
                <div class="p-4 bg-panel-sub border-panel rounded-panel text-sm text-p leading-relaxed whitespace-pre-line">
                    {!! nl2br(e($strengths)) !!}
                </div>
            </div>

            {{-- Interests & Hobbies --}}
            <div x-cloak x-show="activeTab === 'hobbies'" class="flex flex-col gap-xs">
                <span class="text-xs font-bold text-input-muted uppercase tracking-wider">{{ lexicon('label_Personality_HobbiesHeading', 'Interests, Hobbies & Recreations') }}</span>
                <div class="p-4 bg-panel-sub border-panel rounded-panel text-sm text-p leading-relaxed whitespace-pre-line">
                    {!! nl2br(e($hobbies)) !!}
                </div>
            </div>

        </div>

    </div>
</div>