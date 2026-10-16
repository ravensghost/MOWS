{{-- 
@WidgetManifest({
    "name": "Service Log & Timeline",
    "description": "Displays a filtered timeline of service records, background history, or family bonds.",
    "icon": "bi-clock-history",
    "fields": {
        "title": { "type": "text", "label": "Widget Title", "default": "Service Log" },
        "mode": { 
            "type": "dropdown", 
            "label": "Display Mode", 
            "options": {"auto": "Auto-Detect Context", "user": "Force User OOC", "character": "Force Character IC"}, 
            "default": "auto" 
        },
        "display_target": {
            "type": "dropdown",
            "label": "Display Target Filter",
            "options": {"": "All Targets", "IC": "IC Only", "OOC": "OOC Only", "BOTH": "Both"},
            "default": ""
        },
        "event_types": {
            "type": "text",
            "label": "Event Types (comma-separated, leave blank for all)",
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
    $displayTarget = trim($options['display_target'] ?? '');
    $eventTypesRaw = trim($options['event_types'] ?? '');
    $limit = (int) trim($options['limit'] ?? '10');
    
    $targetType = 'user';
    $targetId = auth()->id();

    if ($mode === 'character' || ($mode === 'auto' && isset($character))) {
        $targetType = 'character';
        $targetId = $character->id ?? null;
    } elseif ($mode === 'user' && isset($user)) {
        $targetType = 'user';
        $targetId = $user->id ?? auth()->id();
    }

    $types = !empty($eventTypesRaw) ? array_filter(array_map('trim', explode(',', $eventTypesRaw))) : [];

    // Filter eager-loaded relationship in memory when on a character page to prevent N+1 queries
    if ($targetType === 'character' && isset($character) && $character->relationLoaded('serviceRecords')) {
        $collection = $character->serviceRecords;

        if (!empty($displayTarget)) {
            $collection = $collection->where('display_target', $displayTarget);
        }

        if (!empty($types)) {
            $collection = $collection->whereIn('event_type', $types);
        }

        $records = $collection->sortByDesc('created_at')->take($limit)->values();
    } else {
        $query = \App\Models\ServiceRecord::with(['award', 'issuer'])
            ->where($targetType . '_id', $targetId);

        if (!empty($displayTarget)) {
            $query->where('display_target', $displayTarget);
        }

        if (!empty($types)) {
            $query->whereIn('event_type', $types);
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
                        $badgeVariant = match($record->event_type) {
                            'promotion', 'family'                  => 'success',
                            'transfer', 'background', 'academy'    => 'primary',
                            'award', 'commendation'                => 'warning',
                            'status_change', 'evaluation'          => 'info',
                            'manual_entry'                         => 'planned',
                            default                                => 'planned'
                        };
                        $isRevoked = \Illuminate\Support\Str::startsWith($record->description, 'REVOKED:');
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
                                
                                @if($record->award)
                                    <x-badge :type="$badgeVariant"
                                             :variant="$badgeVariant"
                                             x-tooltip="'{{ addslashes(lexicon('tooltip_ServiceRecords_AwardBadge', 'Award record entry')) }}'"
                                             aria-label="{{ lexicon('tooltip_ServiceRecords_AwardBadge', 'Award record entry') }}">
                                        {{ $record->award->name }}
                                    </x-badge>
                                @else
                                    <x-badge :type="$badgeVariant"
                                             :variant="$badgeVariant"
                                             class="uppercase"
                                             x-tooltip="'{{ addslashes(lexicon('tooltip_ServiceRecords_EventBadge', 'Career event record entry')) }}'"
                                             aria-label="{{ lexicon('tooltip_ServiceRecords_EventBadge', 'Career event record entry') }}">
                                        {{ str_replace('_', ' ', $record->event_type) }}
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
                            {{ $record->description }}
                        </span>
                        
                        @if($record->issuer)
                            <span class="text-xs text-muted mt-1 opacity-75 block">
                                {{ lexicon('label_ServiceRecords_AuthorisedBy', 'Authorised by:') }} {{ $record->issuer->name }}
                            </span>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>