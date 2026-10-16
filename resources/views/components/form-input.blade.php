@props([
    'name',
    'label',
    'id' => null,
    'type' => 'text',
    'value' => '',
    'placeholder' => '',
    'required' => false,
    'autocomplete' => '',
    'prefix' => null,
    'tooltip' => null,
    'xModel' => null,
])

@php
    $inputId = $id ?? $name;
@endphp

<div class="flex flex-col gap-xs">
    <label for="{{ $inputId }}" class="text-xs font-bold text-input-muted">
        <span>{{ $label }}</span>
        @if($required)
            <span class="text-required">*</span>
        @endif
    </label>
    
    @if($prefix)
        <div class="flex flex-row flex-nowrap items-center bg-input border-input rounded-input overflow-hidden focus-within-border-accent focus-within-shadow-accent"
             @if($tooltip) x-tooltip="'{{ addslashes($tooltip) }}'" @endif>
            <span class="px-2 text-sm text-muted bg-panel-header h-input flex items-center justify-center select-none">{{ $prefix }}</span>
            <input id="{{ $inputId }}" name="{{ $name }}" type="{{ $type }}" 
                   {{ $attributes->merge(['class' => 'bg-transparent text-input border-none p-input outline-none h-input w-full box-border']) }}
                   value="{{ $value }}" placeholder="{{ $placeholder }}" {{ $required ? 'required' : '' }}
                   @if($xModel) x-model="{{ $xModel }}" @endif
                   @if($autocomplete) autocomplete="{{ $autocomplete }}" @endif>
        </div>
    @else
        <input id="{{ $inputId }}" name="{{ $name }}" type="{{ $type }}" 
               {{ $attributes->merge(['class' => 'bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent']) }}
               value="{{ $value }}" placeholder="{{ $placeholder }}" {{ $required ? 'required' : '' }}
               @if($xModel) x-model="{{ $xModel }}" @endif
               @if($autocomplete) autocomplete="{{ $autocomplete }}" @endif
               @if($tooltip) x-tooltip="'{{ addslashes($tooltip) }}'" @endif>
    @endif
</div>