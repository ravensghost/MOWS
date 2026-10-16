@props([
    'awardName' => 'Award',
    'description' => null,
    'baseLayer' => '#1a1a1a',
    'midType' => 'css',
    'midImage' => null,
    'midCss' => null,
    'topType' => null,
    'topImage' => null,
    'topCss' => null,
    'skin' => 'default'
])

@php
    $resolvedMidClass = $midCss ? 'award-ml-' . $midCss : '';
    $resolvedTopClass = $topCss ? 'award-tl-' . $topCss : '';
    $tooltipText = $description ? $description . ' (' . $awardName . ')' : $awardName;
@endphp

<div class="ui-award-badge relative inline-block overflow-hidden rounded shadow-panel w-48 h-12 scale-75 origin-left" 
     x-data="{ imgError: false }"
     x-tooltip="'{{ addslashes($tooltipText) }}'"
     aria-label="{{ $tooltipText }}"
     style="--badge-base: {{ $baseLayer }};">
  
    @if($midType === 'image' && $midImage)
        <img src="/skins/{{ $skin }}/images/awards/{{ rawurlencode($midImage) }}" 
             class="absolute top-0 left-0 w-full h-full z-10 block object-cover" 
             alt="{{ $awardName }}">
    @elseif($midType === 'css')
        <div class="absolute top-0 left-0 w-full h-full z-10 block {{ $resolvedMidClass }}"></div>
    @endif
  
    @if($topType === 'image' && $topImage)
        <img src="/skins/{{ $skin }}/images/awards/{{ rawurlencode($topImage) }}" 
             x-show="!imgError"
             x-on:error="imgError = true" 
             class="absolute top-0 left-0 w-full h-full z-20 block object-contain object-right pr-2 drop-shadow-badge" 
             alt="{{ $topImage }}">
    @elseif($topType === 'css' && $topCss)
        <div class="absolute top-0 left-0 w-full h-full z-20 block {{ $resolvedTopClass }}"></div>
    @endif
</div>