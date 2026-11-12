{{-- 
@WidgetManifest({
    "name": "Distinctions Display",
    "description": "Displays earned distinctions for a user or persona.",
    "icon": "bi-award",
    "fields": {
        "title": { "type": "text", "label": "Widget Title", "default": "Service Distinctions" },
        "mode": { 
            "type": "dropdown", 
            "label": "Display Mode", 
            "options": {"auto": "Auto-Detect Context", "user": "Force User OOC", "persona": "Force Persona IC"}, 
            "default": "auto" 
        }
    }
})
--}}
@php
    $options = is_string($widget->pivot->instance_options) ? json_decode($widget->pivot->instance_options, true) : ($widget->pivot->instance_options ?? []);
    $title = trim($options['title'] ?? 'Service Distinctions');
    $mode = trim($options['mode'] ?? 'auto');
    
    $targetColumn = 'user_id';
    $targetId = auth()->id();

    $activePersona = $persona ?? null;

    if ($mode === 'persona' || ($mode === 'auto' && isset($activePersona))) {
        $targetColumn = 'persona_id';
        $targetId = $activePersona->id ?? null;
    } elseif ($mode === 'user' && isset($user)) {
        $targetColumn = 'user_id';
        $targetId = $user->id ?? auth()->id();
    }

    $earnedDistinctions = \App\Models\ChronologicalRegister::with('distinction')
        ->where($targetColumn, $targetId)
        ->whereNotNull('distinction_id')
        ->whereIn('register_event_mode', ['distinction', 'award'])
        ->orderBy('created_at', 'desc')
        ->get();
        
    $skin = $activeSkin->folder_name ?? ($skinSettings['active_skin'] ?? 'default');
@endphp

<div class="ui-panel widget-distinctions" x-data>
    @if($title)
        <div class="ui-panel-header">
            <span class="ui-panel-title">{{ $title }}</span>
            <x-badge variant="primary" x-tooltip="'{{ addslashes(lexicon('tooltip_Distinctions_TotalEarned', 'Total distinctions earned')) }}'">
                {{ $earnedDistinctions->count() }}
            </x-badge>
        </div>
    @endif

    <div class="ui-panel-body">
        @if($earnedDistinctions->isEmpty())
            <div class="ui-empty-state">
                <span>{{ lexicon('msg_common_NoDistinctions', 'No distinctions have been issued yet.') }}</span>
            </div>
        @else
            <div class="widget-distinctions-grid">
                @foreach($earnedDistinctions as $record)
                    @if($record->distinction)
                        <div class="distinction-container">
                            <x-distinction-badge 
                                :distinction-title="$record->distinction->distinction_title"
                                :distinction-description="$record->chronicle_text ?? $record->distinction->distinction_description"
                                :distinction-base-colour="$record->distinction->distinction_base_colour ?? '#1a1a1a'"
                                :mid-layer-mode="$record->distinction->mid_layer_mode ?? 'css'"
                                :mid-layer-asset="$record->distinction->mid_layer_asset ?? null"
                                :mid-layer-modifier="$record->distinction->mid_layer_modifier ?? null"
                                :top-layer-mode="$record->distinction->top_layer_mode ?? 'image'"
                                :top-layer-asset="$record->distinction->top_layer_asset ?? null"
                                :top-layer-modifier="$record->distinction->top_layer_modifier ?? null"
                                :skin="$skin"
                            />
                        </div>
                    @endif
                @endforeach
            </div>
        @endif
    </div>
</div>