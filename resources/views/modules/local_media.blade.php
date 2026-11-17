{{-- resources/views/modules/local_media.blade.php --}}
{{-- @WidgetManifest({ "name": "Local Media Player", "description": "Embeds a local audio or video file.", "icon": "bi-play-circle", "fields": { "title": { "type": "text", "label": "Title", "default": "Media Player" }, "media_type": { "type": "dropdown", "label": "Media Type", "options": {"video": "Video", "audio": "Audio"}, "default": "video" }, "url": { "type": "text", "label": "File URL (e.g. /audio/theme.mp3)", "default": "" } } }) --}}
@php
    $rawOptions = $moduleSettings
        ?? $module?->pivot?->book_config
        ?? $module?->pivot?->instance_options
        ?? $widget?->pivot?->instance_options
        ?? [];
    $options = is_string($rawOptions) ? (json_decode($rawOptions, true) ?: []) : (is_array($rawOptions) ? $rawOptions : []);

    $rawTitle = trim((string) ($options['title'] ?? ''));
    $title = $rawTitle !== '' ? $rawTitle : lexicon('label_common_MediaPlayer', 'Media Player');
    $type = strtolower(trim((string) ($options['media_type'] ?? 'video')));
    $url = trim((string) ($options['url'] ?? ''));
@endphp

<x-tab type="accordion"
       :title="$title"
       :icon="$type === 'audio' ? 'bi-music-note-beamed' : 'bi-play-circle'"
       :open="true">
    <div class="flex items-center justify-center w-full">
        @if($url === '')
            <div class="bg-panel-sub border-dashed-panel border-rounded p-panel-body text-center w-full">
                <span class="text-sm text-muted font-italic">{{ lexicon('msg_common_MediaNotConfigured', 'No media file URL has been configured.') }}</span>
            </div>
        @elseif($type === 'audio')
            <audio controls
                   class="w-full"
                   aria-label="{{ $title }}">
                <source src="{{ $url }}" type="audio/mpeg">
                <source src="{{ $url }}" type="audio/ogg">
                <span>{{ lexicon('msg_common_AudioNotSupported', 'Your browser does not support the audio element.') }}</span>
            </audio>
        @else
            <video controls
                   class="w-full h-auto border-rounded border-panel block"
                   aria-label="{{ $title }}">
                <source src="{{ $url }}" type="video/mp4">
                <source src="{{ $url }}" type="video/webm">
                <span>{{ lexicon('msg_common_VideoNotSupported', 'Your browser does not support the video element.') }}</span>
            </video>
        @endif
    </div>
</x-tab>