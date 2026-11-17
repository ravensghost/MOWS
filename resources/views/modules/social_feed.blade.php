{{-- resources/views/modules/social_feed.blade.php --}}
{{-- @WidgetManifest({ "name": "Social Feed Embed", "description": "Embeds a raw social media script or iframe.", "icon": "bi-chat-quote", "fields": { "platform": { "type": "text", "label": "Platform Name", "default": "Social" }, "embed_code": { "type": "textarea", "label": "Native Embed HTML", "default": "" } } }) --}}
@php
    $rawOptions = $moduleSettings
        ?? $module?->pivot?->book_config
        ?? $module?->pivot?->instance_options
        ?? $widget?->pivot?->instance_options
        ?? [];
    $options = is_string($rawOptions) ? (json_decode($rawOptions, true) ?: []) : (is_array($rawOptions) ? $rawOptions : []);

    $rawPlatform = trim((string) ($options['platform'] ?? ''));
    $platform = $rawPlatform !== '' ? $rawPlatform : lexicon('label_SocialFeed_DefaultTitle', 'Social Feed');
    $embedCode = trim((string) ($options['embed_code'] ?? ''));
@endphp

<x-tab type="accordion"
       :title="$platform"
       icon="bi-chat-quote"
       :open="true">
    <div class="flex flex-row flex-wrap items-center justify-center overflow-x-auto w-full">
        @if($embedCode !== '')
            {!! $embedCode !!}
        @else
            <div class="bg-panel-sub border-dashed-panel border-rounded p-panel-body text-center w-full">
                <span class="text-sm text-muted font-italic">{{ lexicon('msg_common_SocialFeedEmpty', 'No social feed embed code configured.') }}</span>
            </div>
        @endif
    </div>
</x-tab>