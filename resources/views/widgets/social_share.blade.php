{{-- 
@WidgetManifest({
    "name": "Social Share",
    "description": "Adds buttons to share the current page.",
    "icon": "bi-share",
    "fields": {
        "title": { "type": "text", "label": "Widget Title", "default": "Share This Page" }
    }
})
--}}
@php
    $options = is_string($widget->pivot->instance_options) ? json_decode($widget->pivot->instance_options, true) : ($widget->pivot->instance_options ?? []);
    $title = trim($options['title'] ?? 'Share This Page');
    
    // Grabs the current page URL and encodes it safely for a query string
    $rawUrl = request()->url();
    $currentUrl = urlencode($rawUrl);
@endphp

<div class="bg-panel border border-panel rounded-panel shadow-panel flex flex-col overflow-hidden w-full mb-6 p-panel gap-md text-center" x-data>
    @if($title)
        <span class="font-bold text-h block text-sm uppercase tracking-wider">{{ $title }}</span>
    @endif
    <div class="grid grid-cols-1 lg-grid-cols-3 gap-md w-full">
        {{-- X / Twitter Share Navigation Link --}}
        <a href="https://twitter.com/intent/tweet?url={{ $currentUrl }}" 
           target="_blank" 
           rel="noopener noreferrer"
           aria-label="{{ lexicon('tooltip_nav_ShareTwitter', 'Share on X (Twitter)') }}"
           x-tooltip="'{{ addslashes(lexicon('tooltip_nav_ShareTwitter', 'Share on X (Twitter)')) }}'"
           class="bg-secondary text-btn-secondary border border-secondary rounded-input p-btn-sm text-sm font-bold transition-btn hover-bg-border flex items-center justify-center gap-xs no-underline">
            <i class="bi bi-twitter-x" aria-hidden="true"></i>
            <span>{{ lexicon('btn_common_ShareOnX', 'Share on X') }}</span>
        </a>
        
        {{-- Facebook Share Navigation Link --}}
        <a href="https://www.facebook.com/sharer/sharer.php?u={{ $currentUrl }}" 
           target="_blank" 
           rel="noopener noreferrer"
           aria-label="{{ lexicon('tooltip_nav_ShareFacebook', 'Share on Facebook') }}"
           x-tooltip="'{{ addslashes(lexicon('tooltip_nav_ShareFacebook', 'Share on Facebook')) }}'"
           class="bg-primary text-input border border-transparent rounded-input p-btn-sm text-sm font-bold shadow-btn transition-btn hover-brightness-110 flex items-center justify-center gap-xs no-underline">
            <i class="bi bi-facebook" aria-hidden="true"></i>
            <span>{{ lexicon('btn_common_Facebook', 'Facebook') }}</span>
        </a>

        {{-- Copy Page Link Action Button --}}
        <x-button type="button"
                  variant="secondary"
                  x-clipboard="'{{ addslashes($rawUrl) }}'"
                  aria-label="{{ lexicon('tooltip_common_CopyPageUrl', 'Copy page link to clipboard') }}"
                  x-tooltip="'{{ addslashes(lexicon('tooltip_common_CopyPageUrl', 'Copy page link to clipboard')) }}'">
            <i class="bi bi-link-45deg mr-1" aria-hidden="true"></i>
            <span>{{ lexicon('btn_common_CopyLink', 'Copy Link') }}</span>
        </x-button>
    </div>
</div>