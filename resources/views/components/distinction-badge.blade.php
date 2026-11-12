@props([
    'distinctionTitle' => 'Distinction',
    'distinctionDescription' => null,
    'distinctionBaseColour' => '#1a1a1a',
    'midLayerMode' => 'css',
    'midLayerAsset' => null,
    'midLayerModifier' => null,
    'topLayerMode' => 'image',
    'topLayerAsset' => null,
    'topLayerModifier' => null,
    'skin' => 'default',
])

@php
    $resolvedMidClass = $midLayerModifier ? 'award-ml-' . $midLayerModifier : '';
    $resolvedTopClass = $topLayerModifier ? 'award-tl-' . $topLayerModifier : '';
    $tooltipText = $distinctionDescription
        ? $distinctionDescription . ' (' . $distinctionTitle . ')'
        : $distinctionTitle;
@endphp

<div class="ui-award-badge" 
     x-data="{ imgError: false }"
     x-tooltip="'{{ addslashes($tooltipText) }}'"
     aria-label="{{ $tooltipText }}"
     style="--badge-base: {{ $distinctionBaseColour }};">
  
    @if($midLayerMode === 'image' && $midLayerAsset)
        <img src="/skins/{{ $skin }}/images/awards/{{ rawurlencode($midLayerAsset) }}" 
             class="ui-award-layer ui-award-layer-mid" 
             alt="{{ $distinctionTitle }}">
    @elseif($midLayerMode === 'css')
        <div class="ui-award-layer ui-award-layer-mid {{ $resolvedMidClass }}"></div>
    @endif
  
    @if($topLayerMode === 'image' && $topLayerAsset)
        <img src="/skins/{{ $skin }}/images/awards/{{ rawurlencode($topLayerAsset) }}" 
             x-show="!imgError"
             x-on:error="imgError = true" 
             class="ui-award-layer ui-award-layer-top" 
             alt="{{ $topLayerAsset }}">
    @elseif($topLayerMode === 'css' && $topLayerModifier)
        <div class="ui-award-layer ui-award-layer-top {{ $resolvedTopClass }}"></div>
    @endif
</div>