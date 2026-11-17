{{-- resources/views/modules/discord.blade.php --}}
{{-- @WidgetManifest({ "name": "Discord Server", "description": "Embeds a Discord server widget.", "icon": "bi-discord", "fields": { "title": { "type": "text", "label": "Module Title", "default": "Discord Comms" }, "server_id": { "type": "text", "label": "Discord Server ID", "default": "" }, "theme": { "type": "dropdown", "label": "Theme", "options": {"dark": "Dark", "light": "Light"}, "default": "dark" } } }) --}}
@php
    $rawOptions = $moduleSettings
        ?? $module?->pivot?->book_config
        ?? $module?->pivot?->instance_options
        ?? $widget?->pivot?->instance_options
        ?? [];
    $options = is_string($rawOptions) ? (json_decode($rawOptions, true) ?: []) : (is_array($rawOptions) ? $rawOptions : []);

    $rawTitle = trim($options['title'] ?? '');
    $title = $rawTitle !== '' ? $rawTitle : lexicon('label_Discord_DefaultTitle', 'Discord Comms');
    $serverId = trim($options['server_id'] ?? '');
    $theme = in_array(trim($options['theme'] ?? 'dark'), ['dark', 'light'], true) ? trim($options['theme']) : 'dark';
@endphp

<x-tab type="accordion"
       :title="$title"
       icon="bi-discord"
       :open="true">
    @if($serverId !== '')
        <div class="w-full overflow-hidden border-rounded border-panel">
            <iframe src="https://discord.com/widget?id={{ rawurlencode($serverId) }}&theme={{ $theme }}"
                    width="100%"
                    height="400"
                    allowtransparency="true"
                    frameborder="0"
                    sandbox="allow-popups allow-popups-to-escape-sandbox allow-same-origin allow-scripts"
                    title="{{ $title }}"
                    class="w-full block border-none"></iframe>
        </div>
    @else
        <div class="bg-panel-sub border-dashed-panel border-rounded p-panel-body text-center">
            <span class="text-sm text-muted font-italic">{{ lexicon('msg_common_DiscordNotConfigured', 'Discord Server ID has not been configured.') }}</span>
        </div>
    @endif
</x-tab>