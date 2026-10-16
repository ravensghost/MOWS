@props([
    'rankSet' => '',
    'baseLayer' => '#1a1a1aFF',
    'departmentColor' => '#ffffffFF',
    'midType' => 'css',
    'midImage' => null,
    'midCss' => '',
    'midColor' => null,
    'topType' => 'image',
    'topImage' => null,
    'topCss' => '',
    'topColor' => null,
    'tooltip' => null,
    'reactive' => false,
])

@php
    $badgeTooltip =$tooltip ?: lexicon('tooltip_common_CharacterRank', 'Character Rank');
@endphp

<div class="ui-rank-badge relative inline-block overflow-hidden rounded-input shadow-panel w-48 h-12 {{ $reactive ? 'scale-75 origin-center' : 'scale-75 origin-left' }}" 
     x-data="{ midImgError: false, topImgError: false }"
     @if(!$reactive)
         x-tooltip="'{{ addslashes($badgeTooltip) }}'"
         aria-label="{{ $badgeTooltip }}"
     @endif
     style="--badge-dept: {{ $reactive ? '${rankForm.set_color || defaultDeptColor}' : $departmentColor }}; --badge-base: {{$baseLayer }}; --badge-mid-color: {{ $reactive ? '${rankForm.mid_color || baseLayer}' : ($midColor ?: $baseLayer) }}; --badge-top-color: {{ $reactive ? '${rankForm.top_color || baseLayer}' : ($topColor ?: $baseLayer) }};"
     @if($reactive)
         :style="`--badge-dept: ${rankForm.set_color || defaultDeptColor}; --badge-base: ${baseLayer}; --badge-mid-color: ${rankForm.mid_color || baseLayer}; --badge-top-color: ${rankForm.top_color || baseLayer};`"
     @endif>
  
    <div class="layer-texture absolute top-0 left-0 w-full h-full z-0 block"></div>
    <div class="layer-fade absolute top-0 left-0 w-full h-full z-0 block"></div>
  
    @if($reactive)
        <!-- Reactive Alpine Mode (Live Preview) -->
        <div class="layer-shaped absolute top-0 left-0 w-full h-full z-10 block" 
             x-show="rankForm.mid_type === 'css' && rankForm.mid_css" 
             :class="rankForm.mid_css"></div>
        
        <img x-show="midImageUrl !== '' && !imageError.mid" 
             :src="midImageUrl" 
             x-on:load="imageError.mid = false"
             x-on:error="if (midImageUrl) imageError.mid = true"
             class="absolute top-0 left-0 w-full h-full z-10 block object-cover" 
             alt="{{ lexicon('label_ranks_MidLayerAlt', 'Middle Rank Layer') }}">
        
        <div class="layer-top-css absolute top-0 left-0 w-full h-full z-20 block" 
             x-show="rankForm.top_type === 'css' && rankForm.top_css" 
             :class="rankForm.top_css"></div>
        
        <img x-show="topImageUrl !== '' && !imageError.top" 
             :src="topImageUrl" 
             x-on:load="imageError.top = false"
             x-on:error="if (topImageUrl) imageError.top = true"
             class="absolute top-0 left-0 w-full h-full z-20 block object-contain object-right pr-2 drop-shadow-badge" 
             alt="{{ lexicon('label_ranks_TopLayerAlt', 'Top Rank Layer') }}">
    @else
    <!-- Static Mode (Index Roster Cards) -->
    @if($midType === 'image' && $midImage)
        <img src="/images/ranks/{{ rawurlencode($rankSet) }}/{{ rawurlencode($midImage) }}" 
            x-show="!midImgError"
            x-on:error="midImgError = true"
            class="absolute top-0 left-0 w-full h-full z-10 block object-cover" 
            alt="{{ $badgeTooltip }}">
        @elseif($midType === 'css' && !empty($midCss))
            <div class="layer-shaped absolute top-0 left-0 w-full h-full z-10 block {{ $midCss }}"></div>
        @endif
           
        @if($topType === 'image' && $topImage)
            <img src="/images/ranks/{{ rawurlencode($rankSet) }}/{{ rawurlencode($topImage) }}" 
            x-show="!topImgError"
            x-on:error="topImgError = true" 
            class="absolute top-0 left-0 w-full h-full z-20 block object-contain object-right pr-2 drop-shadow-badge"
            alt="{{ $topImage }}">
        @elseif($topType === 'css' && !empty($topCss))
            <div class="layer-top-css absolute top-0 left-0 w-full h-full z-20 block {{ $topCss }}"></div>
        @endif
    @endif
</div>