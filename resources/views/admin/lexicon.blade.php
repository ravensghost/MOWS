{{-- resources/views/admin/lexicon.blade.php --}}
@extends('layouts.app')

@section('title', lexicon('admin_label_lexicon', 'Lexicon Codex Manager'))

@section('content')
<div x-data="lexiconCodexManager()"
     @ajax-success.window="refreshDOM()"
     @lexicon-clear.window="refreshDOM(true)"
     class="flex flex-col gap-lg">

    <x-grid-wrapper :book="$systemBook">
        <x-slot:top>
            <x-page-header :title="lexicon('admin_label_lexicon', 'Lexicon Codex Manager')"
                           :subtitle="lexicon('desc_admin_lexicon', 'Manage semantic tokens, prefix groups, custom conlang dialects, and AI-assisted locale packs.')"
                           icon="bi-translate">
                <x-slot:actions>
                    <x-metadata type="search"
                                name="lexicon_search"
                                id="lexicon_search"
                                :bare="true"
                                xModel="searchQuery"
                                :placeholder="lexicon('label_Lexicon_SearchPlaceholder', 'Search by token, group, or translation...')"
                                :tooltip="lexicon('tooltip_Lexicon_SearchInput', 'Instantaneously filter lexicon terms using fuzzy search')" />

                    @can('manage_lexicon')
                        <x-button type="button"
                                  variant="success"
                                  size="sm"
                                  icon="bi-upload"
                                  :tooltip="lexicon('tooltip_Lexicon_ImportLanguage', 'Import a translation JSON file')"
                                  @click="openImportLangModal()">
                            {{ lexicon('btn_Lexicon_ImportLanguage', 'Import Language') }}
                        </x-button>

                        <x-button variant="secondary"
                                  size="sm"
                                  href="{{ route('admin.lexicon_codex.template.blank') }}"
                                  target="_blank"
                                  icon="bi-file-earmark-code"
                                  :tooltip="lexicon('tooltip_Lexicon_DownloadTemplate', 'Download a blank JSON translation template')">
                            {{ lexicon('btn_Lexicon_DownloadBlankTemplate', 'Blank Template') }}
                        </x-button>

                        <x-button type="button"
                                  variant="primary"
                                  size="sm"
                                  icon="bi-plus-lg"
                                  :tooltip="lexicon('tooltip_Lexicon_AddLanguage', 'Initialise a new custom or auto-translated language')"
                                  @click="openAddLangModal('common')">
                            + {{ lexicon('btn_Lexicon_AddLanguage', 'Add Language') }}
                        </x-button>

                        <x-button variant="secondary"
                                  size="sm"
                                  x-bind:href="'/admin/lexicon-codex/export/' + activeTab"
                                  target="_blank"
                                  icon="bi-download"
                                  :tooltip="lexicon('tooltip_Lexicon_ExportLanguage', 'Export active language dictionary to JSON')">
                            {{ lexicon('btn_Lexicon_ExportLanguage', 'Export') }}
                        </x-button>
                    @endcan
                </x-slot:actions>
            </x-page-header>

            {{-- Alpine-Driven Language Tabs & Active Locale Toolbar --}}
            <x-tab type="panel">
                <div id="lexicon-tabs-header"
                     x-ref="lexiconTabsHeader"
                     class="bg-panel-header border-b-panel p-2 flex flex-row flex-wrap items-center gap-xs border-rounded-sm">
                    @foreach($locales as $code =>$label)
                        <x-button type="button"
                                  size="sm"
                                  ::variant="activeTab === '{{ $code }}' ? 'primary' : 'ghost'"
                                  :tooltip="lexicon('tooltip_Lexicon_SelectTab', 'Switch active language view') . ' (' . $label . ')'"
                                  @click="activeTab = '{{ $code }}'">
                            <span>{{ $label }}</span>
                            <span class="text-xs text-muted">({{ strtoupper($code) }})</span>
                        </x-button>
                    @endforeach
                </div>

                <div id="lexicon-active-toolbar" x-ref="lexiconActiveToolbar" x-cloak>
                    @foreach($locales as $code =>$label)
                        <div x-show="activeTab === '{{ $code }}'"
                             x-cloak
                             class="flex flex-row flex-wrap items-center justify-between gap-md pt-2">
                            <div class="flex flex-row items-center gap-sm">
                                <span class="font-bold text-h">{{ $label }}</span>
                                <x-badge type="pill" variant="info">{{ strtoupper($code) }}</x-badge>
                            </div>

                            @can('manage_lexicon')
                                <div class="flex flex-row flex-wrap items-center gap-xs">
                                    <x-button type="button"
                                              variant="ghost"
                                              size="sm"
                                              icon="bi-pencil"
                                              :tooltip="lexicon('tooltip_Lexicon_EditLanguage', 'Edit language display name')"
                                              @click="openEditLangModal('{{ $code }}', '{{ addslashes($label) }}')">
                                        {{ lexicon('btn_common_Edit', 'Edit Label') }}
                                    </x-button>

                                    @if($code === 'gb' || $code === ($activeSkin ?? ''))
                                        <x-button type="button"
                                                  variant="danger"
                                                  size="sm"
                                                  icon="bi-eraser"
                                                  :tooltip="lexicon('tooltip_Lexicon_ClearLanguage', 'Clear all custom translations for this language')"
                                                  x-data="deleteAction('/admin/lexicon-codex/language/{{ $code }}/clear', 'DELETE', '{{ addslashes($label) }}', 'This will clear all custom strings and rebuild defaults.', true)"
                                                  @click="confirmDelete()">
                                            {{ lexicon('btn_Lexicon_ClearLanguage', 'Clear Language') }}
                                        </x-button>
                                    @else
                                        <x-button type="button"
                                                  variant="danger"
                                                  size="sm"
                                                  icon="bi-trash"
                                                  :tooltip="lexicon('tooltip_Lexicon_DeleteLanguage', 'Delete this language pack entirely')"
                                                  x-data="deleteAction('/admin/lexicon-codex/language/{{ $code }}', 'DELETE', '{{ addslashes($label) }}')"
                                                  @click="confirmDelete()">
                                            {{ lexicon('btn_Lexicon_DeleteLanguage', 'Delete Language') }}
                                        </x-button>
                                    @endif
                                </div>
                            @endcan
                        </div>
                    @endforeach
                </div>
            </x-tab>
        </x-slot:top>

        <x-slot:middle>
            <div id="lexicon-table-content" x-ref="lexiconTableContent" class="flex flex-col gap-md">
                @forelse($groupedTerms as $prefixGroup =>$groups)
                    <div x-show="masterVisible('{{ addslashes($prefixGroup) }}')">
                        <x-tab type="accordion"
                               :title="$prefixGroup"
                               icon="bi-folder2-open"
                               :open="false">
                            <x-slot:top>
                                @can('manage_lexicon')
                                    <div @click.stop>
                                        <x-button type="button"
                                                  variant="danger"
                                                  size="sm"
                                                  icon="bi-trash"
                                                  :tooltip="lexicon('tooltip_Lexicon_DeleteGroup', 'Delete entire prefix group')"
                                                  x-data="deleteAction('/admin/lexicon-codex/group/{{ urlencode($prefixGroup) }}', 'DELETE', 'Group {{ addslashes($prefixGroup) }}')"
                                                  @click="confirmDelete()">
                                            {{ lexicon('btn_common_Delete', 'Delete Group') }}
                                        </x-button>
                                    </div>
                                @endcan
                            </x-slot:top>

                            <x-table type="admin"
                                     :bareBody="true"
                                     :headers="[
                                         lexicon('label_Lexicon_Subgroup', 'Group Title'),
                                         lexicon('label_Lexicon_Term', 'Semantic Token'),
                                         lexicon('label_Lexicon_Translation', 'Translation Payload'),
                                         lexicon('label_common_Actions', 'Actions')
                                     ]">
                                @foreach($groups as $groupTitle =>$groupEntries)
                                    <tbody class="text-sm text-p"
                                           x-data="{ subExpanded: false }"
                                           x-show="subgroupVisible('{{ addslashes($prefixGroup) }}', '{{ addslashes($groupTitle) }}')">

                                        {{-- Clickable Subgroup Header Row --}}
                                        <tr class="bg-panel-sub border-b-table-row hover-bg-panel-hover cursor-pointer select-none"
                                            @click="subExpanded = !subExpanded"
                                            x-tooltip="'{{ addslashes(lexicon('tooltip_common_TogglePanel', 'Click to expand or collapse panel')) }}'">
                                            <td colspan="4" class="p-3 font-bold text-h">
                                                <div class="flex flex-row items-center justify-between gap-sm">
                                                    <div class="flex flex-row items-center gap-sm">
                                                        <span class="text-xs text-muted" x-text="(subExpanded || searchQuery.trim() !== '') ? '▼' : '▶'"></span>
                                                        <span>{{ $groupTitle }}</span>
                                                        <x-badge type="pill" variant="primary">
                                                            {{ count($groupEntries) }} {{ lexicon('label_Lexicon_TermsCount', 'entries') }}
                                                        </x-badge>
                                                    </div>

                                                    @can('manage_lexicon')
                                                        <div @click.stop>
                                                            <x-button type="button"
                                                                      variant="danger"
                                                                      size="sm"
                                                                      icon="bi-trash"
                                                                      :tooltip="lexicon('tooltip_Lexicon_DeleteSubgroup', 'Delete this group and its entries')"
                                                                      x-data="deleteAction('/admin/lexicon-codex/group/{{ urlencode($prefixGroup) }}/subgroup/{{ urlencode($groupTitle) }}', 'DELETE', 'Subgroup {{ addslashes($groupTitle) }}')"
                                                                      @click="confirmDelete()" />
                                                        </div>
                                                    @endcan
                                                </div>
                                            </td>
                                        </tr>

                                        {{-- Translation Entries --}}
                                        @foreach($groupEntries as$entry)
                                            <tr x-show="(subExpanded || searchQuery.trim() !== '') && termVisible({{ $entry->id }})"
                                                x-cloak
                                                data-search-item
                                                data-term-row
                                                data-id="{{ $entry->id }}"
                                                data-master="{{ $prefixGroup }}"
                                                data-prefix-group-title="{{ $prefixGroup }}"
                                                data-sub="{{ $groupTitle }}"
                                                data-group-title="{{ $groupTitle }}"
                                                data-key="{{ $entry->semantic_token }}"
                                                data-semantic-token="{{ $entry->semantic_token }}"
                                                data-translation-payloads="{{ json_encode($entry->translation_payloads ?? []) }}"
                                                class="border-b-table-row hover-bg-panel-hover transition-colors">
                                                <td class="p-4 font-bold text-muted">↳ {{ $groupTitle }}</td>
                                                <td class="p-4 font-mono text-accent text-xs">
                                                    <div class="flex flex-row items-center gap-xs">
                                                        <span>{{ $entry->semantic_token }}</span>
                                                        <x-button type="button"
                                                                  variant="ghost"
                                                                  size="sm"
                                                                  icon="bi-clipboard"
                                                                  :tooltip="lexicon('tooltip_common_CopyToken', 'Copy token name to clipboard')"
                                                                  x-clipboard="'{{ addslashes($entry->semantic_token) }}'" />
                                                    </div>
                                                </td>
                                                <td class="p-4 text-p">
                                                    @foreach($locales as $code =>$label)
                                                        <span x-show="activeTab === '{{ $code }}'" x-cloak>
                                                            {{ $entry->translation_payloads[$code] ?? $entry->default_lexicon_string ?? '' }}
                                                        </span>
                                                    @endforeach
                                                </td>
                                                <td class="p-4 text-right">
                                                    @can('manage_lexicon')
                                                        <div class="flex flex-row flex-wrap items-center justify-end gap-xs">
                                                            <x-button type="button"
                                                                      variant="ghost"
                                                                      size="sm"
                                                                      icon="bi-pencil"
                                                                      :tooltip="lexicon('tooltip_Lexicon_EditTerm', 'Edit lexicon entry and translations')"
                                                                      @click="openTermModal('edit', {{ $entry->id }}, '{{ addslashes($entry->semantic_token) }}', '{{ addslashes($entry->prefix_group_title) }}', '{{ addslashes($entry->group_title) }}', {{ Js::from($entry->translation_payloads ?? []) }})">
                                                                {{ lexicon('btn_common_Edit', 'Edit') }}
                                                            </x-button>

                                                            <x-button type="button"
                                                                      variant="danger"
                                                                      size="sm"
                                                                      icon="bi-trash"
                                                                      :tooltip="lexicon('tooltip_Lexicon_DeleteTerm', 'Delete lexicon entry')"
                                                                      x-data="deleteAction('/admin/lexicon-codex/term/{{ $entry->id }}', 'DELETE', 'Entry {{ addslashes($entry->semantic_token) }}')"
                                                                      @click="confirmDelete()" />
                                                        </div>
                                                    @endcan
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                @endforeach
                            </x-table>
                        </x-tab>
                    </div>
                @empty
                    <x-tab type="panel">
                        <p class="text-center text-muted font-italic m-0">
                            {{ lexicon('msg_Lexicon_NoTermsFound', 'No lexicon entries discovered yet.') }}
                        </p>
                    </x-tab>
                @endforelse
            </div>
        </x-slot:middle>

        <x-slot:bottom>
            {{-- MODAL 1: EDIT LEXICON TERM --}}
            <x-modal show="modals.term.open" onClose="closeModals()" maxWidth="4xl">
                <x-slot:top>
                    <span x-text="'{{ addslashes(lexicon('label_Lexicon_EditTerm', 'Edit Lexicon Token')) }}: ' + (modals.term.semantic_token || '')"></span>
                </x-slot:top>

                <x-meta type="modal" xAction="modals.term.action" xMethod="modals.term.method">
                    <input type="hidden" name="id" :value="modals.term.id">

                    <div class="grid grid-cols-1 lg-grid-cols-3 gap-md">
                        <x-metadata type="text"
                                    name="prefix_group_title"
                                    id="term_prefix_group_title"
                                    xModel="modals.term.prefix_group_title"
                                    :label="lexicon('label_Lexicon_MasterGroup', 'Prefix Group Title')"
                                    :tooltip="lexicon('tooltip_Lexicon_MasterGroupInput', 'Primary category grouping (e.g. admin_label, label)')"
                                    readonly
                                    :required="true" />

                        <x-metadata type="text"
                                    name="group_title"
                                    id="term_group_title"
                                    xModel="modals.term.group_title"
                                    :label="lexicon('label_Lexicon_Subgroup', 'Group Title')"
                                    :tooltip="lexicon('tooltip_Lexicon_SubgroupInput', 'Secondary feature namespace (e.g. Lexicon, Navigation)')"
                                    readonly
                                    :required="true" />

                        <x-metadata type="text"
                                    name="semantic_token"
                                    id="term_semantic_token"
                                    xModel="modals.term.semantic_token"
                                    :label="lexicon('label_Lexicon_CodeKey', 'Semantic Token')"
                                    :tooltip="lexicon('tooltip_Lexicon_CodeKeyInput', 'Unique semantic token identifier')"
                                    readonly
                                    :required="true" />
                    </div>

                    <x-tab type="sub">
                        <span class="text-xs font-bold text-h uppercase text-wider border-b-panel pb-2">
                            {{ lexicon('label_Lexicon_Translations', 'Translation Payloads') }}
                        </span>

                        <div class="grid grid-cols-1 md-grid-cols-2 gap-md pt-2">
                            @foreach($locales as $code =>$label)
                                <x-metadata type="text"
                                            name="translation_payloads[{{ $code }}]"
                                            id="translation_payload_{{ $code }}"
                                            xModel="modals.term.translation_payloads['{{ $code }}']"
                                            :label="$label . ' (' . strtoupper($code) . ')'"
                                            :tooltip="lexicon('tooltip_Lexicon_TranslationInput', 'Translated string for this locale')" />
                            @endforeach
                        </div>
                    </x-tab>

                    <x-slot:footer>
                        <x-button type="button"
                                  variant="secondary"
                                  :tooltip="lexicon('tooltip_common_Cancel', 'Cancel and close modal')"
                                  @click="closeModals()">
                            {{ lexicon('btn_common_Cancel', 'Cancel') }}
                        </x-button>

                        <x-button type="submit"
                                  variant="primary"
                                  icon="bi-save"
                                  :tooltip="lexicon('tooltip_Lexicon_SaveTerm', 'Save translation term changes')">
                            {{ lexicon('btn_common_Save', 'Save Changes') }}
                        </x-button>
                    </x-slot:footer>
                </x-meta>
            </x-modal>

            {{-- MODAL 2: ADD LANGUAGE --}}
            <x-modal show="modals.addLang.open" onClose="closeModals()" maxWidth="max-w-lg">
                <x-slot:top>
                    <span>{{ lexicon('label_Lexicon_AddLanguage', 'Initialise Language Pack') }}</span>
                </x-slot:top>

                <x-meta type="modal"
                        xAction="modals.addLang.action"
                        xMethod="modals.addLang.method"
                        :loadingTitle="lexicon('msg_Lexicon_InitialisingLang', 'Initialising Language...')"
                        :loadingMsg="lexicon('msg_Lexicon_AITranslationWarning', 'This will use AI to translate all current English terms. This may take a moment.')">

                    <div class="flex flex-col gap-sm border-b-panel pb-3">
                        <label class="flex flex-row items-center gap-sm cursor-pointer">
                            <input type="radio"
                                   name="lang_type"
                                   value="common"
                                   x-model="modals.addLang.langType"
                                   class="w-4 h-4 m-0 bg-input border-input border-rounded-sm grid items-center justify-center checkbox-engine cursor-pointer">
                            <span class="text-sm font-bold text-p">{{ lexicon('label_Lexicon_CommonLanguage', 'Real Language (AI Auto-Translate)') }}</span>
                        </label>

                        <label class="flex flex-row items-center gap-sm cursor-pointer">
                            <input type="radio"
                                   name="lang_type"
                                   value="custom"
                                   x-model="modals.addLang.langType"
                                   class="w-4 h-4 m-0 bg-input border-input border-rounded-sm grid items-center justify-center checkbox-engine cursor-pointer">
                            <span class="text-sm font-bold text-p">{{ lexicon('label_Lexicon_CustomLanguage', 'Custom Conlang / Dialect (Blank Canvas)') }}</span>
                        </label>
                    </div>

                    <div x-show="modals.addLang.langType === 'common'" class="flex flex-col gap-xs">
                        <label for="common_lang_code" class="text-xs font-bold text-input-muted uppercase text-wider">
                            {{ lexicon('label_Lexicon_SelectLanguage', 'Select Target Language') }}
                        </label>
                        <select id="common_lang_code"
                                name="common_lang_code"
                                x-ref="commonLangSelect"
                                class="w-full h-input p-input bg-input text-input border-input border-rounded text-sm transition-colors hover-border-accent">
                            <option value="es">{{ lexicon('label_Lexicon_OptLangEs', 'Spanish (ES)') }}</option>
                            <option value="fr">{{ lexicon('label_Lexicon_OptLangFr', 'French (FR)') }}</option>
                            <option value="de">{{ lexicon('label_Lexicon_OptLangDe', 'German (DE)') }}</option>
                            <option value="it">{{ lexicon('label_Lexicon_OptLangIt', 'Italian (IT)') }}</option>
                        </select>
                        <span class="text-xs text-muted">{{ lexicon('msg_Lexicon_AITranslationWarning', 'This will use AI to translate all current English terms. This may take a moment.') }}</span>
                    </div>

                    <div x-show="modals.addLang.langType === 'custom'" x-cloak class="grid grid-cols-1 md-grid-cols-2 gap-md">
                        <x-metadata type="text"
                                    name="custom_lang_name"
                                    id="custom_lang_name"
                                    :label="lexicon('label_common_Name', 'Language Name')"
                                    :placeholder="lexicon('label_Lexicon_NamePlaceholder', 'e.g. Romulan, Belter Creole')"
                                    :tooltip="lexicon('tooltip_Lexicon_CustomLangName', 'Display name for the custom language')" />

                        <x-metadata type="text"
                                    name="custom_lang_code"
                                    id="custom_lang_code"
                                    :label="lexicon('label_Lexicon_ShortCode', 'Short Locale Code')"
                                    :placeholder="lexicon('label_Lexicon_CodePlaceholder', 'e.g. rom')"
                                    :tooltip="lexicon('tooltip_Lexicon_CustomLangCode', 'Short unique locale code')" />
                    </div>

                    <x-slot:footer>
                        <x-button type="button"
                                  variant="secondary"
                                  :tooltip="lexicon('tooltip_common_Cancel', 'Cancel and close modal')"
                                  @click="closeModals()">
                            {{ lexicon('btn_common_Cancel', 'Cancel') }}
                        </x-button>

                        <x-button type="submit"
                                  variant="primary"
                                  icon="bi-translate"
                                  :tooltip="lexicon('tooltip_Lexicon_InitLang', 'Initialise language pack')">
                            {{ lexicon('btn_Lexicon_InitializeLanguage', 'Initialise Language') }}
                        </x-button>
                    </x-slot:footer>
                </x-meta>
            </x-modal>

            {{-- MODAL 3: IMPORT LANGUAGE (DROPZONE) --}}
            <x-modal show="modals.importLang.open" onClose="closeModals()" maxWidth="max-w-lg">
                <x-slot:top>
                    <span>{{ lexicon('label_Lexicon_ImportLanguage', 'Import Language Pack') }}</span>
                </x-slot:top>

                <div class="flex flex-col gap-md">
                    <x-metadata type="text"
                                name="import_lang_code"
                                id="import_lang_code"
                                xModel="modals.importLang.langCode"
                                :label="lexicon('label_Lexicon_TargetLangCode', 'Target Language Code (Optional)')"
                                :placeholder="lexicon('label_Lexicon_ImportCodePlaceholder', 'e.g. klg')"
                                :tooltip="lexicon('tooltip_Lexicon_ImportLangCode', 'Optional override for imported language code')" />

                    <span class="text-xs text-muted">
                        {{ lexicon('msg_Lexicon_ImportCodeHint', 'Leave blank if your JSON file was exported from this system (it contains the language code automatically).') }}
                    </span>

                    <div class="flex flex-col gap-xs">
                        <span class="text-xs font-bold text-input-muted uppercase text-wider">
                            {{ lexicon('label_Lexicon_UploadJson', 'Upload JSON File') }} <span class="text-required">*</span>
                        </span>

                        <div x-ref="lexiconDropzone"
                             class="dropzone bg-panel-sub border-dashed-panel border-rounded p-6 flex flex-col items-center justify-center gap-sm cursor-pointer transition-colors hover-border-accent text-center"
                             x-tooltip="'{{ addslashes(lexicon('tooltip_Lexicon_DropzoneUpload', 'Drag and drop a .json lexicon file or click to browse')) }}'">
                            <div class="dz-message flex flex-col items-center justify-center gap-xs">
                                <i class="bi bi-file-earmark-arrow-up text-2xl text-accent"></i>
                                <span class="font-bold text-sm text-h">{{ lexicon('label_Lexicon_DropJsonHere', 'Drop Lexicon JSON (.json) here') }}</span>
                                <span class="text-xs text-muted">{{ lexicon('msg_Themes_BrowseFiles', 'or click to browse your files') }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <x-slot:bottom>
                    <x-button type="button"
                              variant="secondary"
                              @click="closeModals()">
                        {{ lexicon('btn_common_Cancel', 'Cancel') }}
                    </x-button>

                    <x-button type="button"
                              variant="primary"
                              icon="bi-upload"
                              :tooltip="lexicon('tooltip_Lexicon_UploadImport', 'Upload and merge lexicon file')"
                              @click="submitImport()">
                        {{ lexicon('btn_Lexicon_ImportFile', 'Import File') }}
                    </x-button>
                </x-slot:bottom>
            </x-modal>

            {{-- MODAL 4: EDIT LANGUAGE LABEL --}}
            <x-modal show="modals.editLang.open" onClose="closeModals()" maxWidth="max-w-lg">
                <x-slot:top>
                    <span>{{ lexicon('label_Lexicon_EditLanguageSettings', 'Edit Language Label') }}</span>
                </x-slot:top>

                <x-meta type="modal" xAction="modals.editLang.action" xMethod="modals.editLang.method">
                    <input type="hidden" name="lang_code" :value="modals.editLang.code">

                    <x-metadata type="text"
                                name="lang_label"
                                id="edit_lang_label"
                                xModel="modals.editLang.label"
                                :label="lexicon('label_Lexicon_DisplayLabel', 'Display Label')"
                                :tooltip="lexicon('tooltip_Lexicon_EditLangLabel', 'Update language display name')"
                                :required="true" />

                    <x-slot:footer>
                        <x-button type="button"
                                  variant="secondary"
                                  @click="closeModals()">
                            {{ lexicon('btn_common_Cancel', 'Cancel') }}
                        </x-button>

                        <x-button type="submit"
                                  variant="primary"
                                  icon="bi-save"
                                  :tooltip="lexicon('tooltip_Lexicon_UpdateLang', 'Save language settings')">
                            {{ lexicon('btn_common_Update', 'Update') }}
                        </x-button>
                    </x-slot:footer>
                </x-meta>
            </x-modal>
        </x-slot:bottom>
    </x-grid-wrapper>
</div>
@endsection