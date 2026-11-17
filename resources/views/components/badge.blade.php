{{-- resources/views/components/badge.blade.php --}}
@props([
    'type' => null,
    'variant' => 'primary',
    'size' => 'md',
    'icon' => null,
    'tooltip' => '',
    'award' => null,
    'rank' => null,
    'rating' => null,
    'languageRatings' => null,
    'sexRatings' => null,
    'violenceRatings' => null,
    'distinctionTitle' => 'Distinction',
    'distinctionDescription' => null,
    'distinctionBaseColour' => '#1a1a1aFF',
    'rankSet' => '',
    'baseLayer' => '#1a1a1aFF',
    'departmentColour' => '#ffffffFF',
    'midLayerMode' => 'css',
    'midLayerAsset' => null,
    'midLayerModifier' => '',
    'midLayerHue' => null,
    'topLayerMode' => 'image',
    'topLayerAsset' => null,
    'topLayerModifier' => '',
    'topLayerHue' => null,
    'skin' => 'default',
    'reactive' => false,
])

@php
    if ($type === null) {
        if ($languageRatings !== null || $sexRatings !== null || $violenceRatings !== null || $rating !== null) {
            $type = 'ai-rating';
        } elseif ($award !== null || $attributes->has('distinctionTitle') || $attributes->has('distinctionBaseColour')) {
            $type = 'distinction';
        } elseif ($rank !== null || $reactive || $rankSet !== '') {
            $type = 'insignia';
        } else {
            $type = 'pill';
        }
    }

    $variantClasses = match(strtolower((string) $variant)) {
        'info'                => 'bg-badge-info text-accent border-info',
        'success', 'active'   => 'bg-badge-success text-success border-success',
        'warning'             => 'bg-badge-warning text-warning border-warning',
        'danger'              => 'bg-badge-danger text-danger-light border-danger',
        'muted', 'completed', 'planned' => 'bg-panel-sub text-muted border-panel',
        default               => 'bg-badge-primary text-accent border-accent',
    };
@endphp

@switch($type)
    @case('distinction')
        @php
            $dTitle = $award->distinction_title ?? $distinctionTitle;
            $dDesc = $award->distinction_description ?? $distinctionDescription;
            $dBase = $award->distinction_base_colour ?? $distinctionBaseColour;
            $dMidMode = $award->mid_layer_mode ?? $midLayerMode;
            $dMidAsset = $award->mid_layer_asset ?? $midLayerAsset;
            $dMidMod = $award->mid_layer_modifier ?? $midLayerModifier;
            $dTopMode = $award->top_layer_mode ?? $topLayerMode;
            $dTopAsset = $award->top_layer_asset ?? $topLayerAsset;
            $dTopMod = $award->top_layer_modifier ?? $topLayerModifier;

            $resolvedMidClass = $dMidMod ? (str_starts_with($dMidMod, 'award-ml-') || str_starts_with($dMidMod, 'distinction-ml-') ? $dMidMod : 'award-ml-' . $dMidMod) : '';
            $resolvedTopClass = $dTopMod ? (str_starts_with($dTopMod, 'award-tl-') || str_starts_with($dTopMod, 'distinction-tl-') ? $dTopMod : 'award-tl-' . $dTopMod) : '';
            $dTooltip = $tooltip ?: ($dDesc ? $dDesc . ' (' . $dTitle . ')' : $dTitle);
        @endphp
        <div @if($dTooltip)
                 x-tooltip="'{{ addslashes($dTooltip) }}'"
                 title="{{ $dTooltip }}"
                 aria-label="{{ $dTooltip }}"
             @endif
             x-data="{ imgError: false }"
             {{ $attributes->merge(['class' => 'ui-award-badge relative inline-block flex items-center justify-center drop-shadow-badge select-none']) }}
             :style="`--badge-base: {{ $dBase }};`">
            @if($slot->isNotEmpty())
                {{ $slot }}
            @else
                @if($dMidMode === 'image' && $dMidAsset)
                    <img src="/skins/{{ $skin }}/images/distinctions/{{ rawurlencode($dMidAsset) }}"
                         class="ui-award-layer ui-award-layer-mid absolute inset-0 w-full h-full object-contain"
                         alt="{{ $dTitle }}">
                @elseif($dMidMode === 'css' && $resolvedMidClass)
                    <div class="ui-award-layer ui-award-layer-mid {{ $resolvedMidClass }}"></div>
                @endif

                @if($dTopMode === 'image' && $dTopAsset)
                    <img src="/skins/{{ $skin }}/images/distinctions/{{ rawurlencode($dTopAsset) }}"
                         x-show="!imgError"
                         x-on:error="imgError = true"
                         class="ui-award-layer ui-award-layer-top absolute inset-0 w-full h-full object-contain"
                         alt="{{ $dTopAsset }}">
                @elseif($dTopMode === 'css' && $resolvedTopClass)
                    <div class="ui-award-layer ui-award-layer-top {{ $resolvedTopClass }}"></div>
                @endif
            @endif
        </div>
        @break

    @case('insignia')
        @php
            $rTitle = $rank->insignia_title ?? lexicon('tooltip_common_CharacterRank', 'Character Rank');
            $rMidMode = $rank->mid_layer_mode ?? $midLayerMode;
            $rMidAsset = $rank->mid_layer_asset ?? $midLayerAsset;
            $rMidMod = $rank->mid_layer_modifier ?? $midLayerModifier;
            $rMidHue = $rank->mid_layer_hue ?? $midLayerHue;
            $rTopMode = $rank->top_layer_mode ?? $topLayerMode;
            $rTopAsset = $rank->top_layer_asset ?? $topLayerAsset;
            $rTopMod = $rank->top_layer_modifier ?? $topLayerModifier;
            $rTopHue = $rank->top_layer_hue ?? $topLayerHue;
            $badgeTooltip = $tooltip ?: $rTitle;
        @endphp
        <div @if(!$reactive && $badgeTooltip)
                 x-tooltip="'{{ addslashes($badgeTooltip) }}'"
                 title="{{ $badgeTooltip }}"
                 aria-label="{{ $badgeTooltip }}"
             @endif
             x-data="{ midImgError: false, topImgError: false }"
             {{ $attributes->merge(['class' => 'ui-rank-badge relative inline-block flex items-center justify-center drop-shadow-badge select-none overflow-hidden border-rounded shadow-panel w-48 h-12 scale-75 origin-left']) }}
             @if($reactive)
                 :style="`--badge-dept: ${modals.insignia.set_color || defaultDeptColor}; --badge-base: ${baseLayer}; --badge-mid-color: ${modals.insignia.mid_layer_hue || baseLayer}; --badge-top-color: ${modals.insignia.top_layer_hue || baseLayer};`"
             @else
                 :style="`--badge-dept: {{ $departmentColour }}; --badge-base: {{ $baseLayer }}; --badge-mid-color: {{ $rMidHue ?: $baseLayer }}; --badge-top-color: {{ $rTopHue ?: $baseLayer }};`"
             @endif>
            @if($slot->isNotEmpty())
                {{ $slot }}
            @else
                <div class="layer-texture absolute inset-0 w-full h-full z-0 block"></div>
                <div class="layer-fade absolute inset-0 w-full h-full z-0 block"></div>

                @if($reactive)
                    <div class="layer-shaped absolute inset-0 w-full h-full z-10 block"
                         x-show="modals.insignia.mid_layer_mode === 'css' && modals.insignia.mid_layer_modifier"
                         :class="modals.insignia.mid_layer_modifier"></div>

                    <img x-show="midImageUrl !== '' && !imageError.mid"
                         :src="midImageUrl"
                         x-on:load="imageError.mid = false"
                         x-on:error="if (midImageUrl) imageError.mid = true"
                         class="absolute inset-0 w-full h-full z-10 block object-cover"
                         alt="{{ lexicon('label_ranks_MidLayerAlt', 'Middle Rank Layer') }}">

                    <div class="layer-top-css absolute inset-0 w-full h-full z-20 block"
                         x-show="modals.insignia.top_layer_mode === 'css' && modals.insignia.top_layer_modifier"
                         :class="modals.insignia.top_layer_modifier"></div>

                    <img x-show="topImageUrl !== '' && !imageError.top"
                         :src="topImageUrl"
                         x-on:load="imageError.top = false"
                         x-on:error="if (topImageUrl) imageError.top = true"
                         class="absolute inset-0 w-full h-full z-20 block object-contain object-right pr-2 drop-shadow-badge"
                         alt="{{ lexicon('label_ranks_TopLayerAlt', 'Top Rank Layer') }}">
                @else
                    @if($rMidMode === 'image' && $rMidAsset)
                        <img src="/images/ranks/{{ rawurlencode($rankSet) }}/{{ rawurlencode($rMidAsset) }}"
                             x-show="!midImgError"
                             x-on:error="midImgError = true"
                             class="absolute inset-0 w-full h-full z-10 block object-cover"
                             alt="{{ $badgeTooltip }}">
                    @elseif($rMidMode === 'css' && !empty($rMidMod))
                        <div class="layer-shaped absolute inset-0 w-full h-full z-10 block {{ $rMidMod }}"></div>
                    @endif

                    @if($rTopMode === 'image' && $rTopAsset)
                        <img src="/images/ranks/{{ rawurlencode($rankSet) }}/{{ rawurlencode($rTopAsset) }}"
                             x-show="!topImgError"
                             x-on:error="topImgError = true"
                             class="absolute inset-0 w-full h-full z-20 block object-contain object-right pr-2 drop-shadow-badge"
                             alt="{{ $rTopAsset }}">
                    @elseif($rTopMode === 'css' && !empty($rTopMod))
                        <div class="layer-top-css absolute inset-0 w-full h-full z-20 block {{ $rTopMod }}"></div>
                    @endif
                @endif
            @endif
        </div>
        @break

    @case('ai-rating')
        @if($languageRatings !== null || $sexRatings !== null || $violenceRatings !== null)
            <x-tab type="accordion" :title="lexicon('label_WriteChapter_ToggleRatings', 'Toggle Content Ratings (L-S-V)')" :open="false">
                <div class="grid grid-cols-1 lg-grid-cols-3 gap-lg">
                    <x-metadata type="select"
                                id="content_tier_l"
                                name="content_tier_l"
                                :label="lexicon('label_WriteChapter_LanguageRating', 'Language (L) Rating')"
                                :tooltip="lexicon('tooltip_WriteChapter_LanguageRating', 'Select the language maturity rating for this folio')"
                                required>
                        <option value="">{{ lexicon('label_WriteChapter_OptSelectRating', '-- Select Rating --') }}</option>
                        @foreach($languageRatings ?? [] as $tier)
                            <option value="{{ $tier->tier_level }}">{{ $tier->tier_label }}</option>
                        @endforeach
                    </x-metadata>

                    <x-metadata type="select"
                                id="content_tier_s"
                                name="content_tier_s"
                                :label="lexicon('label_WriteChapter_SexRating', 'Sex (S) Rating')"
                                :tooltip="lexicon('tooltip_WriteChapter_SexRating', 'Select the sexual content maturity rating for this folio')"
                                required>
                        <option value="">{{ lexicon('label_WriteChapter_OptSelectRating', '-- Select Rating --') }}</option>
                        @foreach($sexRatings ?? [] as $tier)
                            <option value="{{ $tier->tier_level }}">{{ $tier->tier_label }}</option>
                        @endforeach
                    </x-metadata>

                    <x-metadata type="select"
                                id="content_tier_v"
                                name="content_tier_v"
                                :label="lexicon('label_WriteChapter_ViolenceRating', 'Violence (V) Rating')"
                                :tooltip="lexicon('tooltip_WriteChapter_ViolenceRating', 'Select the violence maturity rating for this folio')"
                                required>
                        <option value="">{{ lexicon('label_WriteChapter_OptSelectRating', '-- Select Rating --') }}</option>
                        @foreach($violenceRatings ?? [] as $tier)
                            <option value="{{ $tier->tier_level }}">{{ $tier->tier_label }}</option>
                        @endforeach
                    </x-metadata>
                </div>
            </x-tab>
        @else
            @php
                $activeRating = $rating ?? (function_exists('ai_rating') ? ai_rating() : 'R0-S0-V0');
            @endphp
            <span @if($tooltip)
                      x-tooltip="'{{ addslashes($tooltip) }}'"
                      title="{{ $tooltip }}"
                      aria-label="{{ $tooltip }}"
                  @endif
                  {{ $attributes->merge(['class' => 'inline-block p-badge border-rounded-pill text-xs font-bold uppercase text-wider whitespace-nowrap leading-none bg-badge-info text-accent border-info']) }}>
                @if($icon)
                    <i class="bi {{ $icon }}"></i>
                @endif
                <span>{{ $slot->isNotEmpty() ? $slot : $activeRating }}</span>
            </span>
        @endif
        @break

    @case('pill')
    @default
        <span @if($tooltip)
                  x-tooltip="'{{ addslashes($tooltip) }}'"
                  title="{{ $tooltip }}"
                  aria-label="{{ $tooltip }}"
              @endif
              {{ $attributes->merge(['class' => "inline-block p-badge border-rounded-pill text-xs font-bold uppercase text-wider whitespace-nowrap leading-none {$variantClasses}"]) }}>
            @if($icon)
                <i class="bi {{ $icon }}"></i>
            @endif
            {{ $slot }}
        </span>
@endswitch