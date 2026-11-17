{{-- resources/views/admin/themes.blade.php --}}
@extends('layouts.app')

@section('title', lexicon('admin_label_theme', 'Thematic Bindings & Skin Engine'))

@section('content')
<div x-data="thematicBindingManager()"
     @ajax-success.window="refreshDOM()"
     x-ref="thematicBindingsWrapper"
     class="flex flex-col gap-lg">

    <x-grid-wrapper :book="$systemBook">
        <x-slot:top>
            <x-page-header :title="lexicon('admin_label_theme', 'Thematic Bindings & Skin Engine')"
                           :subtitle="lexicon('desc_admin_theme', 'Manage installed thematic skin packages, custom head font/CDN injections, and live CSS design tokens.')"
                           icon="bi-palette2">
                <x-slot:actions>
                    @can('manage_themes')
                        <x-button type="button"
                                  variant="primary"
                                  size="sm"
                                  icon="bi-cloud-arrow-up"
                                  :tooltip="lexicon('tooltip_Themes_UploadSkin', 'Upload a new .zip skin package')"
                                  @click="openUploadModal('{{ route('admin.thematic_bindings.upload') }}')">
                            + {{ lexicon('btn_Themes_UploadSkin', 'Upload Skin') }}
                        </x-button>
                    @endcan
                </x-slot:actions>
            </x-page-header>

            @if(session('status'))
                <div x-init="window.Swal && window.Swal.toast('success', '{{ addslashes(session('status')) }}')"></div>
            @endif
        </x-slot:top>

        <x-slot:middle>
            <div class="flex flex-col gap-lg">
                {{-- ACCORDION 1: Installed Skins Manager --}}
                <x-tab type="accordion"
                       :title="lexicon('label_Themes_InstalledSkins', 'Installed Thematic Binding Packages')"
                       icon="bi-box-seam"
                       :open="true">
                    <div id="skins-table-wrapper" x-ref="skinsTableWrapper">
                        <x-table type="admin"
                                 :headers="[
                                     lexicon('label_Themes_SkinName', 'Binding Title'),
                                     lexicon('label_Themes_DirectoryVersion', 'Directory / Version'),
                                     lexicon('label_common_Status', 'Status'),
                                     lexicon('label_common_Actions', 'Actions')
                                 ]"
                                 :emptyText="lexicon('msg_Themes_NoSkins', 'No custom thematic bindings installed.')">
                            @foreach($skins ?? [] as $skin)
                                <tr class="border-b-table-row hover-bg-panel-hover transition-colors">
                                    <td class="p-4 font-bold text-h">{{ $skin->binding_title }}</td>
                                    <td class="p-4 font-mono text-xs text-muted">
                                        <div class="flex flex-row items-center gap-xs">
                                            <span>/skins/{{ $skin->directory_name }}</span>
                                            <x-button type="button"
                                                      variant="ghost"
                                                      size="sm"
                                                      icon="bi-clipboard"
                                                      :tooltip="lexicon('tooltip_Themes_CopyPath', 'Copy skin directory path')"
                                                      x-clipboard="'/skins/{{ addslashes($skin->directory_name) }}'" />
                                        </div>
                                        <span class="block text-xs text-muted">v{{ $skin->package_version ?? '1.0' }}</span>
                                    </td>
                                    <td class="p-4">
                                        @if($skin->is_global_default)
                                            <x-badge type="pill"
                                                     variant="success"
                                                     icon="bi-check-circle"
                                                     :tooltip="lexicon('tooltip_Themes_GlobalDefault', 'Active global default skin')">
                                                {{ lexicon('label_Themes_StatusActive', 'Global Default') }}
                                            </x-badge>
                                        @else
                                            <x-badge type="pill"
                                                     variant="muted"
                                                     :tooltip="lexicon('tooltip_Themes_StatusInactive', 'Installed but not set as global default')">
                                                {{ lexicon('msg_Themes_StatusInactive', 'Inactive') }}
                                            </x-badge>
                                        @endif
                                    </td>
                                    <td class="p-4 text-right">
                                        <div class="flex flex-row flex-wrap justify-end items-center gap-xs">
                                            @can('manage_themes')
                                                @if(!$skin->is_global_default)
                                                    <x-meta type="default"
                                                            :action="route('admin.thematic_bindings.activate', $skin->id)"
                                                            method="POST"
                                                            :loadingTitle="lexicon('msg_Themes_ActivatingSkin', 'Activating Skin...')">
                                                        <x-button type="submit"
                                                                  variant="primary"
                                                                  size="sm"
                                                                  icon="bi-lightning-charge"
                                                                  :tooltip="lexicon('tooltip_Themes_ActivateSkin', 'Set this skin as the global site default')">
                                                            {{ lexicon('btn_Themes_Activate', 'Activate') }}
                                                        </x-button>
                                                    </x-meta>
                                                @endif
                                            @endcan

                                            <x-button variant="secondary"
                                                      size="sm"
                                                      href="{{ route('admin.thematic_bindings.export', $skin->id) }}"
                                                      icon="bi-download"
                                                      :tooltip="lexicon('tooltip_Themes_ExportSkin', 'Download this skin package as a .zip archive')">
                                                {{ lexicon('btn_Themes_Export', 'Export') }}
                                            </x-button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </x-table>
                    </div>
                </x-tab>

                {{-- GLOBAL THEME VARIABLE & CUSTOM HEAD FORM --}}
                <x-meta type="default"
                        :action="route('admin.thematic_bindings.css')"
                        method="POST"
                        :loadingTitle="lexicon('msg_Themes_SavingCss', 'Saving Theme Variables...')">

                    {{-- ACCORDION 2: Custom Head Injection --}}
                    <x-tab type="accordion"
                           :title="lexicon('label_Themes_CustomHead', 'Custom <head> Injection (Fonts, CDNs, Meta)')"
                           icon="bi-code-square"
                           :open="false">
                        <x-slot:top>
                            <span class="text-xs text-muted">{{ lexicon('msg_Themes_CustomHeadSub', 'Manage external scripts, fonts, and meta tags.') }}</span>
                        </x-slot:top>

                        <x-metadata type="textarea"
                                    name="custom_head_injection"
                                    class="font-mono"
                                    :value="old('custom_head_injection', ($skins ?? collect())->where('is_global_default', 1)->first()->custom_head_injection ?? '')"
                                    :placeholder="lexicon('label_Themes_CustomHeadPlaceholder', '<link href=\'https://fonts.googleapis.com/css2?family=Roboto&display=swap\' rel=\'stylesheet\'>')"
                                    :tooltip="lexicon('tooltip_Themes_CustomHeadInput', 'Enter custom HTML link or meta tags for the active skin')"
                                    x-init="window.Autosize && window.Autosize.init($el)" />

                        <span class="block text-xs text-muted border-l-accent pl-2">
                            {{ lexicon('msg_Themes_CustomHeadHint', 'These tags will be injected directly into the master layout for the active skin. Do not include your core CSS files here.') }}
                        </span>
                    </x-tab>

                    {{-- ACCORDION 3: Visual Theme Customiser --}}
                    <x-tab type="accordion"
                           :title="lexicon('label_Themes_Editor', 'Visual Theme Token Customiser')"
                           icon="bi-sliders"
                           :open="true">
                        <x-slot:top>
                            <div @click.stop class="w-full md-w-64">
                                <x-metadata type="search"
                                            name="theme_var_search"
                                            id="theme-var-search"
                                            :bare="true"
                                            xModel="varSearchQuery"
                                            :placeholder="lexicon('label_Themes_SearchVarsPlaceholder', 'Search by token, label, or usage...')"
                                            :tooltip="lexicon('tooltip_Themes_SearchVars', 'Instantaneously filter theme variables using fuzzy search')" />
                            </div>
                        </x-slot:top>

                        <div class="flex flex-col gap-lg">
                            @foreach($groupedVars ?? [] as $groupName => $vars)
                                <div class="flex flex-col gap-md">
                                    <span class="block font-bold text-accent pb-2 border-b-panel uppercase text-wider text-sm">
                                        {{ $groupName }}
                                    </span>

                                    <div class="grid grid-cols-1 lg-grid-cols-3 gap-md w-full">
                                        @foreach($vars as $key => $varData)
                                            <div data-search-item
                                                 data-theme-var-item
                                                 data-id="{{ $key }}"
                                                 data-key="{{ $key }}"
                                                 data-label="{{ $varData['label'] ?? $key }}"
                                                 data-usage="{{ $varData['shortUsageText'] ?? '' }}"
                                                 x-show="matchesVarSearch('{{ addslashes($varData['label'] ?? $key) }}', '{{ addslashes($key) }}', '{{ addslashes($varData['shortUsageText'] ?? '') }}')">
                                                <x-var :varKey="$key"
                                                       :varData="$varData"
                                                       :structuralOptions="$structuralOptions ?? []" />
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        @can('manage_themes')
                            <div class="border-t-actions pt-4 flex flex-row justify-end">
                                <x-button type="submit"
                                          variant="primary"
                                          size="lg"
                                          icon="bi-floppy"
                                          :tooltip="lexicon('tooltip_Themes_SaveCss', 'Save custom head tags and theme variables')">
                                    {{ lexicon('btn_Themes_SaveCss', 'Save Theme Variables') }}
                                </x-button>
                            </div>
                        @endcan
                    </x-tab>
                </x-meta>
            </div>
        </x-slot:middle>

        <x-slot:bottom>
            {{-- MODAL: UPLOAD SKIN PACKAGE --}}
            @can('manage_themes')
                <x-modal show="modals.uploadSkin.open" onClose="closeUploadModal()" maxWidth="max-w-lg">
                    <x-slot:top>
                        <span>{{ lexicon('label_Themes_UploadNew', 'Upload Skin Package (.zip)') }}</span>
                    </x-slot:top>

                    <div class="flex flex-col gap-md" @upload-completed.window="refreshDOM()">
                        <x-tab type="sub">
                            <span class="text-xs font-bold text-h">{{ lexicon('msg_Themes_UploadDesc', 'Your .zip file must match this exact folder structure:') }}</span>
                            <ul class="font-mono text-xs text-muted flex flex-col gap-xs pl-4 m-0">
                                <li><i class="bi bi-folder2-open text-accent"></i> css/theme.css</li>
                                <li><i class="bi bi-folder2-open text-accent"></i> images/site/ (banner.png, logo.png, favicon.png)</li>
                            </ul>
                        </x-tab>

                        <x-dropzone :url="route('admin.thematic_bindings.upload')"
                                    id="skin-package-dropzone"
                                    paramName="skin_zip"
                                    :maxFiles="1"
                                    :maxFilesize="10"
                                    acceptedFiles=".zip,application/zip,application/x-zip-compressed"
                                    :title="lexicon('label_Themes_Package', 'Drop Skin Archive (.zip) here')"
                                    :tooltip="lexicon('tooltip_Themes_DropzoneUpload', 'Drag and drop a .zip archive (max 10 MB) or click to browse')" />
                    </div>

                    <x-slot:bottom>
                        <x-button type="button"
                                  variant="secondary"
                                  @click="closeUploadModal()">
                            {{ lexicon('btn_common_Close', 'Close') }}
                        </x-button>
                    </x-slot:bottom>
                </x-modal>
            @endcan
        </x-slot:bottom>
    </x-grid-wrapper>
</div>
@endsection