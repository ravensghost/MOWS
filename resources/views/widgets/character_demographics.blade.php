{{-- 
@WidgetManifest({
    "name": "Demographics & Vitals",
    "description": "Displays character identity, rank, position, species, age, and physical attributes.",
    "icon": "bi-person-badge",
    "fields": {
        "title": { "type": "text", "label": "Widget Title", "default": "Demographics & Vitals" }
    }
})
--}}
@php
    $options = is_string($widget->pivot->instance_options) ? json_decode($widget->pivot->instance_options, true) : ($widget->pivot->instance_options ?? []);
    $rawTitle = trim($options['title'] ?? '');
    $title = $rawTitle !== '' ? $rawTitle : lexicon('label_Demographics_DefaultTitle', 'Demographics & Vitals');

    // Retrieve eager-loaded candidate petition answers linked to this persona without N+1 queries
    $petition = null;
    if ($character) {
        if ($character->relationLoaded('petition')) {
            $petition = $character->petition;
        } elseif ($character->relationLoaded('application')) {
            $petition = $character->application;
        } else {
            $petition = \App\Models\CandidatePetition::with('answers')
                ->where('persona_id', $character->id)
                ->first();
        }
    }

    $answersCollection = ($petition && $petition->relationLoaded('answers'))
        ? $petition->answers
        : ($petition ? $petition->answers()->get() : collect());

    $metadataMap = \App\Models\Metadata::all()->keyBy('id');

    $answers = $answersCollection->keyBy(function ($item) use ($metadataMap) {
        $meta = ($item->relationLoaded('metadata') ? $item->metadata : null)
            ?? ($item->relationLoaded('formField') ? $item->formField : null)
            ?? $metadataMap->get($item->metadata_id);

        $fieldName = $meta->metadata_label ?? $meta->metadata_key ?? $meta->name ?? '';
        return strtolower(trim($fieldName));
    });

    // Helper to pull petition_answer (or legacy answer) by field name or fallback
    $getAnswer = function ($names) use ($answers) {
        foreach ((array) $names as $name) {
            $key = strtolower(trim($name));
            if ($answers->has($key)) {
                $entry = $answers[$key];
                $val = $entry->petition_answer ?? $entry->answer ?? null;
                if (filled($val)) {
                    return $val;
                }
            }
        }
        return null;
    };

    $notSpecified = lexicon('label_common_NotSpecified', 'Not Specified');

    $charName = $character?->name ?? $petition?->proposed_persona_name ?? $getAnswer(['Character Name', 'Name']) ?? $notSpecified;
    $rankName = $character?->rank?->name ?? lexicon('label_Demographics_Unranked', 'Unranked');
    $positionName = $character?->position?->name ?? lexicon('label_Demographics_Unassigned', 'Unassigned');

    $species = $getAnswer(['Species / Heritage', 'Species', 'Heritage']) ?? $notSpecified;
    $age = $getAnswer(['Age']) ?? $notSpecified;
    $gender = $getAnswer(['Gender']) ?? $notSpecified;
    $origin = $getAnswer(['Place of Origin', 'Origin']) ?? $notSpecified;
    $height = $getAnswer(['Height & Build', 'Height']) ?? $notSpecified;
    $appearance = $getAnswer(['Hair & Eye Colour', 'Hair & Eye Color', 'Hair & Eyes']) ?? $notSpecified;
@endphp

<div class="bg-panel border-panel rounded-panel shadow-panel flex flex-col overflow-hidden mb-6 widget-character-demographics" x-data>
    @if($title)
        <div class="bg-panel-header text-h border-b-panel p-panel-header font-bold flex flex-row flex-wrap justify-between items-center gap-md">
            <span class="text-xl font-bold text-accent">{{ $title }}</span>
            <i class="bi bi-person-badge text-accent"
               x-tooltip="'{{ addslashes(lexicon('tooltip_Demographics_VitalsHeader', 'Character identity and physical vitals')) }}'"
               aria-label="{{ lexicon('tooltip_Demographics_VitalsHeader', 'Character identity and physical vitals') }}"></i>
        </div>
    @endif

    <div class="p-panel flex flex-col gap-md">
        
        {{-- Primary Identity Banner (Name, Rank, Position) --}}
        <div class="bg-panel-sub border-panel rounded-panel p-4 flex flex-row flex-wrap justify-between items-center gap-md">
            <div class="flex flex-col gap-xs">
                <span class="text-xs font-bold text-input-muted uppercase tracking-wider">{{ lexicon('label_Demographics_CharacterName', 'Character Name') }}</span>
                <span class="text-lg font-bold text-accent">{{ $charName }}</span>
            </div>
            <div class="flex flex-row flex-wrap gap-md items-center">
                <div class="flex flex-col gap-xs">
                    <span class="text-xs font-bold text-input-muted uppercase tracking-wider">{{ lexicon('label_Demographics_Rank', 'Rank') }}</span>
                    <span class="text-sm font-bold text-p"
                          x-tooltip="'{{ addslashes(lexicon('tooltip_common_CharacterRank', 'Character Rank')) }}'">{{ $rankName }}</span>
                </div>
                <div class="flex flex-col gap-xs">
                    <span class="text-xs font-bold text-input-muted uppercase tracking-wider">{{ lexicon('label_Demographics_Position', 'Duty Position') }}</span>
                    <span class="text-sm font-bold text-p"
                          x-tooltip="'{{ addslashes(lexicon('tooltip_Demographics_DutyPosition', 'Assigned duty position')) }}'">{{ $positionName }}</span>
                </div>
            </div>
        </div>

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
    </div>
</div>