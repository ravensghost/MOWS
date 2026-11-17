{{-- resources/views/modules/persona_metadata_summary.blade.php --}}
{{-- @WidgetManifest({ "name": "Persona Metadata Summary", "description": "Displays persona identity, insignia, assignment, duty status, owning user details, physical vitals, and psychological profile.", "icon": "bi-person-badge", "fields": { "title": { "type": "text", "label": "Module Title", "default": "Persona Metadata Summary" } } }) --}}
@php
    $rawOptions = $moduleSettings
        ?? $module?->pivot?->book_config
        ?? $module?->pivot?->instance_options
        ?? $widget?->pivot?->instance_options
        ?? [];
    $options = is_string($rawOptions) ? (json_decode($rawOptions, true) ?: []) : (is_array($rawOptions) ? $rawOptions : []);

    $rawTitle = trim((string) ($options['title'] ?? ''));
    $title = $rawTitle !== '' ? $rawTitle : lexicon('label_PersonaSummary_DefaultTitle', 'Persona Metadata Summary');

    $targetPersona = $persona ?? $character ?? null;
    $petition = null;

    if ($targetPersona) {
        if ($targetPersona->relationLoaded('candidatePetition')) {
            $petition = $targetPersona->candidatePetition;
        } else {
            $petition = \App\Models\CandidatePetition::with('entries')
                ->where('persona_id', $targetPersona->id)
                ->first();
        }
    }

    $user = $targetPersona?->user;
    $personaStatus = strtolower((string) ($targetPersona?->persona_status ?? 'active'));

    $statusBadge = match($personaStatus) {
        'active'                    => 'success',
        'loa'                       => 'warning',
        'eloa'                      => 'danger',
        'honoured_dead', 'memorial' => 'primary',
        default                     => 'primary',
    };

    $statusLabel = match($personaStatus) {
        'active'        => lexicon('label_ServiceRecords_StatusActive', 'Active'),
        'loa'           => lexicon('label_ServiceRecords_StatusLoa', 'Leave of Absence (LOA)'),
        'eloa'          => lexicon('label_ServiceRecords_StatusEloa', 'Extended Leave of Absence (ELOA)'),
        'honoured_dead' => lexicon('label_ServiceRecords_StatusHonouredDead', 'Honoured Dead'),
        'memorial'      => lexicon('label_ServiceRecords_StatusMemorial', 'Memorial'),
        default         => strtoupper(str_replace('_', ' ', $personaStatus)),
    };

    $twitter = $user?->twitter_handle ?? null;
    $facebook = $user?->facebook_handle ?? null;
    $facebookUrl = $facebook && \Illuminate\Support\Str::startsWith($facebook, ['http://', 'https://'])
        ? $facebook
        : ($facebook ? 'https://facebook.com/' . ltrim($facebook, '/') : null);

    $entriesCollection = collect();
    if ($petition) {
        if ($petition->relationLoaded('entries')) {
            $entriesCollection = $petition->entries;
        } elseif ($petition->relationLoaded('answers')) {
            $entriesCollection = $petition->answers;
        } else {
            $entriesCollection = $petition->entries()->get();
        }
    }

    $metadataMap = \App\Models\Metadata::all()->keyBy('id');

    $entriesByKey = $entriesCollection->keyBy(function ($item) use ($metadataMap) {
        $meta = ($item->relationLoaded('metadata') ? $item->metadata : null)
            ?? $metadataMap->get($item->metadata_id);

        $fieldName = $meta->metadata_title ?? $meta->metadata_label ?? $meta->metadata_key ?? '';
        return strtolower(trim((string) $fieldName));
    });

    $getEntryValue = function ($names) use ($entriesByKey) {
        foreach ((array) $names as $name) {
            $key = strtolower(trim((string) $name));
            if ($entriesByKey->has($key)) {
                $entry = $entriesByKey[$key];
                $val = $entry->petition_answer ?? null;
                if (filled($val)) {
                    return $val;
                }
            }
        }
        return null;
    };

    $notSpecified = lexicon('label_common_NotSpecified', 'Not Specified');
    $noData = lexicon('msg_common_NoData', 'No information provided.');

    $personaName = $targetPersona?->persona_name ?? $petition?->proposed_persona_name ?? $getEntryValue(['Persona Name']) ?? $notSpecified;
    $insigniaName = $targetPersona?->insignia?->insignia_title ?? lexicon('label_Demographics_Unranked', 'Unranked');
    $assignmentName = $targetPersona?->assignment?->assignment_title ?? lexicon('label_Demographics_Unassigned', 'Unassigned');

    $species = $getEntryValue(['Species / Heritage', 'Species', 'Heritage']) ?? $notSpecified;
    $age = $getEntryValue(['Age']) ?? $notSpecified;
    $gender = $getEntryValue(['Gender']) ?? $notSpecified;
    $origin = $getEntryValue(['Place of Origin', 'Origin']) ?? $notSpecified;
    $height = $getEntryValue(['Height & Build', 'Height']) ?? $notSpecified;
    $appearance = $getEntryValue(['Hair & Eye Colour', 'Hair & Eyes']) ?? $notSpecified;

    $personality = $getEntryValue(['Personality & Traits', 'Personality', 'Traits']) ?? $noData;
    $strengths = $getEntryValue(['Strengths & Weaknesses', 'Strengths', 'Weaknesses']) ?? $noData;
    $hobbies = $getEntryValue(['Interests & Hobbies', 'Interests', 'Hobbies']) ?? $noData;

    $profileTabs = [
        'personality' => lexicon('label_Personality_TabTraits', 'Personality & Traits'),
        'strengths'   => lexicon('label_Personality_TabStrengths', 'Strengths & Weaknesses'),
        'hobbies'     => lexicon('label_Personality_TabHobbies', 'Interests & Hobbies'),
    ];
@endphp

<x-tab type="accordion"
       :title="$title"
       icon="bi-person-badge"
       :open="true">
    {{-- Primary Identity Banner (Persona Name, Insignia, Assignment, Duty Status) --}}
    <div class="bg-panel-sub border-panel border-rounded p-4 flex flex-row flex-wrap justify-between items-center gap-md">
        <div class="flex flex-col gap-xs">
            <span class="text-xs font-bold text-input-muted uppercase text-wider">{{ lexicon('label_Demographics_PersonaName', 'Persona Name') }}</span>
            <span class="text-lg font-bold text-accent">{{ $personaName }}</span>
        </div>

        <div class="flex flex-row flex-wrap gap-md items-center">
            <div class="flex flex-col gap-xs">
                <span class="text-xs font-bold text-input-muted uppercase text-wider">{{ lexicon('label_Demographics_Rank', 'Insignia') }}</span>
                <span class="text-sm font-bold text-p"
                      x-tooltip="'{{ addslashes(lexicon('tooltip_common_PersonaRank', 'Assigned Insignia')) }}'"
                      title="{{ lexicon('tooltip_common_PersonaRank', 'Assigned Insignia') }}">{{ $insigniaName }}</span>
            </div>

            <div class="flex flex-col gap-xs">
                <span class="text-xs font-bold text-input-muted uppercase text-wider">{{ lexicon('label_Demographics_Position', 'Assignment') }}</span>
                <span class="text-sm font-bold text-p"
                      x-tooltip="'{{ addslashes(lexicon('tooltip_Demographics_DutyPosition', 'Assigned position')) }}'"
                      title="{{ lexicon('tooltip_Demographics_DutyPosition', 'Assigned position') }}">{{ $assignmentName }}</span>
            </div>

            <div class="flex flex-col gap-xs">
                <span class="text-xs font-bold text-input-muted uppercase text-wider">{{ lexicon('label_Sidebar_DutyStatus', 'Duty Status') }}</span>
                <div>
                    <x-badge type="pill"
                             :variant="$statusBadge"
                             :tooltip="lexicon('tooltip_Sidebar_CurrentDutyStatus', 'Current persona duty status')">
                        {{ $statusLabel }}
                    </x-badge>
                </div>
            </div>
        </div>
    </div>

    {{-- Account Writer & Social Channels Row --}}
    @if($user || $twitter || $facebookUrl)
        <div class="grid grid-cols-1 md-grid-cols-2 gap-md w-full">
            @if($user)
                <x-tab type="sub" :title="lexicon('label_Sidebar_Writer', 'Account Writer')" icon="bi-person-circle">
                    <span class="text-sm font-bold text-p">{{ $user->user_handle ?? $user->name }}</span>
                </x-tab>
            @endif

            @if($twitter || $facebookUrl)
                <x-tab type="sub" :title="lexicon('label_Sidebar_Socials', 'Social Channels')" icon="bi-share">
                    <div class="flex flex-row flex-wrap gap-sm items-center">
                        @if($twitter)
                            <a href="https://twitter.com/{{ ltrim($twitter, '@') }}"
                               target="_blank"
                               rel="noopener noreferrer"
                               class="text-xs text-accent hover-text-h flex flex-row items-center gap-xs no-underline transition-colors"
                               x-tooltip="'{{ addslashes(lexicon('tooltip_Sidebar_TwitterProfile', 'View writer on X (Twitter)')) }}'"
                               title="{{ lexicon('tooltip_Sidebar_TwitterProfile', 'View writer on X (Twitter)') }}"
                               aria-label="{{ lexicon('tooltip_Sidebar_TwitterProfile', 'View writer on X (Twitter)') }}">
                                <i class="bi bi-twitter-x" aria-hidden="true"></i>
                                <span>{{ '@' . ltrim($twitter, '@') }}</span>
                            </a>
                        @endif

                        @if($facebookUrl)
                            <a href="{{ $facebookUrl }}"
                               target="_blank"
                               rel="noopener noreferrer"
                               class="text-xs text-accent hover-text-h flex flex-row items-center gap-xs no-underline transition-colors"
                               x-tooltip="'{{ addslashes(lexicon('tooltip_Sidebar_FacebookProfile', 'View writer on Facebook')) }}'"
                               title="{{ lexicon('tooltip_Sidebar_FacebookProfile', 'View writer on Facebook') }}"
                               aria-label="{{ lexicon('tooltip_Sidebar_FacebookProfile', 'View writer on Facebook') }}">
                                <i class="bi bi-facebook" aria-hidden="true"></i>
                                <span>{{ lexicon('label_Sidebar_FacebookLink', 'Facebook') }}</span>
                            </a>
                        @endif
                    </div>
                </x-tab>
            @endif
        </div>
    @endif

    {{-- Physical Vitals Grid --}}
    <div class="grid grid-cols-1 md-grid-cols-2 lg-grid-cols-3 gap-md w-full">
        <x-tab type="sub" :title="lexicon('label_Demographics_Species', 'Species / Heritage')">
            <span class="text-sm font-bold text-p">{{ $species }}</span>
        </x-tab>

        <x-tab type="sub" :title="lexicon('label_Demographics_Age', 'Age')">
            <span class="text-sm font-bold text-p">{{ $age }}</span>
        </x-tab>

        <x-tab type="sub" :title="lexicon('label_Demographics_Gender', 'Gender')">
            <span class="text-sm font-bold text-p">{{ $gender }}</span>
        </x-tab>

        <x-tab type="sub" :title="lexicon('label_Demographics_Origin', 'Place of Origin')">
            <span class="text-sm font-bold text-p">{{ $origin }}</span>
        </x-tab>

        <x-tab type="sub" :title="lexicon('label_Demographics_HeightBuild', 'Height & Build')">
            <span class="text-sm font-bold text-p">{{ $height }}</span>
        </x-tab>

        <x-tab type="sub" :title="lexicon('label_Demographics_HairEyeColour', 'Hair & Eye Colour')">
            <span class="text-sm font-bold text-p">{{ $appearance }}</span>
        </x-tab>
    </div>

    {{-- Psychological Profile Tabs --}}
    <x-tab type="tabs"
           activeTab="personality"
           :tabs="$profileTabs">
        <div x-show="activeTab === 'personality'" class="flex flex-col gap-xs">
            <span class="text-xs font-bold text-input-muted uppercase text-wider">{{ lexicon('label_Personality_TraitsHeading', 'Personality & Behavioural Traits') }}</span>
            <div class="p-4 bg-panel-sub border-panel border-rounded text-sm text-p leading-relaxed whitespace-pre-line">
                {!! nl2br(e($personality)) !!}
            </div>
        </div>

        <div x-cloak x-show="activeTab === 'strengths'" class="flex flex-col gap-xs">
            <span class="text-xs font-bold text-input-muted uppercase text-wider">{{ lexicon('label_Personality_StrengthsHeading', 'Strengths & Weaknesses') }}</span>
            <div class="p-4 bg-panel-sub border-panel border-rounded text-sm text-p leading-relaxed whitespace-pre-line">
                {!! nl2br(e($strengths)) !!}
            </div>
        </div>

        <div x-cloak x-show="activeTab === 'hobbies'" class="flex flex-col gap-xs">
            <span class="text-xs font-bold text-input-muted uppercase text-wider">{{ lexicon('label_Personality_HobbiesHeading', 'Interests, Hobbies & Recreations') }}</span>
            <div class="p-4 bg-panel-sub border-panel border-rounded text-sm text-p leading-relaxed whitespace-pre-line">
                {!! nl2br(e($hobbies)) !!}
            </div>
        </div>
    </x-tab>
</x-tab>