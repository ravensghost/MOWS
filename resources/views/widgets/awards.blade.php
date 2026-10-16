{{-- 
@WidgetManifest({
    "name": "Awards Display",
    "description": "Displays earned awards for a user or character.",
    "icon": "bi-award",
    "fields": {
        "title": { "type": "text", "label": "Widget Title", "default": "Service Awards" },
        "mode": { 
            "type": "dropdown", 
            "label": "Display Mode", 
            "options": {"auto": "Auto-Detect Context", "user": "Force User OOC", "character": "Force Character IC"}, 
            "default": "auto" 
        }
    }
})
--}}
@php
    $options = is_string($widget->pivot->instance_options) ? json_decode($widget->pivot->instance_options, true) : ($widget->pivot->instance_options ?? []);
    $title = trim($options['title'] ?? 'Service Awards');
    $mode = trim($options['mode'] ?? 'auto');
    
    $targetType = 'user';
    $targetId = auth()->id();

    if ($mode === 'character' || ($mode === 'auto' && isset($character))) {
        $targetType = 'character';
        $targetId = $character->id ?? null;
    } elseif ($mode === 'user' && isset($user)) {
        $targetType = 'user';
        $targetId = $user->id ?? auth()->id();
    }

    $earnedAwards = \App\Models\ServiceRecord::with('award')
        ->where($targetType . '_id', $targetId)
        ->whereNotNull('award_id')
        ->where('event_type', 'award')
        ->orderBy('created_at', 'desc')
        ->get();
        
    $skin = $activeSkin->folder_name ?? ($skinSettings['active_skin'] ?? 'default');
@endphp

<div class="bg-panel border border-panel rounded-panel shadow-panel flex flex-col overflow-hidden mb-6 widget-awards" x-data>
    @if($title)
        <div class="bg-panel-header text-h border-b border-panel p-panel-header font-bold flex flex-row flex-wrap justify-between items-center gap-md">
            <span class="text-xl font-bold text-accent">{{ $title }}</span>
            <x-badge variant="primary" x-tooltip="'{{ addslashes(lexicon('tooltip_Awards_TotalEarned', 'Total awards earned')) }}'">
                {{ $earnedAwards->count() }}
            </x-badge>
        </div>
    @endif

    <div class="p-panel flex flex-col flex-grow gap-md">
        @if($earnedAwards->isEmpty())
            <div class="bg-panel-sub border-dashed-panel rounded-panel p-panel text-muted italic text-center">
                <span>{{ lexicon('msg_common_NoAwards', 'No awards have been issued yet.') }}</span>
            </div>
        @else
            <div class="flex flex-row flex-wrap gap-md">
                @foreach($earnedAwards as $record)
                    @if($record->award)
                        <div class="award-container flex flex-col items-center gap-xs">
                            <x-award-badge 
                                :award-name="$record->award->name"
                                :description="$record->description"
                                :base-layer="$record->award->base_color ?? '#1a1a1a'"
                                :mid-type="$record->award->mid_type ?? 'css'"
                                :mid-image="$record->award->mid_image ?? null"
                                :mid-css="$record->award->mid_css ?? null"
                                :top-type="$record->award->top_type ?? 'css'"
                                :top-image="$record->award->top_image ?? null"
                                :top-css="$record->award->top_css ?? null"
                                :skin="$skin"
                            />
                        </div>
                    @endif
                @endforeach
            </div>
        @endif
    </div>
</div>