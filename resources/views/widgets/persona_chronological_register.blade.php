{{-- 
@WidgetManifest({
    "name": "Persona Chronological Register",
    "description": "Displays the chronological service register, career milestones, promotions, and honours for this persona.",
    "icon": "bi-clock-history",
    "fields": {
        "title": { "type": "text", "label": "Widget Title", "default": "Chronological Register" }
    }
})
--}}
@php
    $options = is_string($widget->pivot->instance_options ?? null)
        ? json_decode($widget->pivot->instance_options, true)
        : ($widget->pivot->instance_options ?? []);

    $rawTitle = trim($options['title'] ?? '');
    $title = $rawTitle !== '' ? $rawTitle : lexicon('label_PersonaRegister_DefaultTitle', 'Chronological Register');

    $targetPersona = $persona ?? $character ?? null;
    $registerEntries = collect();

    if ($targetPersona) {
        if ($targetPersona->relationLoaded('chronologicalRegister')) {
            $registerEntries = $targetPersona->chronologicalRegister;
        } elseif ($targetPersona->relationLoaded('registers')) {
            $registerEntries = $targetPersona->registers;
        } elseif ($targetPersona->relationLoaded('serviceRecords')) {
            $registerEntries = $targetPersona->serviceRecords;
        } elseif (method_exists($targetPersona, 'chronologicalRegister')) {
            $registerEntries = $targetPersona->chronologicalRegister()->latest()->get();
        } elseif (method_exists($targetPersona, 'registers')) {
            $registerEntries = $targetPersona->registers()->latest()->get();
        } elseif (method_exists($targetPersona, 'serviceRecords')) {
            $registerEntries = $targetPersona->serviceRecords()->latest()->get();
        } elseif (method_exists($targetPersona, 'metadata')) {
            $metaCollection = $targetPersona->relationLoaded('metadata')
                ? $targetPersona->metadata
                : $targetPersona->metadata()->get();

            $registerEntries = $metaCollection->filter(function ($item) {
                $key = $item->metadata_key ?? $item->key ?? '';
                return in_array($key, ['register_entry', 'service_record', 'chronological_entry'], true);
            })->values();
        }
    }
@endphp

<div class="bg-panel border border-panel rounded-panel shadow-panel flex flex-col overflow-hidden mb-6 widget-persona-chronological-register" x-data>
    @if($title)
        <div class="bg-panel-header text-h border-b-panel p-panel-header font-bold flex flex-row flex-wrap justify-between items-center gap-md">
            <div class="flex flex-row items-center gap-xs">
                <i class="bi bi-clock-history text-accent"></i>
                <span class="text-xl font-bold text-accent">{{ $title }}</span>
            </div>
            <i class="bi bi-calendar3 text-accent"
               x-tooltip="'{{ addslashes(lexicon('tooltip_PersonaRegister_Header', 'Chronological service history and milestones')) }}'"
               aria-label="{{ lexicon('tooltip_PersonaRegister_Header', 'Chronological service history and milestones') }}"></i>
        </div>
    @endif

    <div class="p-panel flex flex-col gap-md">
        @if($registerEntries->isNotEmpty())
            <div class="flex flex-col gap-sm">
                @foreach($registerEntries as $entry)
                    @php
                        $entryTitle = $entry->register_title ?? $entry->title ?? $entry->event_type ?? lexicon('label_PersonaRegister_Entry', 'Register Entry');
                        $entryDetails = $entry->register_notes ?? $entry->description ?? $entry->notes ?? $entry->metadata_value ?? $entry->value ?? '';
                        $entryStardate = $entry->stardate ?? $entry->register_stardate ?? null;
                        $entryDate = $entry->recorded_at ?? $entry->created_at ?? null;
                    @endphp
                    <div class="bg-panel-sub border border-panel rounded-panel p-3 flex flex-col gap-xs">
                        <div class="flex flex-row flex-wrap justify-between items-center gap-sm">
                            <span class="text-sm font-bold text-h">{{ $entryTitle }}</span>
                            <div class="flex flex-row items-center gap-xs text-xs text-muted">
                                @if($entryStardate)
                                    <span class="font-bold text-accent">{{ $entryStardate }}</span>
                                @endif
                                @if($entryDate)
                                    <span>{{ $entryDate instanceof \Carbon\CarbonInterface ? $entryDate->format('d M Y') : $entryDate }}</span>
                                @endif
                            </div>
                        </div>
                        @if(filled($entryDetails))
                            <p class="text-xs text-p leading-relaxed whitespace-pre-line m-0">{{ $entryDetails }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="bg-panel-sub border-dashed-panel rounded-panel p-panel text-center flex flex-col items-center justify-center gap-xs">
                <i class="bi bi-clock-history text-2xl text-muted"></i>
                <span class="text-sm text-muted italic">{{ lexicon('msg_PersonaRegister_NoEntries', 'No chronological register entries recorded for this persona.') }}</span>
            </div>
        @endif
    </div>
</div>