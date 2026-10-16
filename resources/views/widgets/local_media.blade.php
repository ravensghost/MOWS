{{-- 
@WidgetManifest({
    "name": "Local Media Player",
    "description": "Embeds a local audio or video file.",
    "icon": "bi-play-circle",
    "fields": {
        "title": { "type": "text", "label": "Title", "default": "Media Player" },
        "media_type": { "type": "dropdown", "label": "Media Type", "options": {"video": "Video", "audio": "Audio"}, "default": "video" },
        "url": { "type": "text", "label": "File URL (e.g. /audio/theme.mp3)", "default": "" }
    }
})
--}}
@php
    $options = is_string($widget->pivot->instance_options) ? json_decode($widget->pivot->instance_options, true) : ($widget->pivot->instance_options ?? []);
    $title = trim($options['title'] ?? 'Media Player');
    $type = strtolower(trim($options['media_type'] ?? 'video'));
    $url = trim($options['url'] ?? '');
    $playerLabel = $title ?: lexicon('label_common_MediaPlayer', 'Media Player');
@endphp

<div class="bg-panel border border-panel rounded-panel shadow-panel flex flex-col overflow-hidden w-full mb-6" x-data>
    @if($title)
        <div class="bg-panel-header text-h border-b border-panel p-panel-header font-bold">
            <span class="text-xl font-bold text-accent">{{ $title }}</span>
        </div>
    @endif
    
    <div class="p-panel flex items-center justify-center">
        @if(!$url)
            <div class="bg-panel-sub border-dashed-panel rounded-panel p-panel text-center w-full">
                <span class="text-muted italic">{{ lexicon('msg_common_MediaNotConfigured', 'No media file URL has been configured.') }}</span>
            </div>
        @elseif($type === 'audio')
            <audio controls class="w-full" aria-label="{{ $playerLabel }}">
                <source src="{{ $url }}" type="audio/mpeg">
                <source src="{{ $url }}" type="audio/ogg">
                <span>{{ lexicon('msg_common_AudioNotSupported', 'Your browser does not support the audio element.') }}</span>
            </audio>
        @else
            <video controls class="w-full h-auto rounded-input border border-panel" aria-label="{{ $playerLabel }}">
                <source src="{{ $url }}" type="video/mp4">
                <source src="{{ $url }}" type="video/webm">
                <span>{{ lexicon('msg_common_VideoNotSupported', 'Your browser does not support the video element.') }}</span>
            </video>
        @endif
    </div>
</div>