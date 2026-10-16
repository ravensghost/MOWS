@props([
    'variant' => 'primary', // Accepts: primary, info, success, warning, danger, planned, active, completed
])

@php
    $baseClasses = 'inline-block items-center justify-center p-badge text-xs font-bold leading-none rounded-pill whitespace-nowrap ';
    
    $variantClasses = match(strtolower((string) $variant)) {
        'primary'             => 'bg-badge-primary text-p border-pc',
        'info'                => 'bg-badge-info text-p border-info',
        'success', 'active'   => 'bg-badge-success text-success border-success',
        'warning'             => 'bg-badge-warning text-warning border-warning',
        'danger'              => 'bg-badge-danger text-danger-light border-danger',
        'planned'             => 'bg-panel-header text-p border-panel',
        'completed'           => 'bg-panel-header text-muted border-panel',
        default               => 'bg-badge-primary text-p border-pc',
    };
@endphp

<span {{ $attributes->merge(['class' => $baseClasses . $variantClasses]) }}>
    {{ $slot }}
</span>