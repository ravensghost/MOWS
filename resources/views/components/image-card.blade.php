@props(['url', 'alt' => '', 'gallery' => null])
@php
    $fallbackAlt = $alt ?: lexicon('label_common_Image', 'Image');
@endphp

@if($url)
    <a href="{{ $url }}" 
       class="glightbox block w-full overflow-hidden rounded-input group/img relative {{ $attributes->get('class') }}"
       @if($gallery) data-gallery="{{ $gallery }}" @endif
       data-glightbox="title: {{ $fallbackAlt }}"
       aria-label="{{ $fallbackAlt }}"
       x-tooltip="'{{ addslashes($fallbackAlt) }}'">
        <img src="{{ $url }}" 
             alt="{{ $fallbackAlt }}" 
             class="w-full h-auto object-cover rounded-input border border-panel transition-transform duration-300 group-hover/img:scale-105 block">
        <div class="absolute inset-0 bg-black/40 opacity-0 group-hover/img:opacity-100 transition-opacity flex items-center justify-center text-white text-sm font-bold gap-xs rounded-input">
            <i class="bi bi-zoom-in"></i> {{ lexicon('btn_common_Zoom', 'Zoom') }}
        </div>
    </a>
@else
    <div class="bg-panel-sub border-dashed-panel rounded-panel p-panel text-center w-full {{ $attributes->get('class') }}">
        <span class="text-muted italic">{{ lexicon('msg_common_ImageNotConfigured', 'No image URL has been configured.') }}</span>
    </div>
@endif