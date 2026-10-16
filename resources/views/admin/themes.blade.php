@extends('layouts.app')

@section('title', lexicon('admin_label_theme', 'Theme & Skin Engine'))

@section('content')
<div x-data="themeManager()"
    @ajax-success.window="refreshDOM()">

    <x-admin-grid-wrapper :page="$systemPage">

        <x-slot:top>
            <x-page-header :title="lexicon('admin_label_theme', 'Theme & Skin Engine')" />

            @if (session('status'))
                <div x-data="sessionAlert('{{ addslashes(session('status')) }}')"></div>
            @endif
        </x-slot:top>

        <x-slot:middle>
            <div class="flex flex-col gap-md">
                {{-- ACCORDION 1: Installed Skins Manager --}}
                <x-accordion-panel :title="lexicon('label_Themes_InstalledSkins', 'Installed Skin Packages')" :expanded="true">
                    @can('manage_themes')
                        <x-slot:top>
                            <x-button type="button"
                                      variant="primary"
                                      class="p-btn-sm text-sm"
                                      @click="modals.uploadSkin.open = true"
                                      aria-label="{{ lexicon('tooltip_Themes_UploadSkin', 'Upload a new .zip skin package') }}"
                                      x-tooltip="'{{ addslashes(lexicon('tooltip_Themes_UploadSkin', 'Upload a new .zip skin package')) }}'">
                                <i class="bi bi-cloud-arrow-up mr-1" aria-hidden="true"></i>
                                <span>{{ lexicon('btn_Themes_UploadSkin', 'Upload Skin') }}</span>
                            </x-button>
                        </x-slot:top>
                    @endcan

                    <x-table id="skins-table-wrapper" x-ref="skinsTableWrapper">
                        <x-slot:headers>
                            <th class="p-4 text-left">{{ lexicon('label_Themes_SkinName', 'Skin Name') }}</th>
                            <th class="p-4 text-left">{{ lexicon('label_Themes_DirectoryVersion', 'Directory / Version') }}</th>
                            <th class="p-4 text-left">{{ lexicon('label_common_Status', 'Status') }}</th>
                            <th class="p-4 text-right">{{ lexicon('label_common_Actions', 'Actions') }}</th>
                        </x-slot:headers>

                        @forelse($skins as $skin)
                            <tr class="border-b-table-row hover-bg-table-row">
                                <td class="p-4 text-left font-bold text-p">
                                    <span>{{ $skin->name }}</span>
                                </td>
                                <td class="p-4 text-left font-mono text-xs text-muted">
                                    <div class="flex flex-row items-center gap-xs">
                                        <span>/skins/{{ $skin->folder_name }}</span>
                                        <button type="button"
                                                class="bg-transparent text-muted border-none hover-text-accent cursor-pointer p-0 text-xs"
                                                x-clipboard="'/skins/{{ addslashes($skin->folder_name) }}'"
                                                aria-label="{{ lexicon('tooltip_Themes_CopyPath', 'Copy skin directory path') }}"
                                                x-tooltip="'{{ addslashes(lexicon('tooltip_Themes_CopyPath', 'Copy skin directory path')) }}'">
                                            <i class="bi bi-clipboard" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                    <span class="block mt-1">v{{ $skin->version ?? '1.0' }}</span>
                                </td>
                                <td class="p-4 text-left">
                                    @if($skin->is_global_default)
                                        <x-badge variant="success"
                                                 class="border"
                                                 aria-label="{{ lexicon('tooltip_Themes_GlobalDefault', 'Active global default skin') }}"
                                                 x-tooltip="'{{ addslashes(lexicon('tooltip_Themes_GlobalDefault', 'Active global default skin')) }}'">
                                            <i class="bi bi-check-circle mr-1" aria-hidden="true"></i>
                                            <span>{{ lexicon('label_Themes_StatusActive', 'Global Default') }}</span>
                                        </x-badge>
                                    @else
                                        <x-badge variant="completed"
                                                 class="border"
                                                 aria-label="{{ lexicon('tooltip_Themes_StatusInactive', 'Installed but not set as global default') }}"
                                                 x-tooltip="'{{ addslashes(lexicon('tooltip_Themes_StatusInactive', 'Installed but not set as global default')) }}'">
                                            <span>{{ lexicon('msg_Themes_StatusInactive', 'Inactive') }}</span>
                                        </x-badge>
                                    @endif
                                </td>
                                <td class="p-4 text-right">
                                    <div class="flex flex-row flex-wrap justify-end gap-sm items-center">
                                        @can('manage_themes')
                                            @if(!$skin->is_global_default)
                                                <form action="/admin/themes/{{ $skin->id }}/activate"
                                                      method="POST"
                                                      x-data="ajaxForm"
                                                      @submit.prevent="submit"
                                                      data-loading-title="{{ lexicon('msg_Themes_ActivatingSkin', 'Activating Skin...') }}">
                                                    @csrf
                                                    <x-button type="submit"
                                                              variant="text"
                                                              class="text-accent hover-text-h text-sm"
                                                              aria-label="{{ lexicon('tooltip_Themes_ActivateSkin', 'Set this skin as the global site default') }}"
                                                              x-tooltip="'{{ addslashes(lexicon('tooltip_Themes_ActivateSkin', 'Set this skin as the global site default')) }}'">
                                                        <i class="bi bi-lightning-charge mr-1" aria-hidden="true"></i>
                                                        <span>{{ lexicon('btn_Themes_Activate', 'Activate') }}</span>
                                                    </x-button>
                                                </form>
                                            @endif
                                        @endcan
                                        <x-button variant="text"
                                                  href="/admin/themes/{{ $skin->id }}/export"
                                                  class="text-sm"
                                                  aria-label="{{ lexicon('tooltip_Themes_ExportSkin', 'Download this skin package as a .zip archive') }}"
                                                  x-tooltip="'{{ addslashes(lexicon('tooltip_Themes_ExportSkin', 'Download this skin package as a .zip archive')) }}'">
                                            <i class="bi bi-download mr-1" aria-hidden="true"></i>
                                            <span>{{ lexicon('btn_Themes_Export', 'Export') }}</span>
                                        </x-button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="p-6 text-center text-muted italic">
                                    <span>{{ lexicon('msg_Themes_NoSkins', 'No custom skins installed.') }}</span>
                                </td>
                            </tr>
                        @endforelse
                    </x-table>
                </x-accordion-panel>

                {{-- GLOBAL THEME VARIABLE & CUSTOM HEAD FORM --}}
                <form action="/admin/themes/css"
                      method="POST"
                      class="flex flex-col gap-md"
                      x-data="ajaxForm"
                      @submit.prevent="submit"
                      data-loading-title="{{ lexicon('msg_Themes_SavingCss', 'Saving Theme Variables...') }}">
                    @csrf

                    {{-- ACCORDION 2: Custom Head Injection --}}
                    <x-accordion-panel :title="lexicon('label_Themes_CustomHead', 'Custom <head> Injection (Fonts, CDNs, Meta)')">
                        <x-slot:top>
                            <span class="text-xs text-muted">{{ lexicon('msg_Themes_CustomHeadSub', 'Manage external scripts, fonts, and meta tags.') }}</span>
                        </x-slot:top>

                        <div class="p-panel flex flex-col flex-grow bg-panel-sub border border-panel rounded-panel gap-xs">
                            <textarea name="custom_head"
                                      aria-label="{{ lexicon('label_Themes_CustomHead', 'Custom <head> Injection (Fonts, CDNs, Meta)') }}"
                                      x-tooltip="'{{ addslashes(lexicon('tooltip_Themes_CustomHeadInput', 'Enter custom HTML link or meta tags for the active skin')) }}'"
                                      x-init="window.autosize && window.autosize($el)"
                                      class="bg-input text-input border-input rounded-input p-input outline-none w-full box-border focus-border-accent focus-shadow-accent font-mono text-sm"
                                      rows="3"
                                      placeholder="{{ lexicon('label_Themes_CustomHeadPlaceholder', '<link href=\'https://fonts.googleapis.com/css2?family=Roboto&display=swap\' rel=\'stylesheet\'>') }}">{{ old('custom_head', $skins->where('is_global_default', 1)->first()->custom_head ?? '') }}</textarea>
                            <span class="block text-xs text-muted border-l-2 border-accent pl-2">
                                {{ lexicon('msg_Themes_CustomHeadHint', 'These tags will be injected directly into the master layout for the active skin. Do not include your core CSS files here.') }}
                            </span>
                        </div>
                    </x-accordion-panel>

                    {{-- ACCORDION 3: Visual Theme Customiser --}}
                    <x-accordion-panel :title="lexicon('label_Themes_Editor', 'Visual Theme Customiser')">
                        <x-slot:top>
                            <span class="text-xs text-muted">{{ lexicon('msg_Themes_EditorDesc', 'Easily adjust the colours and settings of your active skin.') }}</span>
                        </x-slot:top>

                        <div class="p-panel flex flex-col flex-grow overflow-y-auto gap-lg">
                            <div class="w-full md-w-1-2">
                                <x-form-input name="theme_var_search"
                                              id="theme-var-search"
                                              :label="lexicon('label_Themes_SearchVars', 'Filter Theme Variables')"
                                              :placeholder="lexicon('label_Themes_SearchVarsPlaceholder', 'Search by variable name, CSS token, or selector...')"
                                              :tooltip="lexicon('tooltip_Themes_SearchVars', 'Instantaneously filter theme variables using fuzzy search')"
                                              xModel="varSearchQuery" />
                            </div>

                            @foreach($groupedVars as $groupName => $vars)
                                <div class="flex flex-col gap-md">
                                    <span class="block font-bold text-accent pb-2 border-b uppercase tracking-wider text-sm">
                                        {{ $groupName }}
                                    </span>

                                    <div class="grid grid-cols-1 lg-grid-cols-3 gap-sm w-full">
                                        @foreach($vars as $key => $varData)
                                            <x-theme-var-card :varKey="$key"
                                                              :varData="$varData"
                                                              :structuralOptions="$structuralOptions" />
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        @can('manage_themes')
                            <div class="border-t-actions pt-6 p-4 text-right bg-panel-sub">
                                <x-button type="submit"
                                          variant="primary"
                                          class="p-btn-lg text-base"
                                          aria-label="{{ lexicon('tooltip_Themes_SaveCss', 'Save custom head tags and theme variables') }}"
                                          x-tooltip="'{{ addslashes(lexicon('tooltip_Themes_SaveCss', 'Save custom head tags and theme variables')) }}'">
                                    <i class="bi bi-floppy mr-1" aria-hidden="true"></i>
                                    <span>{{ lexicon('btn_Themes_SaveCss', 'Save Theme Variables') }}</span>
                                </x-button>
                            </div>
                        @endcan
                    </x-accordion-panel>
                </form>
            </div>
        </x-slot:middle>

        <x-slot:bottom>
            {{-- MODAL: UPLOAD SKIN PACKAGE --}}
            @can('manage_themes')
                <x-modal show="modals.uploadSkin.open" maxWidth="max-w-lg">
                    <x-slot:top>
                        {{ lexicon('label_Themes_UploadNew', 'Upload Skin Package') }}
                    </x-slot:top>

                    <div class="flex flex-col gap-md">
                        <div class="bg-panel-sub border border-panel rounded-panel p-4 flex flex-col gap-xs text-xs text-muted">
                            <span class="block font-bold text-p">{{ lexicon('msg_Themes_UploadDesc', 'Your .zip file must match this exact folder structure:') }}</span>
                            <ul class="font-mono flex flex-col gap-xs pl-2 border-l-2 border-accent">
                                <li><i class="bi bi-folder2-open mr-1" aria-hidden="true"></i>css/
                                    <ul class="pl-4"><li><i class="bi bi-filetype-css mr-1" aria-hidden="true"></i>theme.css</li></ul>
                                </li>
                                <li><i class="bi bi-folder2-open mr-1" aria-hidden="true"></i>images/
                                    <ul class="pl-4">
                                        <li><i class="bi bi-folder2-open mr-1" aria-hidden="true"></i>site/
                                            <ul class="pl-4">
                                                <li><i class="bi bi-file-earmark-image mr-1" aria-hidden="true"></i>banner.png</li>
                                                <li><i class="bi bi-file-earmark-image mr-1" aria-hidden="true"></i>logo.png</li>
                                                <li><i class="bi bi-file-earmark-image mr-1" aria-hidden="true"></i>favicon.png</li>
                                            </ul>
                                        </li>
                                    </ul>
                                </li>
                            </ul>
                        </div>

                        <form action="/admin/themes/upload"
                              method="POST"
                              enctype="multipart/form-data"
                              class="bg-panel-sub border-dashed-panel rounded-panel p-panel text-center cursor-pointer transition-colors hover-border-accent"
                              aria-label="{{ lexicon('label_Themes_Package', 'Drop Skin Archive (.zip) here') }}"
                              x-tooltip="'{{ addslashes(lexicon('tooltip_Themes_DropzoneUpload', 'Drag and drop a .zip archive (max 10 MB) or click to browse')) }}'"
                              x-data="themeUploader('{{ csrf_token() }}')">
                            @csrf
                            <div class="dz-message flex flex-col items-center justify-center gap-xs">
                                <i class="bi bi-file-earmark-zip text-2xl text-accent" aria-hidden="true"></i>
                                <span class="block font-bold text-p">{{ lexicon('label_Themes_Package', 'Drop Skin Archive (.zip) here') }}</span>
                                <span class="block text-xs text-muted">{{ lexicon('msg_Themes_BrowseFiles', 'or click to browse your files') }}</span>
                            </div>
                        </form>
                    </div>
                </x-modal>
            @endcan
        </x-slot:bottom>

    </x-admin-grid-wrapper>
</div>
@endsection