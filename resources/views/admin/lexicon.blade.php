@extends('layouts.app')

@section('title', lexicon('admin_label_lexicon', 'Lexicon Manager'))

@section('content')
<div x-data="lexiconManager" @ajax-success.window="refreshDOM()" @lexicon-clear.window="refreshDOM(true)">
    <x-admin-grid-wrapper :page="$systemPage">

        <x-slot:top>
            <x-page-header :title="lexicon('admin_label_lexicon', 'Lexicon Manager')">
                <x-slot:actions>
                    @can('manage_lexicon')
                        <x-button type="button"
                                  variant="success"
                                  class="p-btn-sm text-sm"
                                  @click="modals.importLang.open = true"
                                  aria-label="{{ lexicon('tooltip_Lexicon_ImportLanguage', 'Import a translation JSON file') }}"
                                  x-tooltip="'{{ addslashes(lexicon('tooltip_Lexicon_ImportLanguage', 'Import a translation JSON file')) }}'">
                            <i class="bi bi-upload"></i> {{ lexicon('btn_Lexicon_ImportLanguage', 'Import Language') }}
                        </x-button>

                        <x-button variant="secondary"
                                  href="/admin/lexicon/template/blank"
                                  class="p-btn-sm text-sm"
                                  target="_blank"
                                  aria-label="{{ lexicon('tooltip_Lexicon_DownloadTemplate', 'Download a blank JSON translation template') }}"
                                  x-tooltip="'{{ addslashes(lexicon('tooltip_Lexicon_DownloadTemplate', 'Download a blank JSON translation template')) }}'">
                            <i class="bi bi-file-earmark-code"></i> {{ lexicon('btn_Lexicon_DownloadBlankTemplate', 'Download Blank Template') }}
                        </x-button>

                        <x-button type="button"
                                  variant="primary"
                                  class="p-btn-sm text-sm"
                                  @click="modals.addLang.open = true"
                                  aria-label="{{ lexicon('tooltip_Lexicon_AddLanguage', 'Initialise a new custom or auto-translated language') }}"
                                  x-tooltip="'{{ addslashes(lexicon('tooltip_Lexicon_AddLanguage', 'Initialise a new custom or auto-translated language')) }}'">
                            <i class="bi bi-translate"></i> {{ lexicon('btn_Lexicon_AddLanguage', 'Add Language') }}
                        </x-button>

                        <x-button variant="secondary"
                                  x-bind:href="'/admin/lexicon/export/' + activeTab"
                                  class="p-btn-sm text-sm"
                                  target="_blank"
                                  aria-label="{{ lexicon('tooltip_Lexicon_ExportLanguage', 'Export active language dictionary to JSON') }}"
                                  x-tooltip="'{{ addslashes(lexicon('tooltip_Lexicon_ExportLanguage', 'Export active language dictionary to JSON')) }}'">
                            <i class="bi bi-download"></i> {{ lexicon('btn_Lexicon_ExportLanguage', 'Export') }}
                        </x-button>
                    @endcan
                </x-slot:actions>
            </x-page-header>

            <!-- Alpine-Driven Language Tabs Bar -->
            <div id="lexicon-tabs-header" class="bg-panel-header rounded-t flex flex-row flex-wrap items-center p-2 gap-sm" x-ref="lexiconTabsHeader">
                @foreach($locales as $code =>$label)
                    <button type="button"
                            class="font-bold text-sm bg-transparent border-none hover-bg-panel-hover py-1 px-2 cursor-pointer transition-btn rounded-input"
                            x-bind:class="activeTab === '{{ $code }}' ? 'text-p border-b-accent bg-panel-header' : 'text-muted border-b-2-transparent'"
                            @click="activeTab = '{{ $code }}'"
                            aria-label="{{ lexicon('tooltip_Lexicon_SelectTab', 'Switch active language view') }} {{ $label }}"
                            x-tooltip="'{{ addslashes(lexicon('tooltip_Lexicon_SelectTab', 'Switch active language view')) }}'">
                        <span>{{ $label }}</span>
                        <span class="text-xs opacity-50">({{ strtoupper($code) }})</span>
                    </button>
                @endforeach
            </div>

            <!-- Dynamic Active Language Toolbar -->
            <div id="lexicon-active-toolbar" class="bg-panel border border-panel rounded-panel shadow-panel flex flex-col gap-md overflow-hidden rounded-t-none border-t-0 mb-lg" x-ref="lexiconActiveToolbar" x-cloak>
                @foreach($locales as $code =>$label)
                    <div x-show="activeTab === '{{ $code }}'" x-cloak class="bg-panel-header text-h border-b border-panel p-panel-header font-bold border-b-0 flex flex-row flex-wrap gap-md items-center justify-between p-3">
                        <div class="flex flex-row items-center gap-sm">
                            <span class="font-bold text-h">{{ $label }}</span>
                            <x-badge type="info">{{ strtoupper($code) }}</x-badge>
                        </div>
                        @can('manage_lexicon')
                            <div class="flex flex-row flex-wrap gap-sm items-center">
                                <x-button type="button"
                                          variant="text"
                                          class="text-warning hover-warning text-sm px-2"
                                          @click="openEditLangModal('{{ $code }}', '{{ addslashes($label) }}')"
                                          aria-label="{{ lexicon('tooltip_Lexicon_EditLanguage', 'Edit language display name') }}"
                                          x-tooltip="'{{ addslashes(lexicon('tooltip_Lexicon_EditLanguage', 'Edit language display name')) }}'">
                                    <i class="bi bi-pencil"></i> {{ lexicon('btn_common_Edit', 'Edit') }}
                                </x-button>

                                @if($code === 'gb' || $code ===$activeSkin)
                                    <x-button type="button"
                                              variant="text"
                                              class="text-danger hover-danger text-sm px-2"
                                              x-data="deleteAction(`/admin/lexicon/language/{{ $code }}/clear`, 'DELETE', '{{ addslashes($label) }}', true)"
                                              @click="confirmDelete"
                                              aria-label="{{ lexicon('tooltip_Lexicon_ClearLanguage', 'Clear all custom translations for this language') }}"
                                              x-tooltip="'{{ addslashes(lexicon('tooltip_Lexicon_ClearLanguage', 'Clear all custom translations for this language')) }}'">
                                        <i class="bi bi-eraser"></i> {{ lexicon('btn_Lexicon_ClearLanguage', 'Clear Language') }}
                                    </x-button>
                                @else
                                    <x-button type="button"
                                              variant="text"
                                              class="text-danger hover-danger text-sm px-2"
                                              x-data="deleteAction(`/admin/lexicon/language/{{ $code }}`, 'DELETE', '{{ addslashes($label) }}')"
                                              @click="confirmDelete"
                                              aria-label="{{ lexicon('tooltip_Lexicon_DeleteLanguage', 'Delete this language pack entirely') }}"
                                              x-tooltip="'{{ addslashes(lexicon('tooltip_Lexicon_DeleteLanguage', 'Delete this language pack entirely')) }}'">
                                        <i class="bi bi-trash"></i> {{ lexicon('btn_Lexicon_DeleteLanguage', 'Delete Language') }}
                                    </x-button>
                                @endif
                            </div>
                        @endcan
                    </div>
                @endforeach
            </div>

            <!-- Fuse.js Instant Search Filter Bar -->
            <div class="bg-panel border border-panel rounded-panel shadow-panel p-4 mb-lg">
                <x-form-input name="lexicon_search"
                              id="lexicon_search"
                              type="text"
                              :label="lexicon('label_Lexicon_SearchTerms', 'Filter Lexicon Terms')"
                              :placeholder="lexicon('label_Lexicon_SearchPlaceholder', 'Search by code key, master group, subgroup, or translation...')"
                              :tooltip="lexicon('tooltip_Lexicon_SearchInput', 'Instantaneously filter lexicon terms using fuzzy search')"
                              xModel="searchQuery" />
            </div>
        </x-slot:top>

        <x-slot:middle>
            <div id="lexicon-table-content" x-ref="lexiconTableContent" class="flex flex-col gap-md">

                @forelse($groupedTerms as $master =>$subgroups)
                    <div x-show="masterVisible('{{ addslashes($master) }}')" class="mb-2">
                        <x-accordion-panel :title="$master" :expanded="false">
                            <x-slot:top>
                                @can('manage_lexicon')
                                    <x-button type="button"
                                              variant="text"
                                              class="text-danger hover-danger flex-shrink-0 text-sm"
                                              x-data="deleteAction(`/admin/lexicon/group/{{ $master }}`, 'DELETE', 'Group {{ addslashes($master) }}')"
                                              @click.stop="confirmDelete"
                                              aria-label="{{ lexicon('tooltip_Lexicon_DeleteGroup', 'Delete entire master group') }}"
                                              x-tooltip="'{{ addslashes(lexicon('tooltip_Lexicon_DeleteGroup', 'Delete entire master group')) }}'">
                                        <i class="bi bi-trash"></i> {{ lexicon('btn_common_Delete', 'Delete') }}
                                    </x-button>
                                @endcan
                            </x-slot:top>

                            <x-table class="border-t-0 rounded-none shadow-none">
                                <x-slot:headers>
                                    <th class="p-4 text-left w-48">{{ lexicon('label_Lexicon_Subgroup', 'Subgroup') }}</th>
                                    <th class="p-4 text-left">{{ lexicon('label_Lexicon_Term', 'Term (Code Key)') }}</th>
                                    <th class="p-4 text-left">{{ lexicon('label_Lexicon_Translation', 'Translation') }}</th>
                                    <th class="p-4 text-right w-48">{{ lexicon('label_common_Actions', 'Actions') }}</th>
                                </x-slot:headers>

                                @foreach($subgroups as $sub =>$subTerms)
                                    <tbody x-data="{ subExpanded: false }" x-show="subgroupVisible('{{ addslashes($master) }}', '{{ addslashes($sub) }}')">

                                        <!-- Clickable Subgroup Header Row -->
                                        <tr class="bg-panel-sub border-b-table-row hover-bg-panel-hover cursor-pointer"
                                            @click="subExpanded = !subExpanded"
                                            aria-label="{{ lexicon('tooltip_common_TogglePanel', 'Click to expand or collapse panel') }}"
                                            x-tooltip="'{{ addslashes(lexicon('tooltip_common_TogglePanel', 'Click to expand or collapse panel')) }}'">
                                            <td colspan="4" class="p-2 pl-4 font-bold text-h">
                                                <div class="flex flex-row items-center justify-between">
                                                    <div class="flex flex-row items-center gap-sm">
                                                        <span class="text-xs text-muted" x-text="(subExpanded || searchQuery.trim() !== '') ? '▼' : '▶'"></span>
                                                        <span>{{ $sub }}</span>
                                                        <x-badge type="primary">{{ count($subTerms) }} {{ lexicon('label_Lexicon_TermsCount', 'terms') }}</x-badge>
                                                    </div>
                                                    @can('manage_lexicon')
                                                        <x-button type="button"
                                                                  variant="text"
                                                                  class="text-danger hover-danger text-xs px-2"
                                                                  x-data="deleteAction(`/admin/lexicon/group/{{ $master }}/{{ $sub }}`, 'DELETE', 'Subgroup {{ addslashes($sub) }}')"
                                                                  @click.stop="confirmDelete"
                                                                  aria-label="{{ lexicon('tooltip_Lexicon_DeleteSubgroup', 'Delete this subgroup and its terms') }}"
                                                                  x-tooltip="'{{ addslashes(lexicon('tooltip_Lexicon_DeleteSubgroup', 'Delete this subgroup and its terms')) }}'">
                                                            <i class="bi bi-trash"></i>
                                                        </x-button>
                                                    @endcan
                                                </div>
                                            </td>
                                        </tr>

                                        <!-- Translation Terms -->
                                        @foreach($subTerms as $term)
                                            <tr x-show="(subExpanded || searchQuery.trim() !== '') && termVisible({{ $term->id }})"
                                                data-term-row
                                                data-id="{{ $term->id }}"
                                                data-master="{{ $master }}"
                                                data-sub="{{ $sub }}"
                                                data-key="{{ $term->code_key }}"
                                                data-translations="{{ json_encode($term->translations ?? []) }}"
                                                class="border-b-table-row hover-bg-table-row"
                                                x-cloak>
                                                <td class="p-4 font-bold text-muted pl-8">↳ {{ $sub }}</td>
                                                <td class="p-4 font-mono text-accent text-sm">
                                                    <div class="flex flex-row items-center gap-xs">
                                                        <span>{{ $term->code_key }}</span>
                                                        <x-button type="button"
                                                                  variant="text"
                                                                  class="text-xs text-muted hover-text-accent"
                                                                  x-clipboard="'{{ addslashes($term->code_key) }}'"
                                                                  aria-label="{{ lexicon('tooltip_common_CopyToken', 'Copy token name to clipboard') }}"
                                                                  x-tooltip="'{{ addslashes(lexicon('tooltip_common_CopyToken', 'Copy token name to clipboard')) }}'">
                                                            <i class="bi bi-clipboard"></i>
                                                        </x-button>
                                                    </div>
                                                </td>
                                                <td class="p-4 text-p">
                                                    @foreach($locales as $code =>$label)
                                                        <span x-show="activeTab === '{{ $code }}'" x-cloak>
                                                            {{ $term->translations[$code] ?? '' }}
                                                        </span>
                                                    @endforeach
                                                </td>
                                                <td class="p-4 text-right flex flex-row items-center justify-end gap-sm text-sm">
                                                    @can('manage_lexicon')
                                                        <x-button type="button"
                                                                  variant="text"
                                                                  class="text-warning hover-warning"
                                                                  @click="openTermModal('edit', {{ $term->id }}, '{{ addslashes($term->code_key) }}', '{{ addslashes($term->master_group) }}', '{{ addslashes($term->subgroup) }}', {{ json_encode($term->translations ?? []) }})"
                                                                  aria-label="{{ lexicon('tooltip_Lexicon_EditTerm', 'Edit lexicon term and translations') }}"
                                                                  x-tooltip="'{{ addslashes(lexicon('tooltip_Lexicon_EditTerm', 'Edit lexicon term and translations')) }}'">
                                                            <i class="bi bi-pencil"></i> {{ lexicon('btn_common_Edit', 'Edit') }}
                                                        </x-button>

                                                        <x-button type="button"
                                                                  variant="text"
                                                                  class="text-danger hover-danger flex-shrink-0"
                                                                  x-data="deleteAction(`/admin/lexicon/term/{{ $term->id }}`, 'DELETE', 'Term {{ addslashes($term->code_key) }}')"
                                                                  @click="confirmDelete"
                                                                  aria-label="{{ lexicon('tooltip_Lexicon_DeleteTerm', 'Delete lexicon term') }}"
                                                                  x-tooltip="'{{ addslashes(lexicon('tooltip_Lexicon_DeleteTerm', 'Delete lexicon term')) }}'">
                                                            <i class="bi bi-trash"></i> {{ lexicon('btn_common_Delete', 'Delete') }}
                                                        </x-button>
                                                    @endcan
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                @endforeach
                            </x-table>
                        </x-accordion-panel>
                    </div>
                @empty
                    <div class="bg-panel border border-panel rounded-panel shadow-panel flex flex-col gap-md overflow-hidden p-6 text-center text-muted italic">
                        <span>{{ lexicon('msg_Lexicon_NoTermsFound', 'No lexicon terms discovered yet.') }}</span>
                    </div>
                @endforelse
            </div>
        </x-slot:middle>

        <x-slot:bottom>

            <!-- ============================================== -->
            <!-- MODAL: TERM EDIT                               -->
            <!-- ============================================== -->
            <x-modal show="modals.term.open" maxWidth="max-w-4xl">
                <x-slot:top>
                    <span>{{ lexicon('label_Lexicon_EditTerm', 'Edit Term') }}</span>
                </x-slot:top>

                <form :action="modals.term.action" method="POST" x-data="ajaxForm" @submit.prevent="submit" class="flex flex-col gap-md"
                      data-loading-title="{{ lexicon('msg_common_Saving', 'Saving...') }}">
                    @csrf
                    <input type="hidden" name="_method" :value="modals.term.method">
                    <input type="hidden" name="id" :value="modals.term.id">

                    <div class="grid grid-cols-1 gap-sm w-full lg-grid-cols-3">
                        <x-form-input name="master_group"
                                      id="term_master_group"
                                      type="text"
                                      :label="lexicon('label_Lexicon_MasterGroup', 'Master Group')"
                                      :tooltip="lexicon('tooltip_Lexicon_MasterGroupInput', 'Primary category grouping (e.g. admin_label, label)')"
                                      xModel="modals.term.master_group"
                                      readonly
                                      :required="true" />

                        <x-form-input name="subgroup"
                                      id="term_subgroup"
                                      type="text"
                                      :label="lexicon('label_Lexicon_Subgroup', 'Subgroup')"
                                      :tooltip="lexicon('tooltip_Lexicon_SubgroupInput', 'Secondary feature namespace (e.g. Lexicon, Navigation)')"
                                      xModel="modals.term.subgroup"
                                      readonly
                                      :required="true" />

                        <x-form-input name="code_key"
                                      id="term_code_key"
                                      type="text"
                                      :label="lexicon('label_Lexicon_CodeKey', 'Exact Code Key')"
                                      :tooltip="lexicon('tooltip_Lexicon_CodeKeyInput', 'Unique 3-part code key identifier')"
                                      xModel="modals.term.code_key"
                                      readonly
                                      :required="true" />
                    </div>

                    <div class="bg-panel border border-panel rounded-panel shadow-panel flex flex-col gap-md overflow-hidden mt-4">
                        <div class="bg-panel-header text-h border-b border-panel p-panel-header font-bold text-sm p-3">
                            <span>{{ lexicon('label_Lexicon_Translations', 'Translations') }}</span>
                        </div>
                        <div class="p-4 grid grid-cols-1 gap-sm w-full md-grid-cols-2 gap-md">
                            @foreach($locales as $code =>$label)
                                <x-form-input name="translations[{{ $code }}]"
                                              id="translation_{{ $code }}"
                                              type="text"
                                              :label="$label . ' (' . strtoupper($code) . ')'"
                                              :tooltip="lexicon('tooltip_Lexicon_TranslationInput', 'Translated string for this locale')"
                                              xModel="modals.term.translations['{{ $code }}']" />
                            @endforeach
                        </div>
                    </div>

                    <div class="border-t-actions pt-6 flex flex-row justify-end gap-sm mt-4">
                        <x-button type="button"
                                  variant="secondary"
                                  class="p-btn-sm text-sm"
                                  @click="modals.term.open = false"
                                  aria-label="{{ lexicon('tooltip_common_Cancel', 'Cancel and close modal') }}"
                                  x-tooltip="'{{ addslashes(lexicon('tooltip_common_Cancel', 'Cancel and close modal')) }}'">
                            {{ lexicon('btn_common_Cancel', 'Cancel') }}
                        </x-button>

                        <x-button type="submit"
                                  variant="primary"
                                  class="p-btn-sm text-sm"
                                  aria-label="{{ lexicon('tooltip_Lexicon_SaveTerm', 'Save translation term changes') }}"
                                  x-tooltip="'{{ addslashes(lexicon('tooltip_Lexicon_SaveTerm', 'Save translation term changes')) }}'">
                            {{ lexicon('btn_common_Save', 'Save Changes') }}
                        </x-button>
                    </div>
                </form>
            </x-modal>

            <!-- ============================================== -->
            <!-- MODAL: ADD LANGUAGE                            -->
            <!-- ============================================== -->
            <x-modal show="modals.addLang.open" maxWidth="max-w-lg">
                <x-slot:top>
                    <span>{{ lexicon('label_Lexicon_AddLanguage', 'Add Language') }}</span>
                </x-slot:top>

                <form method="POST" action="/admin/lexicon/language" x-data="ajaxForm" @submit.prevent="submit" class="flex flex-col gap-md"
                      data-loading-title="{{ lexicon('msg_Lexicon_InitialisingLang', 'Initialising Language...') }}"
                      data-loading-msg="{{ lexicon('msg_Lexicon_AITranslationWarning', 'This will use AI to translate all current English terms. This may take a moment.') }}">
                    @csrf

                    <div class="flex flex-col gap-sm border-b pb-4">
                        <label class="flex flex-row flex-wrap items-center gap-xs cursor-pointer">
                            <input type="radio" name="lang_type" value="common" x-model="modals.addLang.langType" class="appearance-none bg-input m-0 w-4 h-4 border-input rounded-input-sm grid place-content-center checkbox-engine">
                            <span class="text-sm font-bold">{{ lexicon('label_Lexicon_CommonLanguage', 'Real Language (AI Auto-Translate)') }}</span>
                        </label>
                        <label class="flex flex-row flex-wrap items-center gap-xs cursor-pointer">
                            <input type="radio" name="lang_type" value="custom" x-model="modals.addLang.langType" class="appearance-none bg-input m-0 w-4 h-4 border-input rounded-input-sm grid place-content-center checkbox-engine">
                            <span class="text-sm font-bold">{{ lexicon('label_Lexicon_CustomLanguage', 'Custom Language (Blank Canvas)') }}</span>
                        </label>
                    </div>

                    <!-- Common Lang Wrapper (Tom Select) -->
                    <div x-show="modals.addLang.langType === 'common'" class="flex flex-col gap-xs">
                        <label for="common_lang_code" class="text-xs font-bold text-input-muted">{{ lexicon('label_Lexicon_SelectLanguage', 'Select Language') }}</label>
                        <select id="common_lang_code"
                                name="common_lang_code"
                                x-ref="commonLangSelect"
                                class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent"
                                aria-label="{{ lexicon('tooltip_Lexicon_SelectCommonLang', 'Select a language for AI auto-translation') }}"
                                x-tooltip="'{{ addslashes(lexicon('tooltip_Lexicon_SelectCommonLang', 'Select a language for AI auto-translation')) }}'">
                            <option value="es">{{ lexicon('label_Lexicon_OptLangEs', 'Spanish (ES)') }}</option>
                            <option value="fr">{{ lexicon('label_Lexicon_OptLangFr', 'French (FR)') }}</option>
                            <option value="de">{{ lexicon('label_Lexicon_OptLangDe', 'German (DE)') }}</option>
                            <option value="it">{{ lexicon('label_Lexicon_OptLangIt', 'Italian (IT)') }}</option>
                        </select>
                        <span class="text-xs text-muted mt-2">{{ lexicon('msg_Lexicon_AITranslationWarning', 'This will use AI to translate all current English terms. This may take a moment.') }}</span>
                    </div>

                    <!-- Custom Lang Wrapper -->
                    <div x-show="modals.addLang.langType === 'custom'" class="grid grid-cols-1 gap-sm w-full md-grid-cols-2" x-cloak>
                        <x-form-input name="custom_lang_name"
                                      id="custom_lang_name"
                                      type="text"
                                      :label="lexicon('label_common_Name', 'Name')"
                                      :placeholder="lexicon('label_Lexicon_NamePlaceholder', 'e.g. Pirate')"
                                      :tooltip="lexicon('tooltip_Lexicon_CustomLangName', 'Display name for the custom language')" />

                        <x-form-input name="custom_lang_code"
                                      id="custom_lang_code"
                                      type="text"
                                      :label="lexicon('label_Lexicon_ShortCode', 'Short Code')"
                                      :placeholder="lexicon('label_Lexicon_CodePlaceholder', 'e.g. prt')"
                                      :tooltip="lexicon('tooltip_Lexicon_CustomLangCode', 'Short unique locale code')" />
                    </div>

                    <div class="border-t-actions pt-6 flex flex-row justify-end gap-sm mt-4">
                        <x-button type="button"
                                  variant="secondary"
                                  class="p-btn-sm text-sm"
                                  @click="modals.addLang.open = false"
                                  aria-label="{{ lexicon('tooltip_common_Cancel', 'Cancel and close modal') }}"
                                  x-tooltip="'{{ addslashes(lexicon('tooltip_common_Cancel', 'Cancel and close modal')) }}'">
                            {{ lexicon('btn_common_Cancel', 'Cancel') }}
                        </x-button>

                        <x-button type="submit"
                                  variant="primary"
                                  class="p-btn-sm text-sm"
                                  aria-label="{{ lexicon('tooltip_Lexicon_InitLang', 'Initialise language pack') }}"
                                  x-tooltip="'{{ addslashes(lexicon('tooltip_Lexicon_InitLang', 'Initialise language pack')) }}'">
                            {{ lexicon('btn_Lexicon_InitializeLanguage', 'Initialise Language') }}
                        </x-button>
                    </div>
                </form>
            </x-modal>

            <!-- ============================================== -->
            <!-- MODAL: IMPORT LANGUAGE (DROPZONE)              -->
            <!-- ============================================== -->
            <x-modal show="modals.importLang.open" maxWidth="max-w-lg">
                <x-slot:top>
                    <span>{{ lexicon('label_Lexicon_ImportLanguage', 'Import Language') }}</span>
                </x-slot:top>

                <div class="flex flex-col gap-md">
                    <x-form-input name="import_lang_code"
                                  id="import_lang_code"
                                  type="text"
                                  :label="lexicon('label_Lexicon_TargetLangCode', 'Target Language Code (Optional)')"
                                  :placeholder="lexicon('label_Lexicon_ImportCodePlaceholder', 'e.g. klg')"
                                  :tooltip="lexicon('tooltip_Lexicon_ImportLangCode', 'Optional override for imported language code')"
                                  xModel="modals.importLang.langCode" />
                    <span class="text-xs text-muted -mt-2">{{ lexicon('msg_Lexicon_ImportCodeHint', 'Leave blank if your JSON file was exported from this system (it contains the language code automatically).') }}</span>

                    <div class="flex flex-col gap-xs">
                        <span class="text-xs font-bold text-input-muted">
                            <span>{{ lexicon('label_Lexicon_UploadJson', 'Upload JSON File') }}</span>
                            <span class="text-required">*</span>
                        </span>

                        <div x-ref="lexiconDropzone"
                             class="bg-panel-sub border-dashed-panel rounded-panel p-panel text-center cursor-pointer hover-bg-panel-hover transition-btn"
                             aria-label="{{ lexicon('tooltip_Lexicon_DropzoneUpload', 'Drag and drop a .json lexicon file or click to browse') }}"
                             x-tooltip="'{{ addslashes(lexicon('tooltip_Lexicon_DropzoneUpload', 'Drag and drop a .json lexicon file or click to browse')) }}'">
                            <div class="dz-message flex flex-col items-center justify-center gap-xs">
                                <i class="bi bi-file-earmark-arrow-up text-2xl text-accent"></i>
                                <span class="font-bold text-sm text-h">{{ lexicon('label_Lexicon_DropJsonHere', 'Drop Lexicon JSON (.json) here') }}</span>
                                <span class="text-xs text-muted">{{ lexicon('msg_Themes_BrowseFiles', 'or click to browse your files') }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="border-t-actions pt-6 flex flex-row justify-end gap-sm mt-4">
                        <x-button type="button"
                                  variant="secondary"
                                  class="p-btn-sm text-sm"
                                  @click="modals.importLang.open = false"
                                  aria-label="{{ lexicon('tooltip_common_Cancel', 'Cancel and close modal') }}"
                                  x-tooltip="'{{ addslashes(lexicon('tooltip_common_Cancel', 'Cancel and close modal')) }}'">
                            {{ lexicon('btn_common_Cancel', 'Cancel') }}
                        </x-button>

                        <x-button type="button"
                                  variant="primary"
                                  class="p-btn-sm text-sm"
                                  @click="submitImport()"
                                  aria-label="{{ lexicon('tooltip_Lexicon_UploadImport', 'Upload and merge lexicon file') }}"
                                  x-tooltip="'{{ addslashes(lexicon('tooltip_Lexicon_UploadImport', 'Upload and merge lexicon file')) }}'">
                            {{ lexicon('btn_Lexicon_ImportFile', 'Import File') }}
                        </x-button>
                    </div>
                </div>
            </x-modal>

            <!-- ============================================== -->
            <!-- MODAL: EDIT LANGUAGE LABEL                     -->
            <!-- ============================================== -->
            <x-modal show="modals.editLang.open" maxWidth="max-w-lg">
                <x-slot:top>
                    <span>{{ lexicon('label_Lexicon_EditLanguageSettings', 'Edit Language Settings') }}</span>
                </x-slot:top>

                <form method="POST" action="/admin/lexicon/language" x-data="ajaxForm" @submit.prevent="submit" class="flex flex-col gap-md"
                      data-loading-title="{{ lexicon('msg_Lexicon_UpdatingLang', 'Updating Language...') }}"
                      data-loading-msg="{{ lexicon('msg_common_Saving', 'Saving...') }}">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="lang_code" :value="modals.editLang.code">

                    <x-form-input name="lang_label"
                                  id="edit_lang_label"
                                  type="text"
                                  :label="lexicon('label_Lexicon_DisplayLabel', 'Display Label')"
                                  :tooltip="lexicon('tooltip_Lexicon_EditLangLabel', 'Update language display name')"
                                  xModel="modals.editLang.label"
                                  :required="true" />

                    <div class="border-t-actions pt-6 flex flex-row justify-end gap-sm mt-4">
                        <x-button type="button"
                                  variant="secondary"
                                  class="p-btn-sm text-sm"
                                  @click="modals.editLang.open = false"
                                  aria-label="{{ lexicon('tooltip_common_Cancel', 'Cancel and close modal') }}"
                                  x-tooltip="'{{ addslashes(lexicon('tooltip_common_Cancel', 'Cancel and close modal')) }}'">
                            {{ lexicon('btn_common_Cancel', 'Cancel') }}
                        </x-button>

                        <x-button type="submit"
                                  variant="primary"
                                  class="p-btn-sm text-sm"
                                  aria-label="{{ lexicon('tooltip_Lexicon_UpdateLang', 'Save language settings') }}"
                                  x-tooltip="'{{ addslashes(lexicon('tooltip_Lexicon_UpdateLang', 'Save language settings')) }}'">
                            {{ lexicon('btn_common_Update', 'Update') }}
                        </x-button>
                    </div>
                </form>
            </x-modal>

        </x-slot:bottom>

    </x-admin-grid-wrapper>
</div>
@endsection