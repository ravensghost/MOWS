// resources/js/managers/lexiconCodexManager.js
export default () => ({
    // 1. State Properties Upfront (Unified Modals & State Contract)
    modals: {
        term: {
            open: false,
            mode: 'create',
            id: '',
            code_key: '',
            master_group: '',
            subgroup: '',
            translations: {},
            action: '/admin/lexicon',
            method: 'POST'
        },
        addLang: {
            open: false,
            langType: 'common',
            action: '/admin/lexicon/languages',
            method: 'POST'
        },
        importLang: {
            open: false,
            langCode: '',
            action: '/admin/lexicon/import',
            method: 'POST'
        },
        editLang: {
            open: false,
            code: '',
            label: '',
            action: '/admin/lexicon/languages',
            method: 'PUT'
        }
    },
    activeTab: 'gb',
    searchQuery: '',
    searchHelperInstance: null,
    termsList: [],
    matchedIds: new Set(),
    matchedMasters: new Set(),
    matchedSubgroups: new Set(),
    tomSelectInstance: null,
    dropzoneInstance: null,

    // 2. Lifecycle Hook
    init() {
        this.initFuse();
        this.initTomSelect();
        this.initDropzone();

        this.$watch('searchQuery', () => {
            this.performSearch();
        });
    },

    // 3. Search & Filtering (Fuse.js Integration)
    initFuse() {
        const rows = this.$el.querySelectorAll('[data-term-row]');
        this.termsList = Array.from(rows).map((row) => {
            let translationsObj = {};
            try {
                translationsObj = JSON.parse(row.dataset.translations || '{}');
            } catch (e) {
                translationsObj = {};
            }

            return {
                id: Number(row.dataset.id),
                master: row.dataset.master || '',
                sub: row.dataset.sub || '',
                key: row.dataset.key || '',
                translations: Object.values(translationsObj).join(' ')
            };
        });

        if (window.Search) {
            this.searchHelperInstance = window.Search.create('[data-term-row]', ['key', 'master', 'sub', 'translations'], {
                threshold: 0.3
            });
        }

        this.performSearch();
    },

    performSearch() {
        const query = this.searchQuery.trim();
        if (!query || !this.searchHelperInstance) {
            this.matchedIds = new Set(this.termsList.map(t => t.id));
            this.matchedMasters = new Set(this.termsList.map(t => t.master));
            this.matchedSubgroups = new Set(this.termsList.map(t => `${t.master}::${t.sub}`));
            return;
        }

        const matchedIdsArray = this.searchHelperInstance.search(query) || [];
        this.matchedIds = new Set(matchedIdsArray);

        const matchedItems = this.termsList.filter(t => this.matchedIds.has(t.id));
        this.matchedMasters = new Set(matchedItems.map(t => t.master));
        this.matchedSubgroups = new Set(matchedItems.map(t => `${t.master}::${t.sub}`));
    },

    termVisible(id) {
        if (!this.searchQuery.trim()) return true;
        return this.matchedIds.has(Number(id));
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
        if (this.$refs.commonLangSelect && window.Select) {
            this.tomSelectInstance = window.Select.init(this.$refs.commonLangSelect, {
                create: false,
                sortField: { field: 'text', direction: 'asc' }
            });
        }
    },

    initDropzone() {
        if (!this.$refs.lexiconDropzone) return;

        if (this.dropzoneInstance) {
            this.dropzoneInstance.destroy();
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        this.dropzoneInstance = new Dropzone(this.$refs.lexiconDropzone, {
            url: '/admin/lexicon/import',
            paramName: 'import_file',
            maxFiles: 1,
            maxFilesize: 10,
            acceptedFiles: '.json,application/json,text/plain',
            autoProcessQueue: false,
            addRemoveLinks: true,
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            init: function () {
                this.on('maxfilesexceeded', function (file) {
                    this.removeAllFiles();
                    this.addFile(file);
                });
            }
        });

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
            window.Modal.close(this.modals.importLang);
            this.modals.importLang.open = false;
            this.modals.importLang.langCode = '';

            if (window.Swal && typeof window.Swal.fire === 'function') {
                window.Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: response.message || 'Lexicon imported successfully.',
                    timer: 2000,
                    showConfirmButton: false,
                    ...window.Swal.themeConfig()
                });
            }
            this.refreshDOM();
        });

        this.dropzoneInstance.on('error', (file, errorMessage) => {
            this.dropzoneInstance.removeFile(file);
            const msg = typeof errorMessage === 'object' && errorMessage.message
                ? errorMessage.message
                : (typeof errorMessage === 'string' ? errorMessage : 'Failed to import lexicon file.');

            if (window.Swal && typeof window.Swal.error === 'function') {
                window.Swal.error('Import Error', msg);
            }
        });
    },

    submitImport() {
        if (!this.dropzoneInstance || this.dropzoneInstance.getQueuedFiles().length === 0) {
            if (window.Swal) {
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
    openTermModal(mode = 'create', id = '', code_key = '', master_group = '', subgroup = '', translations = {}) {
        const baseUrl = '/admin/lexicon';
        if (mode === 'edit') {
            window.Modal.edit(this.modals.term, {
                id,
                code_key,
                master_group,
                subgroup,
                translations: translations && typeof translations === 'object' ? { ...translations } : { gb: '' }
            }, (termId) => `${baseUrl}/${termId}`);
            this.modals.term.mode = 'edit';
            this.modals.term.method = 'PUT';
            this.modals.term.action = `${baseUrl}/${id}`;
        } else {
            window.Modal.create(this.modals.term, {
                code_key: '',
                master_group: '',
                subgroup: '',
                translations: { gb: '' }
            }, baseUrl);
            this.modals.term.mode = 'create';
            this.modals.term.method = 'POST';
            this.modals.term.action = baseUrl;
        }
        this.modals.term.open = true;
    },

    openAddLangModal(langType = 'common') {
        const baseUrl = '/admin/lexicon/languages';
        window.Modal.create(this.modals.addLang, { langType }, baseUrl);
        this.modals.addLang.langType = langType;
        this.modals.addLang.method = 'POST';
        this.modals.addLang.action = baseUrl;
        this.modals.addLang.open = true;
    },

    openImportLangModal(langCode = '') {
        const baseUrl = '/admin/lexicon/import';
        window.Modal.create(this.modals.importLang, { langCode }, baseUrl);
        this.modals.importLang.langCode = langCode;
        this.modals.importLang.method = 'POST';
        this.modals.importLang.action = baseUrl;
        this.modals.importLang.open = true;
    },

    openEditLangModal(code = '', label = '') {
        const baseUrl = '/admin/lexicon/languages';
        window.Modal.edit(this.modals.editLang, { code, label }, (langCode) => `${baseUrl}/${langCode}`);
        this.modals.editLang.code = code;
        this.modals.editLang.label = label;
        this.modals.editLang.method = 'PUT';
        this.modals.editLang.action = `${baseUrl}/${code}`;
        this.modals.editLang.open = true;
    },

    closeModals() {
        window.Modal.close(this.modals.term);
        window.Modal.close(this.modals.addLang);
        window.Modal.close(this.modals.importLang);
        window.Modal.close(this.modals.editLang);
        this.modals.term.open = false;
        this.modals.addLang.open = false;
        this.modals.importLang.open = false;
        this.modals.editLang.open = false;
    },

    // 5. DOM Refresh Handler (sanitize = false)
    async refreshDOM(isClearAction = false) {
        if (isClearAction) {
            if (window.Swal && typeof window.Swal.fire === 'function') {
                window.Swal.fire({
                    title: 'Rebuilding UI...',
                    html: 'Auto-discovering fresh terms. Almost done!',
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    didOpen: () => window.Swal.showLoading(),
                    ...window.Swal.themeConfig()
                });
            }
            await window.Fetch.get(window.location.href);
        }

        if (typeof window.updateDOM === 'function') {
            await window.updateDOM(this.$refs, ['lexiconTabsHeader', 'lexiconActiveToolbar', 'lexiconTableContent'], () => {
                this.closeModals();
                this.$nextTick(() => {
                    this.initFuse();
                });
            }, false);
        } else {
            this.closeModals();
            this.$nextTick(() => {
                this.initFuse();
            });
        }
    }
});