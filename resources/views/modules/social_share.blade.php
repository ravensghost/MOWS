{{-- resources/views/modules/social_share.blade.php --}}
{{-- @WidgetManifest({ "name": "Social Share", "description": "Adds buttons to share the current page.", "icon": "bi-share", "fields": { "title": { "type": "text", "label": "Module Title", "default": "Share This Page" } } }) --}}
@php
    $rawOptions = $moduleSettings
        ?? $module?->pivot?->book_config
        ?? $module?->pivot?->instance_options
        ?? $widget?->pivot?->instance_options
        ?? [];
    $options = is_string($rawOptions) ? (json_decode($rawOptions, true) ?: []) : (is_array($rawOptions) ? $rawOptions : []);

    $rawTitle = trim((string) ($options['title'] ?? ''));
    $title = $rawTitle !== '' ? $rawTitle : lexicon('label_SocialShare_DefaultTitle', 'Share This Page');

    $rawUrl = request()->fullUrl();
    $currentUrl = urlencode($rawUrl);
@endphp

<x-tab type="accordion"
       :title="$title"
       icon="bi-share"
       :open="true">
    <div class="flex flex-row flex-wrap items-center justify-center gap-sm w-full" x-data>
        {{-- X (Twitter) Share Button --}}
        <x-button :href="'https://twitter.com/intent/tweet?url=' . $currentUrl"
                  target="_blank"
                  rel="noopener noreferrer"
                  variant="primary"
                  size="sm"
                  icon="bi-twitter-x"
                  :tooltip="lexicon('tooltip_nav_ShareTwitter', 'Share on X (Twitter)')">
            {{ lexicon('btn_common_TwitterX', 'X / Twitter') }}
        </x-button>

        {{-- Facebook Share Button --}}
        <x-button :href="'https://www.facebook.com/sharer/sharer.php?u=' . $currentUrl"
                  target="_blank"
                  rel="noopener noreferrer"
                  variant="primary"
                  size="sm"
                  icon="bi-facebook"
                  :tooltip="lexicon('tooltip_nav_ShareFacebook', 'Share on Facebook')">
            {{ lexicon('btn_common_Facebook', 'Facebook') }}
        </x-button>

        {{-- Copy Page Link Action Button --}}
        <x-button type="button"
                  variant="secondary"
                  size="sm"
                  icon="bi-link-45deg"
                  :tooltip="lexicon('tooltip_common_CopyPageUrl', 'Copy page link to clipboard')"
                  x-clipboard="'{{ addslashes($rawUrl) }}'">
            {{ lexicon('btn_common_CopyLink', 'Copy Link') }}
        </x-button>
    </div>
</x-tab>