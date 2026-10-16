{{-- 
@WidgetManifest({
    "name": "Character Avatar Gallery",
    "description": "Displays character portraits in a carousel view with bottom navigation controls, GLightbox zoom, and Dropzone upload management.",
    "icon": "bi-person-bounding-box",
    "fields": {
        "title": { "type": "text", "label": "Widget Title", "default": "Character Portraits" }
    }
})
--}}
@php
    $options = is_string($widget->pivot->instance_options ?? null)
        ? json_decode($widget->pivot->instance_options, true)
        : ($widget->pivot->instance_options ?? []);

    $title = !empty($options['title'])
        ? $options['title']
        : lexicon('label_CharacterAvatar_DefaultTitle', 'Character Portraits');

    $char = $character ?? null;
    $user = auth()->user();

    $canEditAvatar = $char && $user && (
        (int) $user->id === (int) $char->user_id ||
        $user->role === 'webmaster' ||
        $user->can('manage_roster_assignments') ||
        $user->can('manage_users')
    );

    $primaryUrl = null;
    $galleryItems = [];

    if ($char) {
        if (!empty($char->avatar)) {
            $primaryUrl = \Illuminate\Support\Str::startsWith($char->avatar, ['http://', 'https://', '/'])
                ? $char->avatar
                : asset('images/characters/' . $char->avatar);
        }

        $attributesCollection = $char->relationLoaded('attributes')
            ? $char->attributes
            : $char->attributes()->get();

        $galleryItems = $attributesCollection
            ->where('key', 'gallery_image')
            ->values()
            ->map(function ($attr) {
                $val = $attr->value;
                return [
                    'id'       => $attr->id,
                    'filename' => $val,
                    'url'      => \Illuminate\Support\Str::startsWith($val, ['http://', 'https://', '/'])
                        ? $val
                        : asset('images/characters/' . $val),
                ];
            })
            ->all();
    }

    $galleryGroup = $char ? 'char-portraits-' . $char->id : 'char-portraits';
@endphp

@if($char)
<div class="bg-panel border border-panel rounded-panel shadow-panel flex flex-col overflow-hidden w-full mb-6"
     x-data="characterAvatarManager(@js($primaryUrl), @js($galleryItems), @js($char->name), @js($galleryGroup), '{{ route('bio.avatar.primary', $char) }}', '{{ route('bio.avatar.delete', $char) }}')"
     @upload-completed.window="if ($event.detail && $event.detail.portraits) { syncPortraits($event.detail.portraits); }">

    <!-- Widget Header -->
    <div class="bg-panel-header text-h border-b-panel p-panel-header font-bold flex flex-row flex-wrap items-center justify-between gap-sm">
        <div class="flex flex-row items-center gap-xs"
             x-tooltip="'{{ addslashes(lexicon('tooltip_CharacterAvatar_Header', 'Character portrait gallery')) }}'">
            <i class="bi bi-person-bounding-box text-accent"></i>
            <span>{{ $title }}</span>
        </div>

        @if($canEditAvatar)
            <x-button variant="secondary"
                      class="text-xs"
                      @click="editModalOpen = true"
                      x-tooltip="'{{ addslashes(lexicon('tooltip_CharacterAvatar_EditPortraits', 'Upload or manage character portraits')) }}'">
                <i class="bi bi-camera"></i>
                <span>{{ lexicon('btn_CharacterAvatar_EditPortraits', 'Edit Portraits') }}</span>
            </x-button>
        @endif
    </div>

    <!-- Widget Body -->
    <div class="p-panel flex flex-col gap-md items-center">
        
        <!-- Carousel View -->
        <template x-if="currentImage">
            <div class="relative w-full flex flex-col gap-sm items-center">
                
                <!-- Main Image Display Container -->
                <a :href="currentImage"
                   class="glightbox block w-full overflow-hidden rounded-input relative group/img"
                   :data-gallery="galleryGroup"
                   :data-glightbox="'title: ' + characterName"
                   :aria-label="characterName"
                   x-tooltip="characterName">
                    <img :src="currentImage"
                         :alt="characterName"
                         class="w-full h-auto object-cover rounded-input border border-panel transition-transform duration-300 block">
                    <div class="absolute inset-0 bg-black/40 opacity-0 group-hover/img:opacity-100 transition-opacity flex items-center justify-center text-white text-sm font-bold gap-xs rounded-input pointer-events-none">
                        <i class="bi bi-zoom-in"></i> {{ lexicon('btn_common_Zoom', 'Zoom') }}
                    </div>
                </a>

                <!-- Bottom Navigation Bar (Arrows & Counter) -->
                <template x-if="allImages.length > 1">
                    <div class="w-full flex flex-row items-center justify-between bg-panel-sub border border-panel rounded-panel p-sm">
                        <x-button variant="secondary"
                                  class="text-xs px-3 py-1 flex items-center gap-xs"
                                  @click="prevImage()"
                                  x-tooltip="'Previous Image'">
                            <i class="bi bi-chevron-left"></i>
                            <span>Prev</span>
                        </x-button>

                        <span class="text-xs font-bold text-muted">
                            Image <span x-text="currentIndex + 1"></span> of <span x-text="allImages.length"></span>
                        </span>

                        <x-button variant="secondary"
                                  class="text-xs px-3 py-1 flex items-center gap-xs"
                                  @click="nextImage()"
                                  x-tooltip="'Next Image'">
                            <span>Next</span>
                            <i class="bi bi-chevron-right"></i>
                        </x-button>
                    </div>
                </template>
            </div>
        </template>

        <!-- Empty State -->
        <template x-if="!currentImage">
            <div class="bg-panel-sub border-dashed-panel rounded-panel p-panel text-center flex flex-col items-center justify-center gap-xs w-full">
                <i class="bi bi-person-square text-4xl text-muted"></i>
                <span class="text-muted italic text-sm">{{ lexicon('label_common_ImageNotConfigured', 'No image URL has been configured.') }}</span>
                @if($canEditAvatar)
                    <x-button variant="primary"
                              class="text-xs mt-2"
                              @click="editModalOpen = true"
                              x-tooltip="'{{ addslashes(lexicon('tooltip_CharacterAvatar_UploadFirst', 'Upload a portrait for this character')) }}'">
                        <i class="bi bi-cloud-arrow-up"></i>
                        <span>{{ lexicon('btn_CharacterAvatar_UploadPortrait', 'Upload Portrait') }}</span>
                    </x-button>
                @endif
            </div>
        </template>

    </div>

    <!-- Owner / Admin Portrait Management Modal -->
    @if($canEditAvatar)
        <x-modal show="editModalOpen" maxWidth="max-w-2xl">
            <x-slot name="top">
                <div class="flex flex-row items-center gap-xs">
                    <i class="bi bi-images text-accent"></i>
                    <span>{{ lexicon('label_CharacterAvatar_ManageModalTitle', 'Manage Character Portraits') }} — {{ $char->name }}</span>
                </div>
            </x-slot>

            <div class="flex flex-col gap-md">
                <!-- Dropzone Uploader -->
                <div class="flex flex-col gap-xs">
                    <label class="text-sm font-bold text-h">{{ lexicon('label_CharacterAvatar_UploadNew', 'Upload New Portrait') }}</label>
                    <p class="text-xs text-muted">{{ lexicon('msg_CharacterAvatar_UploadHint', 'If no primary portrait is set, your first upload becomes the primary avatar. Additional uploads are added to the portrait carousel.') }}</p>
                    <x-dropzone :url="route('bio.avatar.upload', $char)"
                                id="char-avatar-dropzone-{{ $char->id }}"
                                :maxFiles="10"
                                acceptedFiles="image/jpeg,image/png,image/webp,image/gif"
                                :title="lexicon('label_CharacterAvatar_DropzoneTitle', 'Drop character portrait here (.jpg, .png, .webp, .gif)')" />
                </div>

                <!-- Current Primary Portrait Management -->
                <div class="flex flex-col gap-xs border-t-panel pt-4">
                    <span class="text-sm font-bold text-h">{{ lexicon('label_CharacterAvatar_PrimaryPortrait', 'Primary Portrait') }}</span>

                    <template x-if="primaryUrl">
                        <div class="bg-panel-sub border border-panel rounded-panel p-sm flex flex-row items-center justify-between gap-md">
                            <div class="flex flex-row items-center gap-md">
                                <img :src="primaryUrl"
                                     :alt="characterName"
                                     class="w-16 h-16 object-cover rounded-input border border-panel block">
                                <div class="flex flex-col">
                                    <span class="text-sm font-bold text-h">{{ lexicon('label_CharacterAvatar_ActivePrimaryBadge', 'Active Primary Avatar') }}</span>
                                    <span class="text-xs text-muted">{{ lexicon('msg_CharacterAvatar_PrimaryNote', 'Displayed at the top of the biography and roster.') }}</span>
                                </div>
                            </div>
                            <x-button variant="danger"
                                      class="text-xs"
                                      @click="removePortrait('primary')"
                                      ::disabled="isBusy"
                                      x-tooltip="'{{ addslashes(lexicon('tooltip_CharacterAvatar_DeletePrimary', 'Remove primary portrait')) }}'">
                                <i class="bi bi-trash"></i>
                                <span>{{ lexicon('btn_common_Delete', 'Delete') }}</span>
                            </x-button>
                        </div>
                    </template>

                    <template x-if="!primaryUrl">
                        <div class="bg-panel-sub border-dashed-panel rounded-panel p-sm text-center">
                            <span class="text-xs text-muted italic">{{ lexicon('msg_CharacterAvatar_NoPrimarySet', 'No primary portrait currently set.') }}</span>
                        </div>
                    </template>
                </div>

                <!-- Additional Gallery Portraits Management -->
                <div class="flex flex-col gap-xs border-t-panel pt-4">
                    <span class="text-sm font-bold text-h">{{ lexicon('label_CharacterAvatar_AdditionalPortraits', 'Carousel Gallery Portraits') }}</span>

                    <template x-if="gallery.length > 0">
                        <div class="grid grid-cols-2 gap-sm">
                            <template x-for="item in gallery" :key="item.id">
                                <div class="bg-panel-sub border border-panel rounded-panel p-sm flex flex-row items-center justify-between gap-sm">
                                    <img :src="item.url"
                                         :alt="characterName"
                                         class="w-14 h-14 object-cover rounded-input border border-panel block">
                                    <div class="flex flex-row items-center gap-xs">
                                        <x-button variant="primary"
                                                  class="text-xs"
                                                  @click="setAsPrimary(item.id)"
                                                  ::disabled="isBusy"
                                                  x-tooltip="'{{ addslashes(lexicon('tooltip_CharacterAvatar_SetPrimary', 'Promote to primary character avatar')) }}'">
                                            <i class="bi bi-star-fill"></i>
                                            <span>{{ lexicon('btn_CharacterAvatar_SetPrimary', 'Make Primary') }}</span>
                                        </x-button>
                                        <x-button variant="danger"
                                                  class="text-xs"
                                                  @click="removePortrait('gallery', item.id)"
                                                  ::disabled="isBusy"
                                                  x-tooltip="'{{ addslashes(lexicon('tooltip_CharacterAvatar_DeleteGalleryItem', 'Delete this portrait')) }}'">
                                            <i class="bi bi-trash"></i>
                                        </x-button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>

                    <template x-if="gallery.length === 0">
                        <div class="bg-panel-sub border-dashed-panel rounded-panel p-sm text-center">
                            <span class="text-xs text-muted italic">{{ lexicon('msg_CharacterAvatar_NoGalleryItems', 'No additional gallery portraits uploaded.') }}</span>
                        </div>
                    </template>
                </div>
            </div>

            <x-slot name="bottom">
                <x-button variant="secondary"
                          @click="editModalOpen = false"
                          x-tooltip="'{{ addslashes(lexicon('tooltip_common_CloseModal', 'Close Modal')) }}'">
                    {{ lexicon('btn_common_Done', 'Done') }}
                </x-button>
            </x-slot>
        </x-modal>
    @endif
</div>
@endif