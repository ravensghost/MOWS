{{-- resources/views/modules/rank_badge.blade.php --}}
{{-- @WidgetManifest({ "name": "Rank Badge", "description": "Displays a standalone rank badge.", "icon": "bi-shield-check", "fields": { "rank_set": { "type": "text", "label": "Rank Set Folder", "default": "DefaultSet" }, "base_color": { "type": "text", "label": "Base Colour (Hex)", "default": "#1a1a1aFF" }, "dept_color": { "type": "text", "label": "Department Colour (Hex)", "default": "#ffffffFF" }, "mid_type": { "type": "dropdown", "label": "Mid Type", "options": {"css": "CSS Class", "image": "Image"}, "default": "css" }, "mid_asset": { "type": "text", "label": "Mid Asset", "default": "" }, "top_type": { "type": "dropdown", "label": "Top Type", "options": {"css": "CSS Class", "image": "Image"}, "default": "image" }, "top_asset": { "type": "text", "label": "Top Asset", "default": "default.png" } } }) --}}
@php
    $rawOptions = $moduleSettings
        ?? $module?->pivot?->book_config
        ?? $module?->pivot?->instance_options
        ?? $widget?->pivot?->instance_options
        ?? [];
    $options = is_string($rawOptions) ? (json_decode($rawOptions, true) ?: []) : (is_array($rawOptions) ? $rawOptions : []);

    $rankSet    = trim((string) ($options['rank_set'] ?? 'DefaultSet'));
    $baseLayer  = trim((string) ($options['base_color'] ?? '#1a1a1aFF'));
    $deptColour = trim((string) ($options['dept_color'] ?? '#ffffffFF'));

    $midType  = trim((string) ($options['mid_type'] ?? 'css'));
    $midAsset = trim((string) ($options['mid_asset'] ?? ''));

    $topType  = trim((string) ($options['top_type'] ?? 'image'));
    $topAsset = trim((string) ($options['top_asset'] ?? 'default.png'));

    $skin = $activeSkin?->directory_name ?? ($skinSettings['active_skin'] ?? 'default');
@endphp

<x-tab type="panel">
    <div class="flex items-center justify-center p-2 w-full">
        <x-badge type="insignia"
                 :rankSet="$rankSet"
                 :baseLayer="$baseLayer"
                 :departmentColour="$deptColour"
                 :midLayerMode="$midType"
                 :midLayerAsset="$midType === 'image' ? $midAsset : null"
                 :midLayerModifier="$midType === 'css' ? $midAsset : ''"
                 :topLayerMode="$topType"
                 :topLayerAsset="$topType === 'image' ? $topAsset : null"
                 :topLayerModifier="$topType === 'css' ? $topAsset : ''"
                 :skin="$skin"
                 :tooltip="lexicon('tooltip_common_CharacterRank', 'Character Rank')" />
    </div>
</x-tab>