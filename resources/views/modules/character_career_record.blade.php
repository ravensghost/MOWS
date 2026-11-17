{{-- resources/views/modules/character_career_record.blade.php --}}
{{-- @WidgetManifest({ "name": "Character Career Record", "description": "Displays character promotions, transfers, awards, and career milestones.", "icon": "bi-award", "fields": { "title": { "type": "text", "label": "Module Title", "default": "Career Service Record" }, "limit": { "type": "text", "label": "Number of Records to Show", "default": "15" } } }) --}}
@php
    $rawOptions = $moduleSettings
        ?? $module?->pivot?->book_config
        ?? $module?->pivot?->instance_options
        ?? $widget?->pivot?->instance_options
        ?? [];
    $options = is_string($rawOptions) ? (json_decode($rawOptions, true) ?: []) : (is_array($rawOptions) ? $rawOptions : []);

    if (empty($options['title'])) {
        $options['title'] = lexicon('label_CharacterCareer_DefaultTitle', 'Career Service Record');
    }

    $options['event_types'] = 'promotion,transfer,award,status_change,assignment,commendation';
    $options['scope_target'] = 'IC';
    $options['mode'] = 'character';
@endphp

@include('modules.chronological_registers', [
    'moduleSettings' => $options,
])