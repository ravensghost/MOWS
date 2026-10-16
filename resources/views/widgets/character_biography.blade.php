{{-- 
@WidgetManifest({
    "name": "Character Biography & History",
    "description": "Displays character background, academy, education, and life milestones.",
    "icon": "bi-journal-text",
    "fields": {
        "title": { "type": "text", "label": "Widget Title", "default": "Personal History & Biography" },
        "limit": { "type": "text", "label": "Number of Records to Show", "default": "15" }
    }
})
--}}
@php
    $options = is_string($widget->pivot->instance_options) ? json_decode($widget->pivot->instance_options, true) : ($widget->pivot->instance_options ?? []);
    if (empty($options['title'])) {
        $options['title'] = lexicon('label_CharacterBiography_DefaultTitle', 'Personal History & Biography');
    }
    $options['event_types'] = 'background,academy,education,training,milestone,incident';
    $options['display_target'] = 'IC';
    $options['mode'] = 'character';
    $widget->pivot->instance_options = json_encode($options);
@endphp
@include('widgets.service_records')