{{-- 
@WidgetManifest({
    "name": "Persona Portrait Gallery",
    "description": "Displays persona portraits in a carousel view with bottom navigation controls, GLightbox zoom, and Dropzone upload management.",
    "icon": "bi-person-bounding-box",
    "fields": {
        "title": { "type": "text", "label": "Widget Title", "default": "Persona Portraits" }
    }
})
--}}
@php
    $options = is_string($widget->pivot->instance_options ?? null)
        ? json_decode($widget->pivot->instance_options, true)
        : ($widget->pivot->instance_options ?? []);

    $title = !empty($options['title'])
        ? $options['title']
        : lexicon('label_PersonaPortrait_DefaultTitle', 'Persona Portraits');

    $targetPersona = $persona ?? $character ?? null;
    $user = auth()->user();

    $canEditAvatar = $targetPersona &&$user && (
        (int) $user->id === (int) $targetPersona->user_id \vert{}\vert{}$user->hasRole('webmaster') ||
        $user->can('manage_roster_assignments') \vert{}\vert{}$user->can('manage_users')
    );

    $primaryUrl = null;
    $galleryItems = [];

    if ($targetPersona) {$avatarFile = $targetPersona->portrait_image ?? $targetPersona->avatar ?? null;
        if (!empty($avatarFile)) {
            $primaryUrl = \Illuminate\Support\Str::startsWith($avatarFile, ['http://', 'https://', '/'])
                ? $avatarFile
                : asset('images/characters/' . $avatarFile);
        }

        $metadataCollection = collect();
        if (method_exists($targetPersona, 'metadata')) {
            $metadataCollection =$targetPersona->relationLoaded('metadata')
                ? $targetPersona->metadata
                : $targetPersona->metadata()->get();
        } elseif (method_exists($targetPersona, 'attributes')) {
            $metadataCollection =$targetPersona->relationLoaded('attributes')
                ? $targetPersona->attributes
                : $targetPersona->attributes()->get();
        }

        $galleryItems =$metadataCollection
            ->filter(function ($meta) {$key = $meta->metadata_key ?? $meta->key ?? '';
                return $key === 'gallery_image';
            })
            ->values()
            ->map(function ($meta) {$val = $meta->metadata_value ?? $meta->value ?? '';
                return [
                    'id'       => $meta->id,
                    'filename' => $val,
                    'url'      => \Illuminate\Support\Str::startsWith($val, ['http://', 'https://', '/'])
                        ? $val
                        : asset('images/characters/' . $val),
                ];
            })
            ->all();
    }

    $personaDisplayName =$targetPersona?->persona_name ?? $targetPersona?->persona_designation ?? $targetPersona?->name ?? '';
    $galleryGroup = $targetPersona ? 'persona-portraits-' . $targetPersona->id : 'persona-portraits';
@endphp

@if($targetPersona)
<div class="bg-panel border border-panel rounded-panel shadow-panel flex flex-col overflow-hidden w-full mb-6"
     x-data="characterAvatarManager(@js($primaryUrl), @js($galleryItems), @js($personaDisplayName), @js($galleryGroup), '{{ route('bio.avatar.primary', $targetPersona) }}', '{{ route('bio.avatar.delete',$targetPersona) }}')"
     @upload-completed.window="if ($event.detail && $event.detail.portraits) { syncPortraits($event.detail.portraits); }">

    <!-- Widget Header -->
    <div class="bg-panel-header text-h border-b-panel p-panel-header font-bold flex flex-row flex-wrap items-center justify-between gap-sm">
        <div class="flex flex-row items-center gap-xs"
             x-tooltip="'{{ addslashes(lexicon('tooltip_PersonaPortrait_Header', 'Persona portrait gallery')) }}'">
            <i class="bi bi-person-bounding-box text-accent"></i>
            <span>{{ $title }}</span>
        </div>

        @if($canEditAvatar)
            <x-button variant="secondary"
                      class="text-xs"
                      @click="editModalOpen = true"
                      x-tooltip="'{{ addslashes(lexicon('tooltip_PersonaPortrait_EditPortraits', 'Upload or manage persona portraits')) }}'">
                <i class="bi bi-camera"></i>
                <span>{{ lexicon('btn_PersonaPortrait_EditPortraits', 'Edit Portraits') }}</span>
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
                   :data-glightbox="'title: ' + personaName"
                   :aria-label="personaName"
                   x-tooltip="personaName">
                    <img :src="currentImage"
                         :alt="personaName"
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
                              x-tooltip="'{{ addslashes(lexicon('tooltip_PersonaPortrait_UploadFirst', 'Upload a portrait for this persona')) }}'">
                        <i class="bi bi-cloud-arrow-up"></i>
                        <span>{{ lexicon('btn_PersonaPortrait_UploadPortrait', 'Upload Portrait') }}</span>
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
                    <span>{{ lexicon('label_PersonaPortrait_ManageModalTitle', 'Manage Persona Portraits') }} — {{ $personaDisplayName }}</span>
                </div>
            </x-slot>

            <div class="flex flex-col gap-md">
                <!-- Dropzone Uploader -->
                <div class="flex flex-col gap-xs">
                    <label class="text-sm font-bold text-h">{{ lexicon('label_PersonaPortrait_UploadNew', 'Upload New Portrait') }}</label>
                    <p class="text-xs text-muted">{{ lexicon('msg_PersonaPortrait_UploadHint', 'If no primary portrait is set, your first upload becomes the primary portrait. Additional uploads are added to the portrait carousel.') }}</p>
                    <x-dropzone :url="route('bio.avatar.upload', $targetPersona)"
                                id="persona-avatar-dropzone-{{ $targetPersona->id }}"
                                :maxFiles="10"
                                acceptedFiles="image/jpeg,image/png,image/webp,image/gif"
                                :title="lexicon('label_PersonaPortrait_DropzoneTitle', 'Drop persona portrait here (.jpg, .png, .webp, .gif)')" />
                </div>

                <!-- Current Primary Portrait Management -->
                <div class="flex flex-col gap-xs border-t-panel pt-4">
                    <span class="text-sm font-bold text-h">{{ lexicon('label_PersonaPortrait_PrimaryPortrait', 'Primary Portrait') }}</span>

                    <template x-if="primaryUrl">
                        <div class="bg-panel-sub border border-panel rounded-panel p-sm flex flex-row items-center justify-between gap-md">
                            <div class="flex flex-row items-center gap-md">
                                <img :src="primaryUrl"
                                     :alt="personaName"
                                     class="w-16 h-16 object-cover rounded-input border border-panel block">
                                <div class="flex flex-col">
                                    <span class="text-sm font-bold text-h">{{ lexicon('label_PersonaPortrait_ActivePrimaryBadge', 'Active Primary Portrait') }}</span>
                                    <span class="text-xs text-muted">{{ lexicon('msg_PersonaPortrait_PrimaryNote', 'Displayed at the top of the biography and personae table.') }}</span>
                                </div>
                            </div>
                            <form action="{{ route('bio.avatar.delete', $targetPersona) }}" method="POST" x-data="ajaxForm" @submit.prevent="submit" @ajax-success.window="syncPortraits($event.detail.portraits)">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="type" value="primary">
                                <x-button variant="danger" class="text-xs" type="submit" x-tooltip="'Remove primary portrait'">
                                    <i class="bi bi-trash"></i>
                                    <span>{{ lexicon('btn_common_Delete', 'Delete') }}</span>
                                </x-button>
                            </form>
                        </div>
                    </template>

                    <template x-if="!primaryUrl">
                        <div class="bg-panel-sub border-dashed-panel rounded-panel p-sm text-center">
                            <span class="text-xs text-muted italic">{{ lexicon('msg_PersonaPortrait_NoPrimarySet', 'No primary portrait currently set.') }}</span>
                        </div>
                    </template>
                </div>

                <!-- Additional Gallery Portraits Management -->
                <div class="flex flex-col gap-xs border-t-panel pt-4">
                    <span class="text-sm font-bold text-h">{{ lexicon('label_PersonaPortrait_AdditionalPortraits', 'Carousel Gallery Portraits') }}</span>

                    <template x-if="gallery.length > 0">
                        <div class="grid grid-cols-2 gap-sm">
                            <template x-for="item in gallery" :key="item.id">
                                <div class="bg-panel-sub border border-panel rounded-panel p-sm flex flex-row items-center justify-between gap-sm">
                                    <img :src="item.url"
                                         :alt="personaName"
                                         class="w-14 h-14 object-cover rounded-input border border-panel block">
                                    <div class="flex flex-row items-center gap-xs">
                                        <form action="{{ route('bio.avatar.primary', $targetPersona) }}" method="POST" x-data="ajaxForm" @submit.prevent="submit" @ajax-success.window="syncPortraits($event.detail.portraits)">
                                            @csrf
                                            <input type="hidden" name="attribute_id" :value="item.id">
                                            <x-button variant="primary" class="text-xs" type="submit" x-tooltip="'Promote to primary persona portrait'">
                                                <i class="bi bi-star-fill"></i>
                                                <span>{{ lexicon('btn_PersonaPortrait_SetPrimary', 'Make Primary') }}</span>
                                            </x-button>
                                        </form>
                                        <form action="{{ route('bio.avatar.delete', $targetPersona) }}" method="POST" x-data="ajaxForm" @submit.prevent="submit" @ajax-success.window="syncPortraits($event.detail.portraits)">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="type" value="gallery">
                                            <input type="hidden" name="attribute_id" :value="item.id">
                                            <x-button variant="danger" class="text-xs" type="submit" x-tooltip="'Delete this portrait'">
                                                <i class="bi bi-trash"></i>
                                            </x-button>
                                        </form>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>

                    <template x-if="gallery.length === 0">
                        <div class="bg-panel-sub border-dashed-panel rounded-panel p-sm text-center">
                            <span class="text-xs text-muted italic">{{ lexicon('msg_PersonaPortrait_NoGalleryItems', 'No additional gallery portraits uploaded.') }}</span>
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