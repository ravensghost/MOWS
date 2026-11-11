@props([
    'rankSet' => '',
    'baseLayer' => '#1a1a1aFF',
    'departmentColor' => '#ffffffFF',
    'midLayerMode' => 'css',
    'midLayerAsset' => null,
    'midLayerModifier' => '',
    'midLayerHue' => null,
    'topLayerMode' => 'image',
    'topLayerAsset' => null,
    'topLayerModifier' => '',
    'topLayerHue' => null,
    'tooltip' => null,
    'reactive' => false,
])

@php
    $badgeTooltip = $tooltip ?: lexicon('tooltip_common_CharacterRank', 'Character Rank');
@endphp

<div class="ui-rank-badge relative inline-block overflow-hidden rounded-input shadow-panel w-48 h-12 {{ $reactive ? 'scale-75 origin-center' : 'scale-75 origin-left' }}" 
     x-data="{ midImgError: false, topImgError: false }"
     @if(!$reactive)
         x-tooltip="'{{ addslashes($badgeTooltip) }}'"
         aria-label="{{ $badgeTooltip }}"
     @endif
     style="--badge-dept: {{ $reactive ? '${rankForm.set_color || defaultDeptColor}' : $departmentColor }}; --badge-base: {{ $baseLayer }}; --badge-mid-color: {{ $reactive ? '${rankForm.mid_layer_hue || baseLayer}' : ($midLayerHue ?: $baseLayer) }}; --badge-top-color: {{ $reactive ? '${rankForm.top_layer_hue || baseLayer}' : ($topLayerHue ?: $baseLayer) }};"
     @if($reactive)
         :style="`--badge-dept: ${rankForm.set_color || defaultDeptColor}; --badge-base: ${baseLayer}; --badge-mid-color: ${rankForm.mid_layer_hue || baseLayer}; --badge-top-color: ${rankForm.top_layer_hue || baseLayer};`"
     @endif>
  
    <div class="layer-texture absolute top-0 left-0 w-full h-full z-0 block"></div>
    <div class="layer-fade absolute top-0 left-0 w-full h-full z-0 block"></div>
  
    @if($reactive)
        <!-- Reactive Alpine Mode (Live Preview) -->
        <div class="layer-shaped absolute top-0 left-0 w-full h-full z-10 block" 
             x-show="rankForm.mid_layer_mode === 'css' && rankForm.mid_layer_modifier" 
             :class="rankForm.mid_layer_modifier"></div>
        
        <img x-show="midImageUrl !== '' && !imageError.mid" 
             :src="midImageUrl" 
             x-on:load="imageError.mid = false"
             x-on:error="if (midImageUrl) imageError.mid = true"
             class="absolute top-0 left-0 w-full h-full z-10 block object-cover" 
             alt="{{ lexicon('label_ranks_MidLayerAlt', 'Middle Rank Layer') }}">
        
        <div class="layer-top-css absolute top-0 left-0 w-full h-full z-20 block" 
             x-show="rankForm.top_layer_mode === 'css' && rankForm.top_layer_modifier" 
             :class="rankForm.top_layer_modifier"></div>
        
        <img x-show="topImageUrl !== '' && !imageError.top" 
             :src="topImageUrl" 
             x-on:load="imageError.top = false"
             x-on:error="if (topImageUrl) imageError.top = true"
             class="absolute top-0 left-0 w-full h-full z-20 block object-contain object-right pr-2 drop-shadow-badge" 
             alt="{{ lexicon('label_ranks_TopLayerAlt', 'Top Rank Layer') }}">
    @else
        <!-- Static Mode (Index Roster Cards) -->
        @if($midLayerMode === 'image' && $midLayerAsset)
            <img src="/images/ranks/{{ rawurlencode($rankSet) }}/{{ rawurlencode($midLayerAsset) }}" 
                 x-show="!midImgError"
                 x-on:error="midImgError = true"
                 class="absolute top-0 left-0 w-full h-full z-10 block object-cover" 
                 alt="{{ $badgeTooltip }}">
        @elseif($midLayerMode === 'css' && !empty($midLayerModifier))
            <div class="layer-shaped absolute top-0 left-0 w-full h-full z-10 block {{ $midLayerModifier }}"></div>
        @endif
           
        @if($topLayerMode === 'image' && $topLayerAsset)
            <img src="/images/ranks/{{ rawurlencode($rankSet) }}/{{ rawurlencode($topLayerAsset) }}" 
                 x-show="!topImgError"
                 x-on:error="topImgError = true" 
                 class="absolute top-0 left-0 w-full h-full z-20 block object-contain object-right pr-2 drop-shadow-badge"
                 alt="{{ $topLayerAsset }}">
        @elseif($topLayerMode === 'css' && !empty($topLayerModifier))
            <div class="layer-top-css absolute top-0 left-0 w-full h-full z-20 block {{ $topLayerModifier }}"></div>
        @endif
    @endif
</div>