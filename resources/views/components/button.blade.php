@props([
    'variant' => 'primary',
    'type' => 'button',
    'href' => null,
])

@php
    $baseClasses = 'font-bold rounded-input transition-btn cursor-pointer inline-block items-center justify-center text-center box-border no-underline ';
    
    $variantClasses = match($variant) {
        'primary'   => 'bg-primary text-input border-transparent shadow-btn hover-brightness-110 p-btn',
        'secondary' => 'bg-secondary text-btn-secondary border-secondary shadow hover-bg-border p-btn',
        'success'   => 'bg-success text-input border-success shadow-btn-sm hover-brightness-110 p-btn',
        'danger'    => 'bg-danger text-btn-danger border-btn-danger shadow-btn-sm hover-bg-danger p-btn',
        'warning'   => 'bg-warning text-input border-warning shadow-btn hover-brightness-110 p-btn',
        'text'      => 'bg-transparent text-muted border-none hover-text-h p-0',
        default     => 'bg-primary text-input border-transparent shadow-btn hover-brightness-110 p-btn',
    };

    $mergedClasses = $baseClasses . $variantClasses;
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $mergedClasses]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $mergedClasses]) }}>
        {{ $slot }}
    </button>
@endif