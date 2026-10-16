{{-- 
@WidgetManifest({
    "name": "Character Career Record",
    "description": "Displays character promotions, transfers, awards, and career milestones.",
    "icon": "bi-award",
    "fields": {
        "title": { "type": "text", "label": "Widget Title", "default": "Career Service Record" },
        "limit": { "type": "text", "label": "Number of Records to Show", "default": "15" }
    }
})
--}}
@php
    $options = is_string($widget->pivot->instance_options) ? json_decode($widget->pivot->instance_options, true) : ($widget->pivot->instance_options ?? []);
    if (empty($options['title'])) {
        $options['title'] = lexicon('label_CharacterCareer_DefaultTitle', 'Career Service Record');
    }
    $options['event_types'] = 'promotion,transfer,award,status_change,assignment,commendation';
    $options['display_target'] = 'IC';
    $options['mode'] = 'character';
    $widget->pivot->instance_options = json_encode($options);
@endphp
@include('widgets.service_records')