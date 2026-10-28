// resources/js/managers/lexiconCodexManager.js
export default () => ({
    activeTab: 'gb',
    searchQuery: '',
    searchHelperInstance: null,
    termsList: [],
    matchedIds: new Set(),
    matchedMasters: new Set(),
    matchedSubgroups: new Set(),
    tomSelectInstance: null,
    dropzoneInstance: null,

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
            langType: 'common'
        },
        importLang: {
            open: false,
            langCode: ''
        },
        editLang: {
            open: false,
            code: '',
            label: ''
        }
    },

    init() {
        this.initFuse();
        this.initTomSelect();
        this.initDropzone();

        this.$watch('searchQuery', () => {
            this.performSearch();
        });
    },

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

        // Utilize the standardized global window.Search helper (powered by Fuse.js)
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

        // Map matching items back to master and subgroup sets
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

        // Leverage Dropzone library directly or via DropzoneHelper wrapper
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

        // Updated to use window.updateDOM with sanitize = false (replacing old _US helper)
        if (typeof window.updateDOM === 'function') {
            await window.updateDOM(this.$refs, ['lexiconTabsHeader', 'lexiconActiveToolbar', 'lexiconTableContent'], () => {
                this.modals.term.open = false;
                this.modals.addLang.open = false;
                this.modals.importLang.open = false;
                this.modals.editLang.open = false;
                this.initFuse();
            }, false);
        } else {
            this.modals.term.open = false;
            this.modals.addLang.open = false;
            this.modals.importLang.open = false;
            this.modals.editLang.open = false;
            this.initFuse();
        }
    },

    openTermModal(mode, id = '', code_key = '', master_group = '', subgroup = '', translations = {}) {
        this.modals.term.mode = mode;
        this.modals.term.id = id;
        this.modals.term.code_key = code_key;
        this.modals.term.master_group = master_group;
        this.modals.term.subgroup = subgroup;
        this.modals.term.translations = translations && typeof translations === 'object' ? { ...translations } : {};

        if (Object.keys(this.modals.term.translations).length === 0) {
            this.modals.term.translations = { gb: '' };
        }

        this.modals.term.action = '/admin/lexicon';
        this.modals.term.method = 'POST';
        this.modals.term.open = true;
    },

    openEditLangModal(code, label) {
        this.modals.editLang.code = code;
        this.modals.editLang.label = label;
        this.modals.editLang.open = true;
    }
});