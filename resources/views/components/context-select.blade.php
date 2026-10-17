@props([
    'name' => 'display_target',
    'id' => null,
    'label' => null,
    'value' => 'BOTH',
    'xModel' => null,
    'required' => false,
    'bare' => false,
    'tooltip' => null,
    'detailedLabels' => false,
])

@php
    $selectId = $id ?? $name;
    $resolvedLabel = $label ?? lexicon('label_ServiceRecords_IcOoc', 'Display Target (IC/OOC)');
    $resolvedTooltip = $tooltip ?? lexicon('tooltip_ServiceRecords_SelectTargetScope', 'Select display target scope');
@endphp

@if(!$bare)
<div class="flex flex-col gap-xs">
    <label for="{{ $selectId }}" class="text-xs font-bold text-input-muted">
        <span>{{ $resolvedLabel }}</span>
        @if($required)
            <span class="text-required">*</span>
        @endif
    </label>
@endif

    <select @if(!$bare) id="{{ $selectId }}" @endif
            name="{{ $name }}"
            aria-label="{{ $resolvedLabel }}"
            x-tooltip="'{{ addslashes($resolvedTooltip) }}'"
            @if($xModel) x-model="{{ $xModel }}" @endif
            {{ $required ? 'required' : '' }}
            {{ $attributes->merge(['class' => 'bg-input text-input border-input rounded-input p-input outline-none h-input box-border focus-border-accent focus-shadow-accent' . ($bare ? '' : ' w-full')]) }}>
        @if($detailedLabels)
            <option value="BOTH" @selected($value === 'BOTH')>{{ lexicon('label_awards_OptBoth', 'BOTH (User & Character)') }}</option>
            <option value="IC" @selected($value === 'IC')>{{ lexicon('label_awards_OptIC', 'IC (Character Only)') }}</option>
            <option value="OOC" @selected($value === 'OOC')>{{ lexicon('label_awards_OptOOC', 'OOC (User Only)') }}</option>
        @else
            <option value="BOTH" @selected($value === 'BOTH')>{{ lexicon('label_ServiceRecords_OptBoth', 'BOTH') }}</option>
            <option value="IC" @selected($value === 'IC')>{{ lexicon('label_ServiceRecords_OptIcOnly', 'IC Only') }}</option>
            <option value="OOC" @selected($value === 'OOC')>{{ lexicon('label_ServiceRecords_OptOocOnly', 'OOC Only') }}</option>
        @endif
    </select>

@if(!$bare)
</div>
@endif