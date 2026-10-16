{{-- 
@WidgetManifest({
    "name": "Scrolling Marquee",
    "description": "A scrolling text ticker for announcements.",
    "icon": "bi-cursor-text",
    "fields": {
        "heading_text": { 
            "type": "text", 
            "label": "Heading Text", 
            "default": "ALERT" 
        },
        "message_body": { 
            "type": "text", 
            "label": "Message Body", 
            "default": "Important announcement here." 
        }
    }
})
--}}
@php
    $options = is_string($widget->pivot->instance_options) ? json_decode($widget->pivot->instance_options, true) : ($widget->pivot->instance_options ?? []);
    
    $headingText = trim($options['heading_text'] ?? '');
    $messageBody = trim($options['message_body'] ?? '');
@endphp

<div class="bg-panel border border-panel rounded-panel shadow-panel w-full overflow-hidden whitespace-nowrap flex items-center px-4 py-3 group-hover-pause mb-6" x-data>
    @if($headingText || $messageBody)
        <div class="inline-block pl-full will-change-transform animate-marquee">
            @if($headingText)
                <span class="text-accent font-bold uppercase tracking-wider pr-2 inline-block m-0 text-base">
                    {{ $headingText }}
                </span>
            @endif
            
            @if($messageBody)
                <span class="text-p">{!! \Stevebauman\Purify\Facades\Purify::clean($messageBody) !!}</span>
            @endif
        </div>
    @else
        <span class="text-muted italic text-sm">{{ lexicon('msg_common_MarqueeEmpty', 'No announcement configured.') }}</span>
    @endif
</div>