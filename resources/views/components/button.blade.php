{{-- resources/views/components/button.blade.php --}}
@props([
    'variant' => 'primary',
    'size' => 'md',
    'icon' => null,
    'tooltip' => null,
    'type' => 'button',
    'href' => null,
])

@php
    $baseClasses = 'flex flex-row items-center justify-center gap-xs font-bold border-rounded cursor-pointer transition-btn select-none whitespace-nowrap no-underline ';

    $sizeClasses = match($size) {
        'sm'    => 'p-btn-sm text-xs shadow-btn-sm ',
        'lg'    => 'p-btn-lg text-m shadow-btn ',
        default => 'p-btn text-sm shadow-btn ',
    };

    $variantClasses = match($variant) {
        'secondary'      => 'bg-secondary text-btn-secondary border-secondary hover-bg-border hover-text-h',
        'danger'         => 'bg-danger text-btn-danger border-btn-danger hover-bg-danger hover-text-h',
        'success'        => 'bg-success text-h border-success hover-brightness-110',
        'warning'        => 'bg-warning text-h border-warning hover-brightness-110',
        'ghost', 'text'  => 'bg-transparent text-muted border-transparent hover-bg-panel-hover hover-text-h',
        default          => 'bg-primary text-h border-accent hover-brightness-110',
    };

    $mergedClasses = $baseClasses . $sizeClasses . $variantClasses;
@endphp

@if($href)
    <a href="{{ $href }}"
       @if($tooltip)
           x-tooltip="'{{ addslashes($tooltip) }}'"
           title="{{ $tooltip }}"
           aria-label="{{ $tooltip }}"
       @endif
       {{ $attributes->merge(['class' => $mergedClasses]) }}>
        @if($icon)
            <i class="bi {{ $icon }}"></i>
        @endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}"
            @if($tooltip)
                x-tooltip="'{{ addslashes($tooltip) }}'"
                title="{{ $tooltip }}"
                aria-label="{{ $tooltip }}"
            @endif
            {{ $attributes->merge(['class' => $mergedClasses]) }}>
        @if($icon)
            <i class="bi {{ $icon }}"></i>
        @endif
        {{ $slot }}
    </button>
@endif