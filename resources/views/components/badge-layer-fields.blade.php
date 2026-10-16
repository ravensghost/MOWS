@props([
    'midShapes',
    'topShapes',
])

<div class="grid grid-cols-1 md-grid-cols-2 gap-md bg-panel-sub border border-panel rounded-panel p-4">
    <!-- Middle Layer -->
    <div class="flex flex-col gap-sm">
        <div class="flex flex-col gap-xs">
            <label for="badge_mid_type" class="text-xs font-bold text-input-muted">
                <span>{{ lexicon('label_ranks_MidType', 'Middle Layer Type') }}</span>
            </label>
            <select id="badge_mid_type"
                    x-model="rankForm.mid_type" 
                    name="mid_type" 
                    class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent"
                    x-tooltip="'{{ addslashes(lexicon('tooltip_ranks_MidType', 'Choose between a CSS shape or an image asset for the middle layer')) }}'">
                <option value="css">{{ lexicon('label_ranks_CssRendered', 'CSS Rendered') }}</option>
                <option value="image">{{ lexicon('label_ranks_ImageFile', 'Image File') }}</option>
            </select>
        </div>
        
        <div x-show="rankForm.mid_type === 'image'" class="flex flex-col gap-xs">
            <label for="badge_mid_image" class="text-xs font-bold text-input-muted">
                <span>{{ lexicon('label_ranks_MidImage', 'Middle Layer Image') }}</span>
            </label>
            <select id="badge_mid_image"
                    x-model="rankForm.mid_image" 
                    @change="imageError.mid = false"
                    name="mid_image" 
                    class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent"
                    x-tooltip="'{{ addslashes(lexicon('tooltip_ranks_SelectMidImage', 'Select an image file from the rank set folder')) }}'">
                <option value="" x-text="!rankForm.set_name ? '{{ addslashes(lexicon('msg_ranks_SelectSetFirst', 'Select Set First...')) }}' : (rankForm.loading_images ? '{{ addslashes(lexicon('msg_ranks_LoadingImages', 'Loading Images...')) }}' : '{{ addslashes(lexicon('msg_ranks_SelectImage', 'Select Image...')) }}')"></option>
                <template x-for="img in rankForm.available_images" :key="'mid-' + img">
                    <option :value="img" :selected="img === rankForm.mid_image" x-text="img"></option>
                </template>
            </select>
        </div>

        <div x-show="rankForm.mid_type === 'css'" class="flex flex-col gap-sm">
            <div class="flex flex-col gap-xs">
                <label for="badge_mid_css" class="text-xs font-bold text-input-muted">
                    <span>{{ lexicon('label_ranks_MidShape', 'Middle CSS Shape') }}</span>
                </label>
                <select id="badge_mid_css"
                        x-model="rankForm.mid_css" 
                        name="mid_css" 
                        class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent"
                        x-tooltip="'{{ addslashes(lexicon('tooltip_ranks_SelectMidShape', 'Select a predefined CSS polygon shape')) }}'">
                    <option value="">{{ lexicon('msg_ranks_SelectShape', 'Select a Shape...') }}</option>
                    @foreach($midShapes as $class => $label)
                        <option value="{{ $class }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <x-colour-picker 
                name="mid_color" 
                :label="lexicon('label_ranks_CustomMidColour', 'Custom Layer Colour')" 
                xModel="rankForm.mid_color" 
            />
        </div>
    </div>
    <!-- Unified Live Preview Panel Component -->
    <x-rank-preview-panel />
    <!-- Top Layer -->
    <div class="flex flex-col gap-sm">
        <div class="flex flex-col gap-xs">
            <label for="badge_top_type" class="text-xs font-bold text-input-muted">
                <span>{{ lexicon('label_ranks_TopType', 'Top Layer Type') }}</span>
            </label>
            <select id="badge_top_type"
                    x-model="rankForm.top_type" 
                    name="top_type" 
                    class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent"
                    x-tooltip="'{{ addslashes(lexicon('tooltip_ranks_TopType', 'Choose between an image asset (pips/insignia) or a CSS shape')) }}'">
                <option value="image">{{ lexicon('label_ranks_ImageFile', 'Image File') }}</option>
                <option value="css">{{ lexicon('label_ranks_CssRendered', 'CSS Rendered') }}</option>
            </select>
        </div>
        
        <div x-show="rankForm.top_type === 'image'" class="flex flex-col gap-xs">
            <label for="badge_top_image" class="text-xs font-bold text-input-muted">
                <span>{{ lexicon('label_ranks_TopImage', 'Top Layer Image') }}</span>
            </label>
            <select id="badge_top_image"
                    x-model="rankForm.top_image" 
                    @change="imageError.top = false"
                    name="top_image" 
                    class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent"
                    x-tooltip="'{{ addslashes(lexicon('tooltip_ranks_SelectTopImage', 'Select a rank pip or insignia image file')) }}'">
                <option value="" x-text="!rankForm.set_name ? '{{ addslashes(lexicon('msg_ranks_SelectSetFirst', 'Select Set First...')) }}' : (rankForm.loading_images ? '{{ addslashes(lexicon('msg_ranks_LoadingImages', 'Loading Images...')) }}' : '{{ addslashes(lexicon('msg_ranks_SelectImage', 'Select Image...')) }}')"></option>
                <template x-for="img in rankForm.available_images" :key="'top-' + img">
                    <option :value="img" :selected="img === rankForm.top_image" x-text="img"></option>
                </template>
            </select>
        </div>

        <div x-show="rankForm.top_type === 'css'" class="flex flex-col gap-sm">
            <div class="flex flex-col gap-xs">
                <label for="badge_top_css" class="text-xs font-bold text-input-muted">
                    <span>{{ lexicon('label_ranks_TopShape', 'Top CSS Shape') }}</span>
                </label>
                <select id="badge_top_css"
                        x-model="rankForm.top_css" 
                        name="top_css" 
                        class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent"
                        x-tooltip="'{{ addslashes(lexicon('tooltip_ranks_SelectTopShape', 'Select a predefined top CSS shape')) }}'">
                    <option value="">{{ lexicon('msg_ranks_SelectShape', 'Select a Shape...') }}</option>
                    @foreach($topShapes as $class => $label)
                        <option value="{{ $class }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <x-colour-picker 
                name="top_color" 
                :label="lexicon('label_ranks_CustomTopColour', 'Custom Layer Colour')" 
                xModel="rankForm.top_color" 
            />
        </div>
    </div>
    
</div>