{{-- resources/views/modules/message.blade.php --}}
{{-- @WidgetManifest({ "name": "Scrolling Marquee", "description": "A scrolling text ticker for announcements.", "icon": "bi-cursor-text", "fields": { "heading_text": { "type": "text", "label": "Heading Text", "default": "ALERT" }, "message_body": { "type": "text", "label": "Message Body", "default": "Important announcement here." } } }) --}}
@php
    $rawOptions = $moduleSettings
        ?? $module?->pivot?->book_config
        ?? $module?->pivot?->instance_options
        ?? $widget?->pivot?->instance_options
        ?? [];
    $options = is_string($rawOptions) ? (json_decode($rawOptions, true) ?: []) : (is_array($rawOptions) ? $rawOptions : []);

    $headingText = trim((string) ($options['heading_text'] ?? ''));
    $messageBody = trim((string) ($options['message_body'] ?? ''));
@endphp

<x-tab type="panel">
    @if($headingText !== '' || $messageBody !== '')
        <div class="bg-panel-sub border-panel border-rounded p-3 flex flex-row flex-wrap items-center gap-sm overflow-hidden w-full">
            @if($headingText !== '')
                <x-badge type="pill"
                         variant="warning"
                         icon="bi-megaphone-fill"
                         :tooltip="lexicon('tooltip_Marquee_AlertBadge', 'Station Announcement')">
                    {{ $headingText }}
                </x-badge>
            @endif

            @if($messageBody !== '')
                <div class="text-sm text-p flex-grow overflow-x-auto whitespace-nowrap">
                    {!! \Stevebauman\Purify\Facades\Purify::clean($messageBody) !!}
                </div>
            @endif
        </div>
    @else
        <div class="bg-panel-sub border-dashed-panel border-rounded p-panel-body text-center">
            <span class="text-sm text-muted font-italic">{{ lexicon('msg_common_MarqueeEmpty', 'No announcement configured.') }}</span>
        </div>
    @endif
</x-tab>