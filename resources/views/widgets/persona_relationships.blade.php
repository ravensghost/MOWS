{{-- 
@WidgetManifest({
    "name": "Persona Relationships",
    "description": "Displays interpersonal connections, family ties, and known associates for this persona.",
    "icon": "bi-diagram-3",
    "fields": {
        "title": { "type": "text", "label": "Widget Title", "default": "Persona Relationships" }
    }
})
--}}
@php
    $options = is_string($widget->pivot->instance_options ?? null)
        ? json_decode($widget->pivot->instance_options, true)
        : ($widget->pivot->instance_options ?? []);

    $rawTitle = trim($options['title'] ?? '');
    $title = $rawTitle !== '' ? $rawTitle : lexicon('label_PersonaRelationships_DefaultTitle', 'Persona Relationships');

    $targetPersona = $persona ?? $character ?? null;
    $relationships = collect();

    if ($targetPersona) {
        if ($targetPersona->relationLoaded('relationships')) {
            $relationships = $targetPersona->relationships;
        } elseif ($targetPersona->relationLoaded('personaRelationships')) {
            $relationships = $targetPersona->personaRelationships;
        } elseif (method_exists($targetPersona, 'relationships')) {
            $relationships = $targetPersona->relationships()->get();
        } elseif (method_exists($targetPersona, 'personaRelationships')) {
            $relationships = $targetPersona->personaRelationships()->get();
        } elseif (method_exists($targetPersona, 'metadata')) {
            $metaCollection = $targetPersona->relationLoaded('metadata')
                ? $targetPersona->metadata
                : $targetPersona->metadata()->get();

            $relationships = $metaCollection->filter(function ($item) {
                $key = $item->metadata_key ?? $item->key ?? '';
                return in_array($key, ['relationship', 'persona_relationship'], true);
            })->values();
        }
    }
@endphp

<div class="bg-panel border border-panel rounded-panel shadow-panel flex flex-col overflow-hidden mb-6 widget-persona-relationships" x-data>
    @if($title)
        <div class="bg-panel-header text-h border-b-panel p-panel-header font-bold flex flex-row flex-wrap justify-between items-center gap-md">
            <div class="flex flex-row items-center gap-xs">
                <i class="bi bi-diagram-3 text-accent"></i>
                <span class="text-xl font-bold text-accent">{{ $title }}</span>
            </div>
            <i class="bi bi-people text-accent"
               x-tooltip="'{{ addslashes(lexicon('tooltip_PersonaRelationships_Header', 'Interpersonal links and relationships')) }}'"
               aria-label="{{ lexicon('tooltip_PersonaRelationships_Header', 'Interpersonal links and relationships') }}"></i>
        </div>
    @endif

    <div class="p-panel flex flex-col gap-md">
        @if($relationships->isNotEmpty())
            <div class="grid grid-cols-1 md-grid-cols-2 gap-md w-full">
                @foreach($relationships as $rel)
                    @php
                        $relatedPersona = $rel->relatedPersona ?? null;
                        $relatedName = $relatedPersona?->persona_designation
                            ?? $relatedPersona?->persona_name
                            ?? $rel->related_persona_name
                            ?? $rel->target_name
                            ?? $rel->name
                            ?? lexicon('label_common_Unknown', 'Unknown Persona');
                        $relationType = $rel->relationship_type ?? $rel->type ?? lexicon('label_PersonaRelationships_Associate', 'Associate');
                        $relationNotes = $rel->relationship_notes ?? $rel->description ?? $rel->notes ?? $rel->metadata_value ?? '';
                        $relatedId = $relatedPersona?->id ?? $rel->related_persona_id ?? null;
                        $portrait = $relatedPersona?->portrait_image ?? $rel->portrait_image ?? null;
                    @endphp
                    <div class="bg-panel-sub border border-panel rounded-panel p-3 flex flex-row items-start gap-md">
                        <div class="bg-panel-header rounded-input flex-shrink-0 flex items-center justify-center overflow-hidden w-12 h-12">
                            @if(!empty($portrait))
                                <img src="{{ asset('images/characters/' . $portrait) }}"
                                     alt="{{ $relatedName }}"
                                     class="w-full h-full object-cover">
                            @else
                                <i class="bi bi-person text-xl text-muted" aria-hidden="true"></i>
                            @endif
                        </div>

                        <div class="flex flex-col flex-grow gap-xs">
                            <div class="flex flex-row flex-wrap justify-between items-center gap-xs">
                                @if($relatedId)
                                    <a href="/bio/{{ $relatedId }}"
                                       class="text-sm font-bold text-h hover-text-accent transition-colors no-underline">
                                        {{ $relatedName }}
                                    </a>
                                @else
                                    <span class="text-sm font-bold text-h">{{ $relatedName }}</span>
                                @endif
                                <x-badge variant="primary">{{ $relationType }}</x-badge>
                            </div>
                            @if(filled($relationNotes))
                                <p class="text-xs text-p leading-relaxed m-0">{{ $relationNotes }}</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="bg-panel-sub border-dashed-panel rounded-panel p-panel text-center flex flex-col items-center justify-center gap-xs">
                <i class="bi bi-diagram-3 text-2xl text-muted"></i>
                <span class="text-sm text-muted italic">{{ lexicon('msg_PersonaRelationships_NoEntries', 'No relationships have been recorded for this persona.') }}</span>
            </div>
        @endif
    </div>
</div>