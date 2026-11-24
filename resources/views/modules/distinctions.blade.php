{{-- resources/views/modules/distinctions.blade.php --}}
{{-- @WidgetManifest({ "name": "Distinctions Display", "description": "Displays earned distinctions for a user or persona.", "icon": "bi-award", "fields": { "title": { "type": "text", "label": "Module Title", "default": "Service Distinctions" }, "mode": { "type": "dropdown", "label": "Display Mode", "options": {"auto": "Auto-Detect Context", "user": "Force User OOC", "persona": "Force Persona IC"}, "default": "auto" } } }) --}}
@php
    $rawOptions =$moduleSettings
        ?? $module?->pivot?->book_config
        ?? $module?->pivot?->instance_options
        ?? $widget?->pivot?->instance_options
        ?? [];
    $options = is_string($rawOptions) ? (json_decode($rawOptions, true) ?: []) : (is_array($rawOptions) ?$rawOptions : []);

    $rawTitle = trim($options['title'] ?? '');$title = $rawTitle !== '' ?$rawTitle : lexicon('label_Distinctions_DefaultTitle', 'Service Distinctions');
    $mode = trim($options['mode'] ?? 'auto');

    $targetColumn = 'user_id';
    $targetId = auth()->id();$activePersona = $persona ?? $character ?? null;

    if (in_array($mode, ['persona', 'character'], true) \vert{}\vert{} ($mode === 'auto' && isset($activePersona))) {$targetColumn = 'persona_id';
        $targetId =$activePersona->id ?? null;
    } elseif ($mode === 'user' && isset($user)) {$targetColumn = 'user_id';
        $targetId =$user->id ?? auth()->id();
    }

    if ($targetColumn === 'persona_id' && isset($activePersona) &&$activePersona->relationLoaded('chronologicalRegisters')) {
        $earnedDistinctions =$activePersona->chronologicalRegisters
            ->whereNotNull('distinction_id')
            ->filter(fn ($r) => in_array($r->register_event_mode, ['award', 'distinction'], true) && !\Illuminate\Support\Str::startsWith((string)$r->chronicle_text, 'REVOKED:'))
            ->sortByDesc('created_at')
            ->values();
    } else {
        $earnedDistinctions = \App\Models\ChronologicalRegister::with('distinction')
            ->where($targetColumn,$targetId)
            ->whereNotNull('distinction_id')
            ->whereIn('register_event_mode', ['award', 'distinction'])
            ->where('chronicle_text', 'not like', 'REVOKED:%')
            ->orderByDesc('created_at')
            ->get();
    }

    $skin = $activeSkin?->directory_name ?? ($skinSettings['active_skin'] ?? 'default');
@endphp

<x-tab type="accordion"
       :title="$title"
       icon="bi-award"
       :open="true">
    @if($earnedDistinctions->isEmpty())
        <div class="bg-panel-sub border-dashed-panel border-rounded p-panel-body text-center">
            <span class="text-sm text-muted font-italic">{{ lexicon('msg_common_NoDistinctions', 'No distinctions have been issued yet.') }}</span>
        </div>
    @else
        <div class="flex flex-row flex-wrap items-center justify-center gap-md p-2">
            @foreach($earnedDistinctions as $record)
                @if($record->distinction)
                    @php
                        $citation = $record->chronicle_text ?: $record->distinction->distinction_description;
                        $badgeTooltip =$citation
                            ? $record->distinction->distinction_title . ' — ' .$citation
                            : $record->distinction->distinction_title;
                    @endphp
                    <x-badge type="distinction"
                             :award="$record->distinction"
                             :distinctionDescription="$citation"
                             :tooltip="$badgeTooltip"
                             :skin="$skin" />
                @endif
            @endforeach
        </div>
    @endif
</x-tab>