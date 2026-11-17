{{-- resources/views/modules/persona_portrait.blade.php --}}
{{-- @WidgetManifest({ "name": "Persona Portrait Gallery", "description": "Displays persona portraits in a carousel view with bottom navigation controls, GLightbox zoom, and Dropzone upload management.", "icon": "bi-person-bounding-box", "fields": { "title": { "type": "text", "label": "Module Title", "default": "Persona Portraits" } } }) --}}
@php
    $rawOptions = $moduleSettings
        ?? $module?->pivot?->book_config
        ?? $module?->pivot?->instance_options
        ?? $widget?->pivot?->instance_options
        ?? [];
    $options = is_string($rawOptions) ? (json_decode($rawOptions, true) ?: []) : (is_array($rawOptions) ? $rawOptions : []);

    $rawTitle = trim((string) ($options['title'] ?? ''));
    $title = $rawTitle !== '' ? $rawTitle : lexicon('label_PersonaPortrait_DefaultTitle', 'Persona Portraits');

    $targetPersona = $persona ?? $character ?? null;
    $user = auth()->user();

    $canEditAvatar = $targetPersona && $user && (
        (int) $user->id === (int) $targetPersona->user_id ||
        $user->hasRole('webmaster') ||
        $user->can('manage_roster_assignments') ||
        $user->can('manage_users')
    );

    $primaryUrl = null;
    $galleryItems = [];

    if ($targetPersona) {
        $avatarFile = $targetPersona->primary_portrait_url ?? $targetPersona->primary_portrait_uri ?? null;
        if (!empty($avatarFile)) {
            $primaryUrl = \Illuminate\Support\Str::startsWith($avatarFile, ['http://', 'https://', '/'])
                ? $avatarFile
                : asset('images/characters/' . $avatarFile);
        }

        $metadataCollection = $targetPersona->relationLoaded('attributes')
            ? $targetPersona->attributes
            : $targetPersona->attributes()->get();

        $galleryItems = $metadataCollection
            ->where('attribute_key', 'gallery_image')
            ->values()
            ->map(function ($meta) {
                $val = (string) $meta->attribute_value;
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

    $personaDisplayName = $targetPersona?->persona_name ?? lexicon('label_common_Persona', 'Persona');
    $galleryGroup = $targetPersona ? 'persona-portraits-' . $targetPersona->id : 'persona-portraits';

    $uploadEndpoint = $targetPersona
        ? (\Illuminate\Support\Facades\Route::has('bio.portrait.upload')
            ? route('bio.portrait.upload', $targetPersona)
            : url('/bio/' . $targetPersona->id . '/portrait'))
        : '';
    $primaryEndpoint = $targetPersona
        ? (\Illuminate\Support\Facades\Route::has('bio.portrait.primary')
            ? route('bio.portrait.primary', $targetPersona)
            : url('/bio/' . $targetPersona->id . '/portrait/primary'))
        : '';
    $deleteEndpoint = $targetPersona
        ? (\Illuminate\Support\Facades\Route::has('bio.portrait.delete')
            ? route('bio.portrait.delete', $targetPersona)
            : url('/bio/' . $targetPersona->id . '/portrait'))
        : '';
@endphp

@if($targetPersona)
    <div x-data="avatarManager(
             @js($primaryUrl),
             @js($galleryItems),
             @js($personaDisplayName),
             @js($galleryGroup),
             @js($primaryEndpoint),
             @js($deleteEndpoint)
         )"
         x-ref="avatarGalleryContent"
         @ajax-success.window="if ($event.detail && ($event.detail.data || $event.detail.payload)) { syncPortraits($event.detail.data || $event.detail.payload); }"
         class="w-full">
        <x-tab type="accordion"
               :title="$title"
               icon="bi-person-bounding-box"
               :open="true">
            @if($canEditAvatar)
                <x-slot:top>
                    <x-button type="button"
                              variant="secondary"
                              size="sm"
                              icon="bi-camera"
                              :tooltip="lexicon('tooltip_PersonaPortrait_EditPortraits', 'Upload or manage persona portraits')"
                              @click="openModal('edit')">
                        {{ lexicon('btn_PersonaPortrait_EditPortraits', 'Edit Portraits') }}
                    </x-button>
                </x-slot:top>
            @endif

            <div class="flex flex-col gap-md items-center w-full">
                {{-- Carousel View --}}
                <template x-if="currentImage">
                    <div class="relative w-full flex flex-col gap-sm items-center">
                        <a :href="currentImage"
                           class="glightbox block w-full overflow-hidden border-rounded relative flex items-center justify-center drop-shadow-badge"
                           :data-gallery="galleryGroup"
                           :data-glightbox="'title: ' + personaName"
                           :aria-label="personaName"
                           :title="personaName">
                            <img :src="currentImage"
                                 :alt="personaName"
                                 class="w-full h-auto object-cover border-rounded border-panel block">
                        </a>

                        {{-- Bottom Navigation Bar (Arrows & Counter) --}}
                        <template x-if="allImages.length > 1">
                            <div class="w-full flex flex-row flex-wrap items-center justify-between gap-sm bg-panel-sub border-panel border-rounded p-2">
                                <x-button type="button"
                                          variant="secondary"
                                          size="sm"
                                          icon="bi-chevron-left"
                                          :tooltip="lexicon('tooltip_PersonaPortrait_PrevImage', 'Previous Portrait')"
                                          @click="prevImage()">
                                    {{ lexicon('btn_common_Previous', 'Prev') }}
                                </x-button>

                                <span class="text-xs font-bold text-muted">
                                    {{ lexicon('label_common_Image', 'Image') }}
                                    <span x-text="currentIndex + 1"></span>
                                    {{ lexicon('label_common_Of', 'of') }}
                                    <span x-text="allImages.length"></span>
                                </span>

                                <x-button type="button"
                                          variant="secondary"
                                          size="sm"
                                          icon="bi-chevron-right"
                                          :tooltip="lexicon('tooltip_PersonaPortrait_NextImage', 'Next Portrait')"
                                          @click="nextImage()">
                                    {{ lexicon('btn_common_Next', 'Next') }}
                                </x-button>
                            </div>
                        </template>
                    </div>
                </template>

                {{-- Empty State --}}
                <template x-if="!currentImage">
                    <div class="bg-panel-sub border-dashed-panel border-rounded p-panel-body text-center flex flex-col items-center justify-center gap-sm w-full">
                        <i class="bi bi-person-square text-2xl text-muted" aria-hidden="true"></i>
                        <span class="text-muted font-italic text-sm">{{ lexicon('label_common_ImageNotConfigured', 'No image URL has been configured.') }}</span>

                        @if($canEditAvatar)
                            <x-button type="button"
                                      variant="primary"
                                      size="sm"
                                      icon="bi-cloud-arrow-up"
                                      :tooltip="lexicon('tooltip_PersonaPortrait_UploadFirst', 'Upload a portrait for this persona')"
                                      @click="openModal('edit')">
                                {{ lexicon('btn_PersonaPortrait_UploadPortrait', 'Upload Portrait') }}
                            </x-button>
                        @endif
                    </div>
                </template>
            </div>
        </x-tab>

        {{-- Owner / Admin Portrait Management Modal --}}
        @if($canEditAvatar)
            <x-modal show="modals.avatar.open"
                     onClose="closeModal()"
                     maxWidth="4xl">
                <x-slot:top>
                    <div class="flex flex-row items-center gap-xs">
                        <i class="bi bi-images text-accent" aria-hidden="true"></i>
                        <span class="font-bold text-h">{{ lexicon('label_PersonaPortrait_ManageModalTitle', 'Manage Persona Portraits') }} — {{ $personaDisplayName }}</span>
                    </div>
                </x-slot:top>

                <div class="flex flex-col gap-md">
                    {{-- Dropzone Uploader --}}
                    <div class="flex flex-col gap-xs">
                        <span class="text-sm font-bold text-h">{{ lexicon('label_PersonaPortrait_UploadNew', 'Upload New Portrait') }}</span>
                        <span class="text-xs text-muted">{{ lexicon('msg_PersonaPortrait_UploadHint', 'If no primary portrait is set, your first upload becomes the primary portrait. Additional uploads are added to the portrait carousel.') }}</span>
                        <x-dropzone :url="$uploadEndpoint"
                                    id="persona-avatar-dropzone-{{ $targetPersona->id }}"
                                    :maxFiles="10"
                                    acceptedFiles="image/jpeg,image/png,image/webp,image/gif"
                                    :title="lexicon('label_PersonaPortrait_DropzoneTitle', 'Drop persona portrait here (.jpg, .png, .webp, .gif)')" />
                    </div>

                    {{-- Current Primary Portrait Management --}}
                    <div class="flex flex-col gap-xs border-t-actions pt-4">
                        <span class="text-sm font-bold text-h">{{ lexicon('label_PersonaPortrait_PrimaryPortrait', 'Primary Portrait') }}</span>

                        <template x-if="primaryUrl">
                            <div class="bg-panel-sub border-panel border-rounded p-3 flex flex-row flex-wrap items-center justify-between gap-md">
                                <div class="flex flex-row items-center gap-md">
                                    <img :src="primaryUrl"
                                         :alt="personaName"
                                         class="w-16 h-16 object-cover border-rounded border-panel block">
                                    <div class="flex flex-col gap-xs">
                                        <span class="text-sm font-bold text-h">{{ lexicon('label_PersonaPortrait_ActivePrimaryBadge', 'Active Primary Portrait') }}</span>
                                        <span class="text-xs text-muted">{{ lexicon('msg_PersonaPortrait_PrimaryNote', 'Displayed at the top of the biography and personae table.') }}</span>
                                    </div>
                                </div>

                                <x-meta :action="$deleteEndpoint" method="DELETE" class="w-auto">
                                    <x-metadata type="hidden" name="type" value="primary" />
                                    <x-button type="submit"
                                              variant="danger"
                                              size="sm"
                                              icon="bi-trash"
                                              :tooltip="lexicon('tooltip_PersonaPortrait_DeletePrimary', 'Remove primary portrait')">
                                        {{ lexicon('btn_common_Delete', 'Delete') }}
                                    </x-button>
                                </x-meta>
                            </div>
                        </template>

                        <template x-if="!primaryUrl">
                            <div class="bg-panel-sub border-dashed-panel border-rounded p-3 text-center">
                                <span class="text-xs text-muted font-italic">{{ lexicon('msg_PersonaPortrait_NoPrimarySet', 'No primary portrait currently set.') }}</span>
                            </div>
                        </template>
                    </div>

                    {{-- Additional Gallery Portraits Management --}}
                    <div class="flex flex-col gap-xs border-t-actions pt-4">
                        <span class="text-sm font-bold text-h">{{ lexicon('label_PersonaPortrait_AdditionalPortraits', 'Carousel Gallery Portraits') }}</span>

                        <template x-if="gallery.length > 0">
                            <div class="grid grid-cols-1 md-grid-cols-2 gap-sm">
                                <template x-for="item in gallery" :key="item.id">
                                    <div class="bg-panel-sub border-panel border-rounded p-3 flex flex-row flex-wrap items-center justify-between gap-sm">
                                        <img :src="item.url"
                                             :alt="personaName"
                                             class="w-14 h-14 object-cover border-rounded border-panel block">

                                        <div class="flex flex-row items-center gap-xs">
                                            <x-meta :action="$primaryEndpoint" method="POST" class="w-auto">
                                                <input type="hidden" name="attribute_id" :value="item.id">
                                                <x-button type="submit"
                                                          variant="primary"
                                                          size="sm"
                                                          icon="bi-star-fill"
                                                          :tooltip="lexicon('tooltip_PersonaPortrait_SetPrimary', 'Promote to primary persona portrait')">
                                                    {{ lexicon('btn_PersonaPortrait_SetPrimary', 'Make Primary') }}
                                                </x-button>
                                            </x-meta>

                                            <x-meta :action="$deleteEndpoint" method="DELETE" class="w-auto">
                                                <x-metadata type="hidden" name="type" value="gallery" />
                                                <input type="hidden" name="attribute_id" :value="item.id">
                                                <x-button type="submit"
                                                          variant="danger"
                                                          size="sm"
                                                          icon="bi-trash"
                                                          :tooltip="lexicon('tooltip_PersonaPortrait_DeleteGalleryItem', 'Delete this portrait')" />
                                            </x-meta>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>

                        <template x-if="gallery.length === 0">
                            <div class="bg-panel-sub border-dashed-panel border-rounded p-3 text-center">
                                <span class="text-xs text-muted font-italic">{{ lexicon('msg_PersonaPortrait_NoGalleryItems', 'No additional gallery portraits uploaded.') }}</span>
                            </div>
                        </template>
                    </div>
                </div>

                <x-slot:bottom>
                    <x-button type="button"
                              variant="secondary"
                              :tooltip="lexicon('tooltip_common_CloseModal', 'Close Modal')"
                              @click="closeModal()">
                        {{ lexicon('btn_common_Done', 'Done') }}
                    </x-button>
                </x-slot:bottom>
            </x-modal>
        @endif
    </div>
@endif