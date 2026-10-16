{{-- 
@WidgetManifest({
    "name": "Image Gallery Carousel",
    "description": "Displays multiple images in a carousel with navigation arrows and GLightbox zoom support.",
    "icon": "bi-images",
    "fields": {
        "title": { "type": "text", "label": "Widget Title", "default": "Image Gallery" },
        "images": { "type": "textarea", "label": "Image URLs (One per line or comma-separated, with optional captions using | e.g. URL|Caption)", "default": "" }
    }
})
--}}
@php
    $options = is_string($widget->pivot->instance_options) ? json_decode($widget->pivot->instance_options, true) : ($widget->pivot->instance_options ?? []);
    $rawTitle = trim($options['title'] ?? '');
    $title =$rawTitle !== '' ? $rawTitle : lexicon('label_ImageGallery_DefaultTitle', 'Image Gallery');$rawImages = trim($options['images'] ?? '');$slides = [];

    if ($rawImages !== '') {
        $lines = preg_split('/\r\n\vert{}\r\vert{}\n\vert{},/',$rawImages);
        foreach ($lines as$line) {
            $line = trim($line);
            if ($line === '') continue;
            
            if (str_contains($line, '|')) {
                [$imgUrl,$imgCaption] = explode('|', $line, 2);$slides[] = ['url' => trim($imgUrl), 'caption' => trim($imgCaption)];
            } else {
                $slides[] = ['url' =>$line, 'caption' => ''];
            }
        }
    }
    
    $galleryId = 'gallery_' . ($widget->id ?? uniqid());
    $totalSlides = count($slides);
@endphp

<div class="bg-panel border border-panel rounded-panel shadow-panel flex flex-col overflow-hidden w-full mb-6 widget-image-gallery" 
     x-data="{ 
        currentIndex: 0, 
        totalSlides: {{ $totalSlides }},
        next() {
            if (this.totalSlides > 1) {
                this.currentIndex = (this.currentIndex + 1) % this.totalSlides;
            }
        },
        prev() {
            if (this.totalSlides > 1) {
                this.currentIndex = (this.currentIndex - 1 + this.totalSlides) % this.totalSlides;
            }
        }
     }">
     
    @if($title)
        <div class="bg-panel-header text-h border-b border-panel p-panel-header font-bold flex flex-row flex-wrap justify-between items-center gap-md">
            <span class="text-xl font-bold text-accent">{{ $title }}</span>
            <div class="flex flex-row items-center gap-sm">
                @if($totalSlides > 1)
                    <span class="text-xs text-muted" x-text="`${currentIndex + 1} / {{ $totalSlides }}`"></span>
                @endif
                <i class="bi bi-images text-accent"
                   x-tooltip="'{{ addslashes(lexicon('tooltip_ImageGallery_Header', 'Image gallery carousel')) }}'"
                   aria-label="{{ lexicon('tooltip_ImageGallery_Header', 'Image gallery carousel') }}"></i>
            </div>
        </div>
    @endif

    <div class="p-panel flex flex-col items-center justify-center gap-md">
        @if($totalSlides > 0)
            <div class="relative w-full group">
                @foreach($slides as $index =>$slide)
                    <div x-show="currentIndex === {{ $index }}" @if($index > 0) x-cloak @endif class="w-full flex flex-col gap-sm">
                        <x-image-card :url="$slide['url']" :alt="$slide['caption']" :gallery="$galleryId" />
                        @if($slide['caption'])
                            <span class="block text-sm text-muted text-center italic">{{ $slide['caption'] }}</span>
                        @endif
                    </div>
                @endforeach

                @if($totalSlides > 1)
                    <div class="absolute inset-y-1/3 inset-x-2 flex items-center justify-between pointer-events-none">
                        <button @click.prevent="prev()" class="w-9 h-9 rounded-full bg-panel/80 text-h border border-panel flex items-center justify-center shadow-panel pointer-events-auto hover:bg-accent hover:text-white transition-colors" aria-label="Previous Slide">
                            <i class="bi bi-chevron-left"></i>
                        </button>
                        <button @click.prevent="next()" class="w-9 h-9 rounded-full bg-panel/80 text-h border border-panel flex items-center justify-center shadow-panel pointer-events-auto hover:bg-accent hover:text-white transition-colors" aria-label="Next Slide">
                            <i class="bi bi-chevron-right"></i>
                        </button>
                    </div>
                @endif
            </div>
        @else
            <div class="bg-panel-sub border-dashed-panel rounded-panel p-panel text-center w-full">
                <span class="text-sm italic">{{ lexicon('msg_common_ImageNotConfigured', 'No image URL has been configured.') }}</span>
            </div>
        @endif
    </div>
</div>