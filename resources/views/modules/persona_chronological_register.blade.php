{{-- resources/views/modules/persona_chronological_register.blade.php --}}
{{-- @WidgetManifest({ "name": "Persona Chronological Register", "description": "Displays the chronological service register, career milestones, promotions, and honours for this persona.", "icon": "bi-clock-history", "fields": { "title": { "type": "text", "label": "Module Title", "default": "Chronological Register" } } }) --}}
@php
    $rawOptions = $moduleSettings
        ?? $module?->pivot?->book_config
        ?? $module?->pivot?->instance_options
        ?? $widget?->pivot?->instance_options
        ?? [];
    $options = is_string($rawOptions) ? (json_decode($rawOptions, true) ?: []) : (is_array($rawOptions) ? $rawOptions : []);

    $rawTitle = trim((string) ($options['title'] ?? ''));
    $title = $rawTitle !== '' ? $rawTitle : lexicon('label_PersonaRegister_DefaultTitle', 'Chronological Register');

    $targetPersona = $persona ?? $character ?? null;
    $registerEntries = collect();

    if ($targetPersona) {
        if ($targetPersona->relationLoaded('chronologicalRegisters')) {
            $registerEntries = $targetPersona->chronologicalRegisters->sortByDesc('created_at')->values();
        } elseif ($targetPersona->relationLoaded('serviceRecords')) {
            $registerEntries = $targetPersona->serviceRecords->sortByDesc('created_at')->values();
        } elseif (method_exists($targetPersona, 'chronologicalRegisters')) {
            $registerEntries = $targetPersona->chronologicalRegisters()->with('distinction')->latest()->get();
        }
    }
@endphp

<x-tab type="accordion"
       :title="$title"
       icon="bi-clock-history"
       :open="true">
    @if($registerEntries->isNotEmpty())
        <div class="flex flex-col gap-sm">
            @foreach($registerEntries as $entry)
                @php
                    $entryTitle = optional($entry->distinction)->distinction_title
                        ?? (!empty($entry->register_event_mode)
                            ? \Illuminate\Support\Str::title(str_replace('_', ' ', (string) $entry->register_event_mode))
                            : lexicon('label_PersonaRegister_Entry', 'Register Entry'));
                    $entryDetails = (string) ($entry->chronicle_text ?? '');
                    $isRevoked = \Illuminate\Support\Str::startsWith($entryDetails, 'REVOKED:');
                    $entryDate = $entry->created_at ?? null;
                    $formattedDate = $entryDate
                        ? (function_exists('world_date')
                            ? world_date($entryDate)
                            : ($entryDate instanceof \Carbon\CarbonInterface ? $entryDate->format('d M Y') : (string) $entryDate))
                        : null;
                @endphp

                <x-tab type="sub"
                       :title="$entryTitle"
                       icon="bi-Journal-check">
                    <x-slot:top>
                        @if($isRevoked)
                            <x-badge type="pill"
                                     variant="danger"
                                     :tooltip="lexicon('tooltip_ServiceRecords_Revoked', 'This record has been revoked')">
                                {{ lexicon('label_ServiceRecords_Revoked', 'Revoked') }}
                            </x-badge>
                        @endif

                        @if($formattedDate)
                            <span class="text-xs text-muted whitespace-nowrap">{{ $formattedDate }}</span>
                        @endif
                    </x-slot:top>

                    @if(filled($entryDetails))
                        <p class="text-xs text-p leading-relaxed whitespace-pre-line m-0">{{ $entryDetails }}</p>
                    @endif
                </x-tab>
            @endforeach
        </div>
    @else
        <div class="bg-panel-sub border-dashed-panel border-rounded p-panel-body text-center flex flex-col items-center justify-center gap-xs">
            <i class="bi bi-clock-history text-2xl text-muted" aria-hidden="true"></i>
            <span class="text-sm text-muted font-italic">{{ lexicon('msg_PersonaRegister_NoEntries', 'No chronological register entries recorded for this persona.') }}</span>
        </div>
    @endif
</x-tab>