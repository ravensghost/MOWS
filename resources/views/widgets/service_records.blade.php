{{-- 
@WidgetManifest({
    "name": "Service Log & Timeline",
    "overview": "Displays a filtered timeline of chronological registers, background history, or family bonds.",
    "icon": "bi-clock-history",
    "fields": {
        "title": { "type": "text", "label": "Widget Title", "default": "Service Log" },
        "mode": { 
            "type": "dropdown", 
            "label": "Display Mode", 
            "options": {"auto": "Auto-Detect Context", "user": "Force User OOC", "character": "Force Character IC"}, 
            "default": "auto" 
        },
        "scope_target": {
            "type": "dropdown",
            "label": "Scope Target Filter",
            "options": {"": "All Targets", "IC": "IC Only", "OOC": "OOC Only", "BOTH": "Both"},
            "default": ""
        },
        "event_types": {
            "type": "text",
            "label": "Event Modes (comma-separated, leave blank for all)",
            "default": ""
        },
        "limit": { "type": "text", "label": "Number of Records to Show", "default": "10" }
    }
})
--}}
@php
    $options = is_string($widget->pivot->instance_options) ? json_decode($widget->pivot->instance_options, true) : ($widget->pivot->instance_options ?? []);
    $rawTitle = trim($options['title'] ?? '');
    $title = $rawTitle !== '' ? $rawTitle : lexicon('label_ServiceRecords_DefaultWidgetTitle', 'Service Log');
    $mode = trim($options['mode'] ?? 'auto');
    $scopeTarget = trim($options['scope_target'] ?? '');
    $eventTypesRaw = trim($options['event_types'] ?? '');
    $limit = (int) trim($options['limit'] ?? '10');
    
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

    $types = !empty($eventTypesRaw) ? array_filter(array_map('trim', explode(',', $eventTypesRaw))) : [];

    // Filter eager-loaded relationship in memory when on a persona page to prevent N+1 queries
    if ($targetColumn === 'persona_id' && isset($activePersona) && $activePersona->relationLoaded('chronologicalRegisters')) {
        $collection = $activePersona->chronologicalRegisters;

        if (!empty($scopeTarget)) {
            $collection = $collection->where('scope_target', $scopeTarget);
        }

        if (!empty($types)) {
            $collection = $collection->whereIn('register_event_mode', $types);
        }

        $records = $collection->sortByDesc('created_at')->take($limit)->values();
    } else {
        $query = \App\Models\ChronologicalRegister::with(['distinction', 'authorisingUser'])
            ->where($targetColumn, $targetId);

        if (!empty($scopeTarget)) {
            $query->where('scope_target', $scopeTarget);
        }

        if (!empty($types)) {
            $query->whereIn('register_event_mode', $types);
        }

        $records = $query->orderBy('created_at', 'desc')
            ->take($limit)
            ->get();
    }
@endphp

<div class="bg-panel border border-panel rounded-panel shadow-panel flex flex-col overflow-hidden w-full mb-6" x-data>
    @if($title)
        <div class="bg-panel-header text-h border-b border-panel p-panel-header font-bold flex flex-row flex-wrap justify-between items-center gap-md">
            <span class="text-xl font-bold text-accent">{{ $title }}</span>
            <x-badge type="primary"
                     variant="primary"
                     x-tooltip="'{{ addslashes(lexicon('tooltip_ServiceRecords_TotalRecords', 'Total service log entries')) }}'"
                     aria-label="{{ lexicon('tooltip_ServiceRecords_TotalRecords', 'Total service log entries') }}">
                {{ $records->count() }}
            </x-badge>
        </div>
    @endif

    <div class="p-0 flex flex-col flex-grow">
        @if($records->isEmpty())
            <div class="p-panel text-center">
                <span class="text-muted italic">{{ lexicon('msg_common_NoServiceRecords', 'No service records found.') }}</span>
            </div>
        @else
            <div class="flex flex-col">
                @foreach($records as $record)
                    @php
                        $badgeVariant = match($record->register_event_mode) {
                            'promotion', 'family'                  => 'success',
                            'transfer', 'background', 'academy'    => 'primary',
                            'award', 'commendation'                => 'warning',
                            'status_change', 'evaluation'          => 'info',
                            'manual_entry'                         => 'planned',
                            default                                => 'planned'
                        };
                        $isRevoked = \Illuminate\Support\Str::startsWith($record->chronicle_text, 'REVOKED:');
                        $formattedDate = function_exists('world_date') ? world_date($record->created_at) : $record->created_at->format('Y-m-d');
                    @endphp
                    
                    <div class="p-4 flex flex-col gap-xs hover-bg-panel-hover transition-colors border-b border-panel">
                        <div class="flex flex-row flex-wrap justify-between items-start gap-sm">
                            <div class="flex flex-row flex-wrap gap-sm items-center">
                                @if($isRevoked)
                                    <x-badge type="danger"
                                             variant="danger"
                                             x-tooltip="'{{ addslashes(lexicon('tooltip_ServiceRecords_Revoked', 'This record has been revoked')) }}'"
                                             aria-label="{{ lexicon('tooltip_ServiceRecords_Revoked', 'This record has been revoked') }}">
                                        {{ lexicon('label_ServiceRecords_Revoked', 'Revoked') }}
                                    </x-badge>
                                @endif
                                
                                @if($record->distinction)
                                    <x-badge :type="$badgeVariant"
                                             :variant="$badgeVariant"
                                             x-tooltip="'{{ addslashes(lexicon('tooltip_ServiceRecords_AwardBadge', 'Award record entry')) }}'"
                                             aria-label="{{ lexicon('tooltip_ServiceRecords_AwardBadge', 'Award record entry') }}">
                                        {{ $record->distinction->distinction_title }}
                                    </x-badge>
                                @else
                                    <x-badge :type="$badgeVariant"
                                             :variant="$badgeVariant"
                                             class="uppercase"
                                             x-tooltip="'{{ addslashes(lexicon('tooltip_ServiceRecords_EventBadge', 'Career event record entry')) }}'"
                                             aria-label="{{ lexicon('tooltip_ServiceRecords_EventBadge', 'Career event record entry') }}">
                                        {{ str_replace('_', ' ', $record->register_event_mode) }}
                                    </x-badge>
                                @endif
                            </div>
                            <span class="text-xs text-muted whitespace-nowrap"
                                  aria-label="{{ lexicon('tooltip_ServiceRecords_RecordDate', 'Date recorded') }}"
                                  x-tooltip="'{{ addslashes(lexicon('tooltip_ServiceRecords_RecordDate', 'Date recorded')) }}'">
                                {{ $formattedDate }}
                            </span>
                        </div>
                        
                        <span class="text-sm text-p mt-1 block">
                            {{ $record->chronicle_text }}
                        </span>
                        
                        @if($record->authorisingUser)
                            <span class="text-xs text-muted mt-1 opacity-75 block">
                                {{ lexicon('label_ServiceRecords_AuthorisedBy', 'Authorised by:') }} {{ $record->authorisingUser->name }}
                            </span>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>