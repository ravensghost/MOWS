{{-- 
@WidgetManifest({
    "name": "Personality & Psychological Profile",
    "description": "Displays character personality traits, strengths, weaknesses, and hobbies.",
    "icon": "bi-journal-richtext",
    "fields": {
        "title": { "type": "text", "label": "Widget Title", "default": "Personality & Psychological Profile" }
    }
})
--}}
@php
    $options = is_string($widget->pivot->instance_options) ? json_decode($widget->pivot->instance_options, true) : ($widget->pivot->instance_options ?? []);
    $rawTitle = trim($options['title'] ?? '');
    $title = $rawTitle !== '' ? $rawTitle : lexicon('label_Personality_DefaultTitle', 'Personality & Psychological Profile');

    // Retrieve eager-loaded application answers linked to this character without N+1 queries
    $application = ($character && $character->relationLoaded('application'))
        ? $character->application
        : ($character?->application ?? \App\Models\Application::with('answers.formField')->where('character_id', $character?->id)->first());

    $answersCollection = ($application && $application->relationLoaded('answers'))
        ? $application->answers
        : ($application ? $application->answers()->with('formField')->get() : collect());

    $answers = $answersCollection->keyBy(function ($item) {
        return strtolower(trim($item->formField->name ?? ''));
    });

    // Helper to pull answer by field name or fallback
    $getAnswer = function ($names) use ($answers) {
        foreach ((array) $names as $name) {
            $key = strtolower(trim($name));
            if ($answers->has($key) && filled($answers[$key]->answer)) {
                return $answers[$key]->answer;
            }
        }
        return null;
    };

    $noData = lexicon('msg_common_NoData', 'No information provided.');

    $personality = $getAnswer(['Personality & Traits', 'Personality', 'Traits']) ?? $noData;
    $strengths = $getAnswer(['Strengths & Weaknesses', 'Strengths', 'Weaknesses']) ?? $noData;
    $hobbies = $getAnswer(['Interests & Hobbies', 'Interests', 'Hobbies']) ?? $noData;
@endphp

<div class="bg-panel border-panel rounded-panel shadow-panel flex flex-col overflow-hidden mb-6 widget-character-personality" x-data="{ activeTab: 'personality' }">
    @if($title)
        <div class="bg-panel-header text-h border-b-panel p-panel-header font-bold flex flex-row flex-wrap justify-between items-center gap-md">
            <span class="text-xl font-bold text-accent">{{ $title }}</span>
            <i class="bi bi-journal-richtext text-accent"
               x-tooltip="'{{ addslashes(lexicon('tooltip_Personality_ProfileHeader', 'Psychological and personality profile')) }}'"
               aria-label="{{ lexicon('tooltip_Personality_ProfileHeader', 'Psychological and personality profile') }}"></i>
        </div>
    @endif

    {{-- Tabs Header --}}
    <div class="flex flex-row flex-wrap gap-xs p-4 bg-panel-sub border-b-panel">
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
                  x-tooltip="'{{ addslashes(lexicon('tooltip_Personality_TabStrengths', 'View character strengths and weaknesses')) }}'"
                  aria-label="{{ lexicon('tooltip_Personality_TabStrengths', 'View character strengths and weaknesses') }}">
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

    {{-- Tab Contents --}}
    <div class="p-panel flex flex-col gap-md">
        
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