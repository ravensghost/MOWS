{{-- 
@WidgetManifest({
    "name": "Discord Server",
    "description": "Embeds a Discord server widget.",
    "icon": "bi-discord",
    "fields": {
        "server_id": { "type": "text", "label": "Discord Server ID", "default": "" },
        "theme": { "type": "dropdown", "label": "Theme", "options": {"dark": "Dark", "light": "Light"}, "default": "dark" }
    }
})
--}}
@php
    $options = is_string($widget->pivot->instance_options) ? json_decode($widget->pivot->instance_options, true) : ($widget->pivot->instance_options ?? []);
    $serverId = trim($options['server_id'] ?? '');
    $theme = trim($options['theme'] ?? 'dark');
@endphp

<div class="bg-panel border border-panel rounded-panel shadow-panel flex flex-col overflow-hidden w-full mb-6 p-0" x-data>
    @if($serverId)
        <iframe src="https://discord.com/widget?id={{ $serverId }}&theme={{ $theme }}" 
                width="100%" 
                height="400" 
                allowtransparency="true" 
                frameborder="0" 
                aria-label="{{ lexicon('label_common_DiscordWidget', 'Discord Server Widget') }}"
                sandbox="allow-popups allow-popups-to-escape-sandbox allow-same-origin allow-scripts" 
                class="w-full border-none"></iframe>
    @else
        <div class="p-panel text-center">
            <span class="text-muted italic">{{ lexicon('msg_common_DiscordNotConfigured', 'Discord server ID has not been configured.') }}</span>
        </div>
    @endif
</div>