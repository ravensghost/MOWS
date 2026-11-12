@props([
    'midShapes' => [],
    'topShapes' => [],
])

<div class="grid grid-cols-1 md-grid-cols-2 gap-md w-full">
    <!-- Middle Layer Configuration -->
    <div class="bg-panel-sub border border-panel rounded-panel p-4 flex flex-col gap-sm">
        <span class="text-sm font-bold text-h border-b border-panel pb-2">
            {{ lexicon('label_ranks_MidLayerConfig', 'Middle Layer (Background / Trim)') }}
        </span>

        <div class="flex flex-col gap-xs">
            <label class="text-xs font-bold text-input-muted">
                {{ lexicon('label_ranks_MidLayerMode', 'Layer Mode') }}
            </label>
            <select name="mid_layer_mode" 
                    x-model="modals.insignia.mid_layer_mode" 
                    class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent">
                <option value="css">{{ lexicon('label_ranks_ModeCss', 'CSS Shape / Modifier') }}</option>
                <option value="image">{{ lexicon('label_ranks_ModeImage', 'Image Asset') }}</option>
                <option value="none">{{ lexicon('label_common_None', 'None') }}</option>
            </select>
        </div>

        <div x-show="modals.insignia.mid_layer_mode === 'css'" class="flex flex-col gap-sm">
            <div class="flex flex-col gap-xs">
                <label class="text-xs font-bold text-input-muted">
                    {{ lexicon('label_ranks_MidLayerShape', 'CSS Shape Modifier') }}
                </label>
                <select name="mid_layer_modifier" 
                        x-model="modals.insignia.mid_layer_modifier" 
                        class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent">
                    <option value="">{{ lexicon('label_common_SelectOption', 'Select Shape...') }}</option>
                    @foreach($midShapes as $shapeKey => $shapeLabel)
                        <option value="{{ $shapeKey }}">{{ $shapeLabel }}</option>
                    @endforeach
                </select>
            </div>

            <x-form-input 
                name="mid_layer_hue" 
                :label="lexicon('label_ranks_MidLayerHue', 'Layer Hue (8-Digit Hex)')" 
                xModel="modals.insignia.mid_layer_hue"
                placeholder="#1a1a1aFF"
            />
        </div>

        <div x-show="modals.insignia.mid_layer_mode === 'image'" class="flex flex-col gap-xs">
            <label class="text-xs font-bold text-input-muted">
                {{ lexicon('label_ranks_MidLayerAsset', 'Middle Layer Image') }}
            </label>
            <select name="mid_layer_asset" 
                    x-model="modals.insignia.mid_layer_asset" 
                    class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent">
                <option value="">{{ lexicon('label_common_SelectImage', 'Select Image...') }}</option>
                <template x-for="img in modals.insignia.available_images" :key="'mid_' + img">
                    <option :value="img" x-text="img"></option>
                </template>
            </select>
        </div>
    </div>

    <!-- Top Layer Configuration -->
    <div class="bg-panel-sub border border-panel rounded-panel p-4 flex flex-col gap-sm">
        <span class="text-sm font-bold text-h border-b border-panel pb-2">
            {{ lexicon('label_ranks_TopLayerConfig', 'Top Layer (Pips / Bars / Insignia)') }}
        </span>

        <div class="flex flex-col gap-xs">
            <label class="text-xs font-bold text-input-muted">
                {{ lexicon('label_ranks_TopLayerMode', 'Layer Mode') }}
            </label>
            <select name="top_layer_mode" 
                    x-model="modals.insignia.top_layer_mode" 
                    class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent">
                <option value="image">{{ lexicon('label_ranks_ModeImage', 'Image Asset') }}</option>
                <option value="css">{{ lexicon('label_ranks_ModeCss', 'CSS Shape / Modifier') }}</option>
                <option value="none">{{ lexicon('label_common_None', 'None') }}</option>
            </select>
        </div>

        <div x-show="modals.insignia.top_layer_mode === 'image'" class="flex flex-col gap-xs">
            <label class="text-xs font-bold text-input-muted">
                {{ lexicon('label_ranks_TopLayerAsset', 'Top Layer Image') }}
            </label>
            <select name="top_layer_asset" 
                    x-model="modals.insignia.top_layer_asset" 
                    class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent">
                <option value="">{{ lexicon('label_common_SelectImage', 'Select Image...') }}</option>
                <template x-for="img in modals.insignia.available_images" :key="'top_' + img">
                    <option :value="img" x-text="img"></option>
                </template>
            </select>
        </div>

        <div x-show="modals.insignia.top_layer_mode === 'css'" class="flex flex-col gap-sm">
            <div class="flex flex-col gap-xs">
                <label class="text-xs font-bold text-input-muted">
                    {{ lexicon('label_ranks_TopLayerShape', 'CSS Shape Modifier') }}
                </label>
                <select name="top_layer_modifier" 
                        x-model="modals.insignia.top_layer_modifier" 
                        class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent">
                    <option value="">{{ lexicon('label_common_SelectOption', 'Select Shape...') }}</option>
                    @foreach($topShapes as $shapeKey => $shapeLabel)
                        <option value="{{ $shapeKey }}">{{ $shapeLabel }}</option>
                    @endforeach
                </select>
            </div>

            <x-form-input 
                name="top_layer_hue" 
                :label="lexicon('label_ranks_TopLayerHue', 'Layer Hue (8-Digit Hex)')" 
                xModel="modals.insignia.top_layer_hue"
                placeholder="#ffffffFF"
            />
        </div>
    </div>
</div>