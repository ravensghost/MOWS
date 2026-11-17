{{-- resources/views/modules/image_gallery.blade.php --}}
{{-- @WidgetManifest({ "name": "Image Gallery Carousel", "description": "Displays multiple images in a carousel with navigation arrows and GLightbox zoom support.", "icon": "bi-images", "fields": { "title": { "type": "text", "label": "Module Title", "default": "Image Gallery" }, "images": { "type": "textarea", "label": "Image URLs (One per line or comma-separated, with optional captions using | e.g. URL|Caption)", "default": "" } } }) --}}
@php
    $rawOptions = $moduleSettings
        ?? $module?->pivot?->book_config
        ?? $module?->pivot?->instance_options
        ?? $widget?->pivot?->instance_options
        ?? [];
    $options = is_string($rawOptions) ? (json_decode($rawOptions, true) ?: []) : (is_array($rawOptions) ? $rawOptions : []);

    $rawTitle = trim((string) ($options['title'] ?? ''));
    $title = $rawTitle !== '' ? $rawTitle : lexicon('label_ImageGallery_DefaultTitle', 'Image Gallery');
    $rawImages = trim((string) ($options['images'] ?? ''));
    $slides = [];

    if ($rawImages !== '') {
        $lines = preg_split('/\r\n|\r|\n|,/', $rawImages) ?: [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            if (str_contains($line, '|')) {
                [$imgUrl, $imgCaption] = explode('|', $line, 2);
                $slides[] = [
                    'url'     => trim($imgUrl),
                    'caption' => trim($imgCaption),
                ];
            } else {
                $slides[] = [
                    'url'     => $line,
                    'caption' => '',
                ];
            }
        }
    }

    $galleryId = 'gallery_' . ($module->pivot->id ?? $module->id ?? $widget->id ?? uniqid());
    $totalSlides = count($slides);
@endphp

<div x-data="{
         currentIndex: 0,
         totalSlides: {{ $totalSlides }},
         prev() {
             if (this.totalSlides > 1) {
                 this.currentIndex = (this.currentIndex - 1 + this.totalSlides) % this.totalSlides;
             }
         },
         next() {
             if (this.totalSlides > 1) {
                 this.currentIndex = (this.currentIndex + 1) % this.totalSlides;
             }
         }
     }"
     x-init="if (window.Lightbox && typeof window.Lightbox.init === 'function') { $nextTick(() => window.Lightbox.init()); }"
     class="w-full">
    <x-tab type="accordion"
           :title="$title"
           icon="bi-images"
           :open="true">
        @if($totalSlides > 1)
            <x-slot:top>
                <span class="text-xs font-mono text-muted"
                      x-text="`${currentIndex + 1} / ${totalSlides}`"></span>
            </x-slot:top>
        @endif

        @if($totalSlides > 0)
            <div class="flex flex-col items-center justify-center gap-md w-full">
                @foreach($slides as $index => $slide)
                    <div x-show="currentIndex === {{ $index }}"
                         @if($index > 0) x-cloak @endif
                         class="w-full flex flex-col items-center gap-sm">
                        <x-preview type="image"
                                   :url="$slide['url']"
                                   :alt="$slide['caption'] ?: $title"
                                   :gallery="$galleryId" />

                        @if($slide['caption'] !== '')
                            <span class="block text-sm text-muted text-center font-italic">{{ $slide['caption'] }}</span>
                        @endif
                    </div>
                @endforeach

                @if($totalSlides > 1)
                    <div class="w-full flex flex-row flex-wrap items-center justify-between gap-sm bg-panel-sub border-panel border-rounded p-2">
                        <x-button type="button"
                                  variant="secondary"
                                  size="sm"
                                  icon="bi-chevron-left"
                                  :tooltip="lexicon('tooltip_ImageGallery_PrevSlide', 'Previous Slide')"
                                  @click.prevent="prev()">
                            {{ lexicon('btn_common_Previous', 'Prev') }}
                        </x-button>

                        <span class="text-xs font-bold text-muted">
                            {{ lexicon('label_common_Image', 'Image') }}
                            <span x-text="currentIndex + 1"></span>
                            {{ lexicon('label_common_Of', 'of') }}
                            <span x-text="totalSlides"></span>
                        </span>

                        <x-button type="button"
                                  variant="secondary"
                                  size="sm"
                                  icon="bi-chevron-right"
                                  :tooltip="lexicon('tooltip_ImageGallery_NextSlide', 'Next Slide')"
                                  @click.prevent="next()">
                            {{ lexicon('btn_common_Next', 'Next') }}
                        </x-button>
                    </div>
                @endif
            </div>
        @else
            <div class="bg-panel-sub border-dashed-panel border-rounded p-panel-body text-center w-full">
                <span class="text-sm text-muted font-italic">{{ lexicon('msg_common_ImageNotConfigured', 'No image URL has been configured.') }}</span>
            </div>
        @endif
    </x-tab>
</div>