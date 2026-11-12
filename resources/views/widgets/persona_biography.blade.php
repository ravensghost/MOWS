{{-- 
@WidgetManifest({
    "name": "Persona Biography",
    "description": "Displays the persona narrative history, background, and service biography.",
    "icon": "bi-journal-richtext",
    "fields": {
        "title": { "type": "text", "label": "Widget Title", "default": "Persona Biography" }
    }
})
--}}
@php
    $options = is_string($widget->pivot->instance_options ?? null)
        ? json_decode($widget->pivot->instance_options, true)
        : ($widget->pivot->instance_options ?? []);

    $rawTitle = trim($options['title'] ?? '');
    $title = $rawTitle !== '' ? $rawTitle : lexicon('label_PersonaBio_DefaultTitle', 'Persona Biography');

    $targetPersona = $persona ?? $character ?? null;
    $petition = null;

    if ($targetPersona) {
        if ($targetPersona->relationLoaded('petition')) {
            $petition = $targetPersona->petition;
        } else {
            $petition = \App\Models\CandidatePetition::with('entries')
                ->where('persona_id', $targetPersona->id)
                ->first();
        }
    }

    $entriesCollection = collect();
    if ($petition) {
        if ($petition->relationLoaded('entries')) {
            $entriesCollection = $petition->entries;
        } elseif ($petition->relationLoaded('answers')) {
            $entriesCollection = $petition->answers;
        } elseif (method_exists($petition, 'entries')) {
            $entriesCollection = $petition->entries()->get();
        } elseif (method_exists($petition, 'answers')) {
            $entriesCollection = $petition->answers()->get();
        }
    }

    $metadataMap = \App\Models\Metadata::all()->keyBy('id');

    $entriesByKey = $entriesCollection->keyBy(function ($item) use ($metadataMap) {
        $meta = ($item->relationLoaded('metadata') ? $item->metadata : null)
            ?? $metadataMap->get($item->metadata_id);

        $fieldName = $meta->metadata_title ?? $meta->metadata_label ?? $meta->metadata_key ?? '';
        return strtolower(trim($fieldName));
    });

    $getEntryValue = function ($names) use ($entriesByKey) {
        foreach ((array) $names as $name) {
            $key = strtolower(trim($name));
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

    $noBioText = lexicon('msg_PersonaBio_NoContent', 'No biographical narrative has been recorded for this persona.');

    $biographyContent = $targetPersona?->persona_biography
        ?? $targetPersona?->biography
        ?? $getEntryValue(['Biography', 'Persona Biography', 'Personal History', 'History', 'Background', 'Service History'])
        ?? null;
@endphp

<div class="bg-panel border border-panel rounded-panel shadow-panel flex flex-col overflow-hidden mb-6 widget-persona-biography" x-data>
    @if($title)
        <div class="bg-panel-header text-h border-b-panel p-panel-header font-bold flex flex-row flex-wrap justify-between items-center gap-md">
            <div class="flex flex-row items-center gap-xs">
                <i class="bi bi-journal-richtext text-accent"></i>
                <span class="text-xl font-bold text-accent">{{ $title }}</span>
            </div>
            <i class="bi bi-book text-accent"
               x-tooltip="'{{ addslashes(lexicon('tooltip_PersonaBio_Header', 'Persona narrative history and background')) }}'"
               aria-label="{{ lexicon('tooltip_PersonaBio_Header', 'Persona narrative history and background') }}"></i>
        </div>
    @endif

    <div class="p-panel flex flex-col gap-md">
        @if(filled($biographyContent))
            <div class="p-4 bg-panel-sub border border-panel rounded-panel text-sm text-p leading-relaxed whitespace-pre-line">
                {!! nl2br(e($biographyContent)) !!}
            </div>
        @else
            <div class="bg-panel-sub border-dashed-panel rounded-panel p-panel text-center flex flex-col items-center justify-center gap-xs">
                <i class="bi bi-journal-x text-2xl text-muted"></i>
                <span class="text-sm text-muted italic">{{ $noBioText }}</span>
            </div>
        @endif
    </div>
</div>