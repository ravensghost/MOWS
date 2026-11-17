// resources/js/managers/lexiconCodexManager.js

export default function lexiconCodexManager() {
    return {
        // 1. State Properties Upfront (Unified Modals & Search Contract)
        modals: {
            term: {
                isOpen: false,
                open: false,
                mode: 'create',
                id: '',
                semantic_token: '',
                prefix_group_title: '',
                group_title: '',
                default_lexicon_string: '',
                translation_payloads: {},
                code_key: '',
                master_group: '',
                subgroup: '',
                translations: {},
                action: '/admin/lexicon-codex',
                method: 'POST'
            },
            addLang: {
                isOpen: false,
                open: false,
                mode: 'create',
                langType: 'common',
                action: '/admin/lexicon-codex/language',
                method: 'POST'
            },
            importLang: {
                isOpen: false,
                open: false,
                mode: 'create',
                langCode: '',
                action: '/admin/lexicon-codex/import',
                method: 'POST'
            },
            editLang: {
                isOpen: false,
                open: false,
                mode: 'edit',
                code: '',
                label: '',
                action: '/admin/lexicon-codex/language',
                method: 'PUT'
            }
        },
        activeTab: 'gb',
        searchQuery: '',
        searchHelperInstance: null,
        filteredIds: null,
        termsList: [],
        matchedIds: new Set(),
        matchedMasters: new Set(),
        matchedSubgroups: new Set(),
        tomSelectInstance: null,
        dropzoneInstance: null,
        isLoading: false,

        // 2. Lifecycle Hook
        init() {
            this.initSearch();
            this.initTomSelect();
            this.initDropzone();

            this.$watch('searchQuery', () => {
                this.performSearch();
            });
        },

        // 3. Search & Filtering (Fuse.js Integration)
        initSearch() {
            const rows = this.$el ? this.$el.querySelectorAll('[data-search-item], [data-term-row]') : [];
            this.termsList = Array.from(rows).map((row) => {
                let translationsObj = {};
                try {
                    translationsObj = JSON.parse(row.dataset.translations || row.dataset.translationPayloads || '{}');
                } catch (e) {
                    translationsObj = {};
                }

                return {
                    id: Number(row.dataset.id),
                    master: row.dataset.prefixGroupTitle || row.dataset.master || '',
                    sub: row.dataset.groupTitle || row.dataset.sub || '',
                    key: row.dataset.semanticToken || row.dataset.key || '',
                    translations: Object.values(translationsObj).join(' ')
                };
            });

            if (window.Search && rows.length > 0) {
                try {
                    this.searchHelperInstance = window.Search.create(
                        '[data-search-item], [data-term-row]',
                        ['semantic_token', 'prefix_group_title', 'group_title', 'key', 'master', 'sub', 'translations'],
                        { threshold: 0.3 }
                    );
                } catch (e) {
                    this.searchHelperInstance = null;
                }
            }

            this.performSearch();
        },

        initFuse() {
            this.initSearch();
        },

        performSearch() {
            const query = this.searchQuery.trim();
            if (!query || !this.searchHelperInstance) {
                this.matchedIds = new Set(this.termsList.map(t => t.id));
                this.matchedMasters = new Set(this.termsList.map(t => t.master));
                this.matchedSubgroups = new Set(this.termsList.map(t => `${t.master}::${t.sub}`));
                return;
            }

            const matchedIdsArray = (this.searchHelperInstance.search(query) || []).map(Number);
            this.matchedIds = new Set(matchedIdsArray);

            const matchedItems = this.termsList.filter(t => this.matchedIds.has(t.id));
            this.matchedMasters = new Set(matchedItems.map(t => t.master));
            this.matchedSubgroups = new Set(matchedItems.map(t => `${t.master}::${t.sub}`));
        },

        isItemVisible(id) {
            if (!this.searchQuery.trim()) return true;
            return this.matchedIds.has(Number(id));
        },

        termVisible(id) {
            return this.isItemVisible(id);
        },

        masterVisible(master) {
            if (!this.searchQuery.trim()) return true;
            return this.matchedMasters.has(master);
        },

        subgroupVisible(master, sub) {
            if (!this.searchQuery.trim()) return true;
            return this.matchedSubgroups.has(`${master}::${sub}`);
        },

        initTomSelect() {
            if (this.$refs?.commonLangSelect && window.Select) {
                this.tomSelectInstance = window.Select.init(this.$refs.commonLangSelect, {
                    create: false,
                    sortField: { field: 'text', direction: 'asc' }
                });
            }
        },

        initDropzone() {
            if (!this.$refs?.lexiconDropzone || !window.DropzoneHelper) return;

            this.dropzoneInstance = window.DropzoneHelper.create(this.$refs.lexiconDropzone, {
                url: '/admin/lexicon-codex/import',
                paramName: 'import_file',
                maxFiles: 1,
                maxFilesize: 10,
                acceptedFiles: '.json,application/json,text/plain',
                autoProcessQueue: false,
                addRemoveLinks: true,
                init: function () {
                    this.on('maxfilesexceeded', function (file) {
                        this.removeAllFiles();
                        this.addFile(file);
                    });
                }
            });

            if (!this.dropzoneInstance) return;

            this.dropzoneInstance.on('sending', (file, xhr, formData) => {
                if (this.modals.importLang.langCode) {
                    formData.append('import_lang_code', this.modals.importLang.langCode);
                }
                if (window.Swal && typeof window.Swal.loading === 'function') {
                    window.Swal.loading('Importing Lexicon...', 'Parsing JSON and merging records...');
                }
            });

            this.dropzoneInstance.on('success', (file, response) => {
                this.dropzoneInstance.removeAllFiles();
                this.closeModals();

                if (window.Swal && typeof window.Swal.toast === 'function') {
                    window.Swal.toast('success', response?.message || 'Lexicon imported successfully.');
                }
                this.refreshDOM();
            });

            this.dropzoneInstance.on('error', (file, errorMessage) => {
                this.dropzoneInstance.removeFile(file);
                const msg = typeof errorMessage === 'object' && errorMessage?.message
                    ? errorMessage.message
                    : (typeof errorMessage === 'string' ? errorMessage : 'Failed to import lexicon file.');

                if (window.Swal && typeof window.Swal.error === 'function') {
                    window.Swal.error('Import Error', msg);
                }
            });
        },

        submitImport() {
            if (!this.dropzoneInstance || this.dropzoneInstance.getQueuedFiles().length === 0) {
                if (window.Swal && typeof window.Swal.fire === 'function') {
                    window.Swal.fire({
                        icon: 'warning',
                        title: 'Missing File',
                        text: 'Please select a JSON file to import.',
                        ...window.Swal.themeConfig()
                    });
                }
                return;
            }
            this.dropzoneInstance.processQueue();
        },

        // 4. Modal Handlers (Using window.Modal utility)
        openTermModal(mode = 'create', id = '', semanticToken = '', prefixGroup = '', groupTitle = '', translations = {}) {
            const baseUrl = '/admin/lexicon-codex';
            const resolvedTranslations = translations && typeof translations === 'object'
                ? { ...translations }
                : { gb: '' };

            if (mode === 'edit') {
                window.Modal.edit(this.modals.term, {
                    id: id,
                    semantic_token: semanticToken,
                    prefix_group_title: prefixGroup,
                    group_title: groupTitle,
                    translation_payloads: resolvedTranslations,
                    code_key: semanticToken,
                    master_group: prefixGroup,
                    subgroup: groupTitle,
                    translations: resolvedTranslations
                }, () => baseUrl);
                this.modals.term.method = 'POST';
            } else {
                window.Modal.create(this.modals.term, {
                    semantic_token: '',
                    prefix_group_title: '',
                    group_title: '',
                    translation_payloads: { gb: '' },
                    code_key: '',
                    master_group: '',
                    subgroup: '',
                    translations: { gb: '' }
                }, baseUrl);
            }
        },

        openAddLangModal(langType = 'common') {
            window.Modal.create(this.modals.addLang, { langType }, '/admin/lexicon-codex/language');
        },

        openImportLangModal(langCode = '') {
            window.Modal.create(this.modals.importLang, { langCode }, '/admin/lexicon-codex/import');
        },

        openEditLangModal(code = '', label = '') {
            window.Modal.edit(this.modals.editLang, { code, label }, () => '/admin/lexicon-codex/language');
        },

        closeModals() {
            window.Modal.close(this.modals);
            this.modals.importLang.langCode = '';
        },

        // 5. DOM Refresh Handler (sanitize = false)
        async refreshDOM(isClearAction = false) {
            if (isClearAction) {
                if (window.Swal && typeof window.Swal.loading === 'function') {
                    window.Swal.loading('Rebuilding UI...', 'Auto-discovering fresh terms. Almost done!');
                }
                await window.Fetch.get(window.location.href);
            }

            if (typeof window.updateDOM === 'function') {
                await window.updateDOM(this.$refs, ['lexiconTabsHeader', 'lexiconActiveToolbar', 'lexiconTableContent'], () => {
                    this.closeModals();
                    this.$nextTick(() => {
                        this.initSearch();
                    });
                }, false);
            } else {
                this.closeModals();
                this.$nextTick(() => {
                    this.initSearch();
                });
            }
        }
    };
}