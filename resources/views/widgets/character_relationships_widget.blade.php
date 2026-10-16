{{-- 
@WidgetManifest({
    "name": "Character Family & Relationships",
    "description": "Displays character family lineage, dependents, and relational bonds.",
    "icon": "bi-people",
    "fields": {
        "title": { "type": "text", "label": "Widget Title", "default": "Family & Relationships" },
        "limit": { "type": "text", "label": "Number of Records to Show", "default": "10" }
    }
})
--}}
@php
    $options = is_string($widget->pivot->instance_options) ? json_decode($widget->pivot->instance_options, true) : ($widget->pivot->instance_options ?? []);
    if (empty($options['title'])) {
        $options['title'] = lexicon('label_CharacterRelationships_DefaultTitle', 'Family & Relationships');
    }
    $options['event_types'] = 'family,relationship';
    $options['display_target'] = 'IC';
    $options['mode'] = 'character';
    $widget->pivot->instance_options = json_encode($options);
@endphp
@include('widgets.service_records')