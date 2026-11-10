@props([
    'distinctionTitle' => null,
    'awardName' => 'Distinction',
    'distinctionDescription' => null,
    'description' => null,
    'distinctionBaseColour' => null,
    'baseLayer' => '#1a1a1a',
    'midLayerMode' => null,
    'midMode' => null,
    'midType' => 'css',
    'midLayerAsset' => null,
    'midImage' => null,
    'midLayerModifier' => null,
    'midCss' => null,
    'topLayerMode' => null,
    'topMode' => null,
    'topType' => null,
    'topLayerAsset' => null,
    'topImage' => null,
    'topLayerModifier' => null,
    'topCss' => null,
    'skin' => 'default'
])

@php
    $resolvedTitle = $distinctionTitle ?? $awardName;
    $resolvedDescription = $distinctionDescription ?? $description;
    $resolvedBaseColour = $distinctionBaseColour ?? $baseLayer;

    $resolvedMidMode = $midLayerMode ?? $midMode ?? $midType ?? 'css';
    $resolvedMidAsset = $midLayerAsset ?? $midImage;
    $resolvedMidModifier = $midLayerModifier ?? $midCss;

    $resolvedTopMode = $topLayerMode ?? $topMode ?? $topType;
    $resolvedTopAsset = $topLayerAsset ?? $topImage;
    $resolvedTopModifier = $topLayerModifier ?? $topCss;

    $resolvedMidClass = $resolvedMidModifier ? 'award-ml-' . $resolvedMidModifier : '';
    $resolvedTopClass = $resolvedTopModifier ? 'award-tl-' . $resolvedTopModifier : '';
    $tooltipText = $resolvedDescription ? $resolvedDescription . ' (' . $resolvedTitle . ')' : $resolvedTitle;
@endphp

<div class="ui-award-badge" 
     x-data="{ imgError: false }"
     x-tooltip="'{{ addslashes($tooltipText) }}'"
     aria-label="{{ $tooltipText }}"
     style="--badge-base: {{ $resolvedBaseColour }};">
  
    @if($resolvedMidMode === 'image' && $resolvedMidAsset)
        <img src="/skins/{{ $skin }}/images/awards/{{ rawurlencode($resolvedMidAsset) }}" 
             class="ui-award-layer ui-award-layer-mid" 
             alt="{{ $resolvedTitle }}">
    @elseif($resolvedMidMode === 'css')
        <div class="ui-award-layer ui-award-layer-mid {{ $resolvedMidClass }}"></div>
    @endif
  
    @if($resolvedTopMode === 'image' && $resolvedTopAsset)
        <img src="/skins/{{ $skin }}/images/awards/{{ rawurlencode($resolvedTopAsset) }}" 
             x-show="!imgError"
             x-on:error="imgError = true" 
             class="ui-award-layer ui-award-layer-top" 
             alt="{{ $resolvedTopAsset }}">
    @elseif($resolvedTopMode === 'css' && $resolvedTopModifier)
        <div class="ui-award-layer ui-award-layer-top {{ $resolvedTopClass }}"></div>
    @endif
</div>