@extends('layouts.app')

@section('title', lexicon('admin_label_GeneralImages', 'General Asset Manager'))

@section('content')
<div x-data="generalImageManager()" @upload-completed.window="loadImages()">
    <x-admin-grid-wrapper :page="$systemPage">
        <x-slot:content>
            <x-page-header :title="lexicon('admin_label_GeneralImages', 'General Asset Manager')">
                <x-slot:actions>
                    <x-button variant="success" @click="uploadModalOpen = true"
                              x-tooltip="'{{ addslashes(lexicon('tooltip_GeneralImages_Upload', 'Upload new general site graphics')) }}'">
                        + {{ lexicon('btn_GeneralImages_Upload', 'Upload Asset') }}
                    </x-button>
                </x-slot:actions>
            </x-page-header>

            <!-- Asset Grid -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-md mt-4">
                <template x-for="img in images" :key="img.filename">
                    <div class="bg-panel border border-panel rounded-panel shadow-panel overflow-hidden flex flex-col justify-between">
                        <div class="h-32 bg-panel-sub overflow-hidden flex items-center justify-center p-2 relative group">
                            <img :src="img.url" :alt="img.filename" class="max-h-full max-w-full object-contain rounded">
                            <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-sm">
                                <x-button variant="primary" class="text-xs px-2 py-1" @click="copyUrl(img.url)" x-tooltip="'Copy URL'">
                                    <i class="bi bi-clipboard"></i>
                                </x-button>
                                <x-button variant="danger" class="text-xs px-2 py-1" @click="deleteImage(img.filename)" x-tooltip="'Delete'">
                                    <i class="bi bi-trash"></i>
                                </x-button>
                            </div>
                        </div>
                        <div class="p-2 bg-panel-header border-t border-panel flex flex-col">
                            <span class="text-xs font-bold text-p truncate" x-text="img.filename"></span>
                            <span class="text-[10px] text-muted" x-text="img.size"></span>
                        </div>
                    </div>
                </template>
            </div>

            <template x-if="images.length === 0 && !isLoading">
                <div class="bg-panel-sub border-dashed-panel rounded-panel p-panel text-center mt-4">
                    <span class="text-muted italic text-sm">{{ lexicon('msg_GeneralImages_NoAssets', 'No general site assets uploaded yet.') }}</span>
                </div>
            </template>

            <!-- UPLOAD MODAL -->
            <x-modal show="uploadModalOpen" maxWidth="max-w-lg">
                <x-slot:top>
                    <span>{{ lexicon('label_GeneralImages_UploadModalTitle', 'Upload General Site Asset') }}</span>
                </x-slot:top>

                <div class="flex flex-col gap-md">
                    <p class="text-xs text-muted">{{ lexicon('msg_GeneralImages_UploadHint', 'Upload images (.jpg, .png, .webp, .svg, .gif) to be used across image and image gallery widgets. Max file size: 10 MB.') }}</p>
                    <x-dropzone :url="route('admin.general-images.store')"
                                id="general-asset-dropzone"
                                :maxFiles="10"
                                acceptedFiles="image/jpeg,image/png,image/webp,image/gif,image/svg+xml"
                                :title="lexicon('label_GeneralImages_DropzoneTitle', 'Drop general assets here')" />
                </div>

                <x-slot:bottom>
                    <x-button variant="secondary" @click="uploadModalOpen = false">
                        {{ lexicon('btn_common_Done', 'Done') }}
                    </x-button>
                </x-slot:bottom>
            </x-modal>

        </x-slot:content>
    </x-admin-grid-wrapper>
</div>
@endsection