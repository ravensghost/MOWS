{{-- resources/views/modules/persona_biography.blade.php --}}
{{-- @WidgetManifest({ "name": "Persona Biography", "description": "Displays the persona narrative history, background, and service biography.", "icon": "bi-journal-richtext", "fields": { "title": { "type": "text", "label": "Module Title", "default": "Persona Biography" } } }) --}}
@php
    $rawOptions = $moduleSettings
        ?? $module?->pivot?->book_config
        ?? $module?->pivot?->instance_options
        ?? $widget?->pivot?->instance_options
        ?? [];
    $options = is_string($rawOptions) ? (json_decode($rawOptions, true) ?: []) : (is_array($rawOptions) ? $rawOptions : []);

    $rawTitle = trim((string) ($options['title'] ?? ''));
    $title = $rawTitle !== '' ? $rawTitle : lexicon('label_PersonaBio_DefaultTitle', 'Persona Biography');

    $targetPersona = $persona ?? $character ?? null;
    $petition = null;

    if ($targetPersona) {
        if ($targetPersona->relationLoaded('candidatePetition')) {
            $petition = $targetPersona->candidatePetition;
        } elseif ($targetPersona->relationLoaded('petition')) {
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

    $noBioText = lexicon('msg_PersonaBio_NoContent', 'No biographical narrative has been recorded for this persona.');

    $biographyContent = $targetPersona?->persona_biography
        ?? $targetPersona?->biography
        ?? $getEntryValue(['Biography', 'Persona Biography', 'Personal History', 'History', 'Background', 'Service History'])
        ?? null;
@endphp

<x-tab type="accordion"
       :title="$title"
       icon="bi-journal-richtext"
       :open="true">
    @if(filled($biographyContent))
        <div class="p-4 bg-panel-sub border-panel border-rounded text-sm text-p leading-relaxed whitespace-pre-line tiptap-content">
            {!! \Stevebauman\Purify\Facades\Purify::clean((string) $biographyContent) !!}
        </div>
    @else
        <div class="bg-panel-sub border-dashed-panel border-rounded p-panel-body text-center flex flex-col items-center justify-center gap-xs">
            <i class="bi bi-journal-x text-2xl text-muted" aria-hidden="true"></i>
            <span class="text-sm text-muted font-italic">{{ $noBioText }}</span>
        </div>
    @endif
</x-tab>