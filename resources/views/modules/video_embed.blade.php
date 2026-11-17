{{-- resources/views/modules/video_embed.blade.php --}}
{{-- @WidgetManifest({ "name": "Video & Soundtrack Player", "description": "Embeds an iframe for external videos (e.g. YouTube) or an ambient scene soundtrack.", "icon": "bi-play-btn", "fields": { "title": { "type": "text", "label": "Media / Soundtrack Title", "default": "Video Player" }, "url": { "type": "text", "label": "Iframe Embed or Audio URL", "default": "" }, "ambient_mode": { "type": "dropdown", "label": "Display Mode", "options": {"standard": "Standard Video Player", "ambient": "Compact Ambient Soundtrack Bar"}, "default": "standard" } } }) --}}
@php
    $rawOptions = $moduleSettings
        ?? $module?->pivot?->book_config
        ?? $module?->pivot?->instance_options
        ?? $widget?->pivot?->instance_options
        ?? [];
    $options = is_string($rawOptions) ? (json_decode($rawOptions, true) ?: []) : (is_array($rawOptions) ? $rawOptions : []);

    $rawTitle = trim((string) ($options['title'] ?? ''));
    $title = $rawTitle !== '' ? $rawTitle : lexicon('label_common_VideoPlayer', 'Video Player');
    $url = trim((string) ($options['url'] ?? ''));
    $ambientMode = trim((string) ($options['ambient_mode'] ?? 'standard'));
    $isAudioFile = (bool) preg_match('/\.(mp3|ogg|wav|m4a|webm)(\?.*)?$/i', $url);
@endphp

<x-tab type="accordion"
       :title="$title"
       :icon="$ambientMode === 'ambient' || $isAudioFile ? 'bi-music-note-beamed' : 'bi-play-btn'"
       :open="$ambientMode !== 'ambient'">
    <div x-data="{
             muted: window.Storage ? Boolean(window.Storage.get('mows_soundtrack_muted', false)) : false,
             toggleMute() {
                 this.muted = !this.muted;
                 if (window.Storage) {
                     window.Storage.set('mows_soundtrack_muted', this.muted);
                 }
                 if (this.$refs.audioPlayer) {
                     this.$refs.audioPlayer.muted = this.muted;
                 }
             }
         }"
         x-init="if ($refs.audioPlayer) { $refs.audioPlayer.muted = muted; }"
         class="flex flex-col items-center justify-center gap-sm w-full">
        @if($url !== '')
            @if($isAudioFile)
                <div class="w-full flex flex-row flex-wrap items-center justify-between gap-sm bg-panel-sub border-panel border-rounded p-3">
                    <audio x-ref="audioPlayer"
                           controls
                           loop
                           class="flex-grow"
                           aria-label="{{ $title }}">
                        <source src="{{ $url }}">
                    </audio>
                    <x-button type="button"
                              variant="secondary"
                              size="sm"
                              ::icon="muted ? 'bi-volume-mute-fill' : 'bi-volume-up-fill'"
                              :tooltip="lexicon('tooltip_Soundtrack_ToggleMute', 'Toggle soundtrack mute preference')"
                              @click="toggleMute()">
                        <span x-text="muted ? '{{ addslashes(lexicon('btn_Soundtrack_Unmute', 'Unmute')) }}' : '{{ addslashes(lexicon('btn_Soundtrack_Mute', 'Mute')) }}'"></span>
                    </x-button>
                </div>
            @else
                <div class="w-full overflow-hidden border-rounded border-panel">
                    <iframe src="{{ $url }}"
                            width="100%"
                            height="{{ $ambientMode === 'ambient' ? '120' : '400' }}"
                            title="{{ $title }}"
                            aria-label="{{ $title }}"
                            class="w-full block border-none"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen></iframe>
                </div>
            @endif
        @else
            <div class="bg-panel-sub border-dashed-panel border-rounded p-panel-body text-center w-full">
                <span class="text-sm text-muted font-italic">{{ lexicon('msg_common_VideoNotConfigured', 'No video or soundtrack URL has been configured.') }}</span>
            </div>
        @endif
    </div>
</x-tab>