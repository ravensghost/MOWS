{{-- resources/views/modules/persona_relationships.blade.php --}}
{{-- @WidgetManifest({ "name": "Persona Relationships", "description": "Displays interpersonal connections, family ties, and known associates for this persona.", "icon": "bi-diagram-3", "fields": { "title": { "type": "text", "label": "Module Title", "default": "Persona Relationships" } } }) --}}
@php
    $rawOptions =$moduleSettings
        ?? $module?->pivot?->book_config
        ?? $module?->pivot?->instance_options
        ?? $widget?->pivot?->instance_options
        ?? [];
    $options = is_string($rawOptions) ? (json_decode($rawOptions, true) ?: []) : (is_array($rawOptions) ?$rawOptions : []);

    $rawTitle = trim((string) ($options['title'] ?? ''));$title = $rawTitle !== '' ?$rawTitle : lexicon('label_PersonaRelationships_DefaultTitle', 'Persona Relationships');

    $targetPersona = $persona ?? $character ?? null;
    $relationships = collect();

    if ($targetPersona) {
        if ($targetPersona->relationLoaded('relationships')) {
            $relationships =$targetPersona->relationships;
        } elseif ($targetPersona->relationLoaded('personaRelationships')) {
            $relationships =$targetPersona->personaRelationships;
        } elseif (method_exists($targetPersona, 'relationships')) {
            $relationships =$targetPersona->relationships()->get();
        } elseif (method_exists($targetPersona, 'personaRelationships')) {
            $relationships =$targetPersona->personaRelationships()->get();
        } else {
            // 1. Check chronological_registers for 'family' or 'relationship' entries
            $regEntries =$targetPersona->relationLoaded('chronologicalRegisters')
                ? $targetPersona->chronologicalRegisters
                : (method_exists($targetPersona, 'chronologicalRegisters') ?$targetPersona->chronologicalRegisters()->get() : collect());

            $fromRegisters = $regEntries->filter(function ($entry) {
                return in_array($entry->register_event_mode ?? '', ['family', 'relationship'], true)
                    && !\Illuminate\Support\Str::startsWith((string) ($entry->chronicle_text ?? ''), 'REVOKED:');
            });

            // 2. Check persona_metadata (attributes) for relationship entries
            $attrEntries =$targetPersona->relationLoaded('attributes')
                ? $targetPersona->attributes
                : (method_exists($targetPersona, 'attributes') ?$targetPersona->attributes()->get() : collect());

            $fromAttributes = $attrEntries->filter(function ($item) {
                $key =$item->attribute_key ?? $item->metadata_key ?? $item->key ?? '';
                return in_array($key, ['relationship', 'persona_relationship', 'family'], true);
            });

            $relationships = $fromRegisters->concat($fromAttributes)->values();
        }
    }
@endphp

<x-tab type="accordion"
       :title="$title"
       icon="bi-diagram-3"
       :open="true">
    @if($relationships->isNotEmpty())
        <div class="flex flex-col gap-md w-full">
            <div class="grid grid-cols-1 md-grid-cols-2 gap-md w-full">
                @foreach($relationships as$rel)
                    @php
                        $relatedPersona =$rel->relatedPersona ?? null;
                        $relatedName =$relatedPersona?->persona_name
                            ?? $rel->related_persona_name
                            ?? $rel->target_name
                            ?? $rel->name
                            ?? lexicon('label_PersonaRelationships_Connection', 'Recorded Connection');
                        $relationType =$rel->relationship_type
                            ?? $rel->type
                            ?? (!empty($rel->register_event_mode) ? \Illuminate\Support\Str::title((string)$rel->register_event_mode) : lexicon('label_PersonaRelationships_Associate', 'Associate'));
                        $relationNotes =$rel->chronicle_text
                            ?? $rel->relationship_notes
                            ?? $rel->description
                            ?? $rel->notes
                            ?? $rel->attribute_value
                            ?? $rel->metadata_value
                            ?? '';
                        $relatedId = $relatedPersona?->id ?? $rel->related_persona_id ?? null;
                        $portrait =$relatedPersona?->primary_portrait_url
                            ?? $relatedPersona?->primary_portrait_uri
                            ?? $rel->portrait_image
                            ?? null;
                        $portraitSrc =$portrait
                            ? (\Illuminate\Support\Str::startsWith($portrait, ['http://', 'https://', '/']) ? $portrait : asset('images/characters/' .$portrait))
                            : null;
                    @endphp

                    <div class="bg-panel-sub border-panel border-rounded p-3 flex flex-row items-start gap-md">
                        <div class="bg-panel-header border-rounded flex-shrink-0 flex items-center justify-center overflow-hidden w-12 h-12">
                            @if($portraitSrc)
                                <img src="{{ $portraitSrc }}"
                                     alt="{{ $relatedName }}"
                                     class="w-full h-full object-cover block">
                            @else
                                <i class="bi bi-person text-xl text-muted" aria-hidden="true"></i>
                            @endif
                        </div>

                        <div class="flex flex-col flex-grow gap-xs">
                            <div class="flex flex-row flex-wrap justify-between items-center gap-xs">
                                @if($relatedId)
                                    <a href="/bio/{{ $relatedId }}"
                                       class="text-sm font-bold text-h hover-text-accent transition-colors no-underline"
                                       x-tooltip="'{{ addslashes(lexicon('tooltip_Roster_ViewPersonaBio', 'View persona biography')) }}'"
                                       title="{{ lexicon('tooltip_Roster_ViewPersonaBio', 'View persona biography') }}">
                                        {{ $relatedName }}
                                    </a>
                                @else
                                    <span class="text-sm font-bold text-h">{{ $relatedName }}</span>
                                @endif

                                <x-badge type="pill"
                                         variant="primary"
                                         :tooltip="lexicon('tooltip_PersonaRelationships_Type', 'Relationship Classification')">
                                    {{ $relationType }}
                                </x-badge>
                            </div>

                            @if(filled($relationNotes))
                                <p class="text-xs text-p leading-relaxed m-0">{{ $relationNotes }}</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @else
        <div class="bg-panel-sub border-dashed-panel border-rounded p-panel-body text-center flex flex-col items-center justify-center gap-xs">
            <i class="bi bi-diagram-3 text-2xl text-muted" aria-hidden="true"></i>
            <span class="text-sm text-muted font-italic">{{ lexicon('msg_PersonaRelationships_NoEntries', 'No relationships have been recorded for this persona.') }}</span>
        </div>
    @endif
</x-tab>