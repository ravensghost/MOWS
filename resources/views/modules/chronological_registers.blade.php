{{-- resources/views/modules/chronological_registers.blade.php --}}
{{-- @WidgetManifest({ "name": "Service Log & Timeline", "overview": "Displays a filtered timeline of chronological registers, background history, or family bonds.", "icon": "bi-clock-history", "fields": { "title": { "type": "text", "label": "Module Title", "default": "Service Log" }, "mode": { "type": "dropdown", "label": "Display Mode", "options": {"auto": "Auto-Detect Context", "user": "Force User OOC", "character": "Force Character IC"}, "default": "auto" }, "scope_target": { "type": "dropdown", "label": "Scope Target Filter", "options": {"": "All Targets", "IC": "IC Only", "OOC": "OOC Only", "BOTH": "Both"}, "default": "" }, "event_types": { "type": "text", "label": "Event Modes (comma-separated, leave blank for all)", "default": "" }, "limit": { "type": "text", "label": "Number of Records to Show", "default": "10" } } }) --}}
@php
    $rawOptions = $moduleSettings
        ?? $module?->pivot?->book_config
        ?? $module?->pivot?->instance_options
        ?? $widget?->pivot?->instance_options
        ?? [];
    $options = is_string($rawOptions) ? (json_decode($rawOptions, true) ?: []) : (is_array($rawOptions) ? $rawOptions : []);

    $rawTitle = trim($options['title'] ?? '');
    $title = $rawTitle !== '' ? $rawTitle : lexicon('label_ServiceRecords_DefaultWidgetTitle', 'Service Log');
    $mode = trim($options['mode'] ?? 'auto');
    $scopeTarget = trim($options['scope_target'] ?? $options['display_target'] ?? '');
    $eventTypesRaw = trim($options['event_types'] ?? '');
    $limit = max(1, (int) trim($options['limit'] ?? '10'));

    $targetColumn = 'user_id';
    $targetId = auth()->id();
    $activePersona = $persona ?? $character ?? null;

    if ($mode === 'character' || ($mode === 'auto' && isset($activePersona))) {
        $targetColumn = 'persona_id';
        $targetId = $activePersona->id ?? null;
    } elseif ($mode === 'user' && isset($user)) {
        $targetColumn = 'user_id';
        $targetId = $user->id ?? auth()->id();
    }

    $types = $eventTypesRaw !== '' ? array_filter(array_map('trim', explode(',', $eventTypesRaw))) : [];

    // Filter eager-loaded relationship in memory when on a persona page to prevent N+1 queries
    if ($targetColumn === 'persona_id' && isset($activePersona) && $activePersona->relationLoaded('chronologicalRegisters')) {
        $collection = $activePersona->chronologicalRegisters;

        if ($scopeTarget !== '') {
            $collection = $collection->where('scope_target', $scopeTarget);
        }

        if (!empty($types)) {
            $collection = $collection->whereIn('register_event_mode', $types);
        }

        $records = $collection->sortByDesc('created_at')->take($limit)->values();
    } else {
        $query = \App\Models\ChronologicalRegister::with(['distinction', 'authorisingUser'])
            ->where($targetColumn, $targetId);

        if ($scopeTarget !== '') {
            $query->where('scope_target', $scopeTarget);
        }

        if (!empty($types)) {
            $query->whereIn('register_event_mode', $types);
        }

        $records = $query->orderByDesc('created_at')
            ->take($limit)
            ->get();
    }
@endphp

<x-tab type="accordion"
       :title="$title"
       icon="bi-clock-history"
       :open="true">
    @if($records->isEmpty())
        <div class="bg-panel-sub border-dashed-panel border-rounded p-panel-body text-center">
            <span class="text-sm text-muted font-italic">{{ lexicon('msg_common_NoServiceRecords', 'No service records found.') }}</span>
        </div>
    @else
        <div class="flex flex-col">
            @foreach($records as $record)
                @php
                    $badgeVariant = match($record->register_event_mode) {
                        'promotion', 'family'               => 'success',
                        'transfer', 'background', 'academy' => 'primary',
                        'award', 'commendation'             => 'warning',
                        'status_change', 'evaluation'       => 'info',
                        default                             => 'muted',
                    };
                    $isRevoked = \Illuminate\Support\Str::startsWith((string) $record->chronicle_text, 'REVOKED:');
                    $formattedDate = function_exists('world_date')
                        ? world_date($record->created_at)
                        : ($record->created_at ? $record->created_at->format('Y-m-d') : '');
                    $dateTooltip = lexicon('tooltip_ServiceRecords_RecordDate', 'Date recorded');
                    $authoriserName = $record->authorisingUser->user_handle
                        ?? $record->authorisingUser->name
                        ?? null;
                @endphp

                <div class="p-4 flex flex-col gap-xs hover-bg-panel-hover transition-colors border-b-table-row">
                    <div class="flex flex-row flex-wrap justify-between items-start gap-sm">
                        <div class="flex flex-row flex-wrap gap-sm items-center">
                            @if($isRevoked)
                                <x-badge type="pill"
                                         variant="danger"
                                         :tooltip="lexicon('tooltip_ServiceRecords_Revoked', 'This record has been revoked')">
                                    {{ lexicon('label_ServiceRecords_Revoked', 'Revoked') }}
                                </x-badge>
                            @endif

                            @if($record->distinction)
                                <x-badge type="pill"
                                         :variant="$badgeVariant"
                                         :tooltip="lexicon('tooltip_ServiceRecords_AwardBadge', 'Award record entry')">
                                    {{ $record->distinction->distinction_title }}
                                </x-badge>
                            @else
                                <x-badge type="pill"
                                         :variant="$badgeVariant"
                                         :tooltip="lexicon('tooltip_ServiceRecords_EventBadge', 'Career event record entry')">
                                    {{ str_replace('_', ' ', (string) $record->register_event_mode) }}
                                </x-badge>
                            @endif
                        </div>

                        @if($formattedDate)
                            <span class="text-xs text-muted whitespace-nowrap"
                                  x-tooltip="'{{ addslashes($dateTooltip) }}'"
                                  title="{{ $dateTooltip }}"
                                  aria-label="{{ $dateTooltip }}">
                                {{ $formattedDate }}
                            </span>
                        @endif
                    </div>

                    <span class="text-sm text-p block">
                        {{ $record->chronicle_text }}
                    </span>

                    @if($authoriserName)
                        <span class="text-xs text-muted block">
                            {{ lexicon('label_ServiceRecords_AuthorisedBy', 'Authorised by:') }} {{ $authoriserName }}
                        </span>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</x-tab>