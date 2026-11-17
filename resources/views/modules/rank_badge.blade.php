{{-- 
@WidgetManifest({
    "name": "Rank Badge",
    "description": "Displays a standalone rank badge.",
    "icon": "bi-shield-check",
    "fields": {
        "rank_set": { "type": "text", "label": "Rank Set Folder", "default": "DefaultSet" },
        "base_color": { "type": "text", "label": "Base Colour (Hex)", "default": "#1a1a1a" },
        "dept_color": { "type": "text", "label": "Department Colour (Hex)", "default": "#ffffff" },
        "mid_type": { "type": "dropdown", "label": "Mid Type", "options": {"css": "CSS Class", "image": "Image"}, "default": "css" },
        "mid_asset": { "type": "text", "label": "Mid Asset", "default": "" },
        "top_type": { "type": "dropdown", "label": "Top Type", "options": {"css": "CSS Class", "image": "Image"}, "default": "image" },
        "top_asset": { "type": "text", "label": "Top Asset", "default": "default.png" }
    }
})
--}}
@php
    $options = is_string($widget->pivot->instance_options) ? json_decode($widget->pivot->instance_options, true) : ($widget->pivot->instance_options ?? []);
    
    $rankSet   = trim($options['rank_set'] ?? 'DefaultSet');
    $baseLayer = trim($options['base_color'] ?? '#1a1a1a'); 
    $deptColor = trim($options['dept_color'] ?? '#ffffff');
    
    $midType   = trim($options['mid_type'] ?? 'css');
    $midAsset  = trim($options['mid_asset'] ?? '');
    
    $topType   = trim($options['top_type'] ?? 'image');
    $topAsset  = trim($options['top_asset'] ?? 'default.png');
    
    $skin = $activeSkin->folder_name ?? ($skinSettings['active_skin'] ?? 'default');
@endphp

<div class="flex items-center justify-center mb-6" 
     x-data
     aria-label="{{ lexicon('tooltip_common_CharacterRank', 'Character Rank') }}"
     x-tooltip="'{{ addslashes(lexicon('tooltip_common_CharacterRank', 'Character Rank')) }}'">
    <x-rank-badge 
        :rankSet="$rankSet" 
        :baseLayer="$baseLayer" 
        :departmentColor="$deptColor" 
        :midType="$midType"
        :midImage="$midType === 'image' ? $midAsset : null"
        :midCss="$midType === 'css' ? $midAsset : ''"
        :topType="$topType"
        :topImage="$topType === 'image' ? $topAsset : null"
        :topCss="$topType === 'css' ? $topAsset : ''"
        :skin="$skin" 
    />
</div>