import Fuse from 'fuse.js';
import TomSelect from 'tom-select';
import Dropzone from 'dropzone';

export default () => ({
    activeTab: 'gb',
    searchQuery: '',
    fuse: null,
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

        this.fuse = new Fuse(this.termsList, {
            keys: ['key', 'master', 'sub', 'translations'],
            threshold: 0.3,
            ignoreLocation: true
        });

        this.performSearch();
    },

    performSearch() {
        const query = this.searchQuery.trim();
        if (!query || !this.fuse) {
            this.matchedIds = new Set(this.termsList.map(t => t.id));
            this.matchedMasters = new Set(this.termsList.map(t => t.master));
            this.matchedSubgroups = new Set(this.termsList.map(t => `${t.master}::${t.sub}`));
            return;
        }

        const results = this.fuse.search(query);
        this.matchedIds = new Set(results.map(r => r.item.id));
        this.matchedMasters = new Set(results.map(r => r.item.master));
        this.matchedSubgroups = new Set(results.map(r => `${r.item.master}::${r.item.sub}`));
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
        if (this.$refs.commonLangSelect) {
            if (this.tomSelectInstance) {
                this.tomSelectInstance.destroy();
            }
            this.tomSelectInstance = new TomSelect(this.$refs.commonLangSelect, {
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

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

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
            window.Swal?.fire({
                title: 'Importing Lexicon...',
                html: 'Parsing JSON and merging records...',
                allowOutsideClick: false,
                didOpen: () => window.Swal.showLoading()
            });
        });

        this.dropzoneInstance.on('success', (file, response) => {
            this.dropzoneInstance.removeAllFiles();
            this.modals.importLang.open = false;
            this.modals.importLang.langCode = '';
            window.Swal?.fire({
                icon: 'success',
                title: 'Success',
                text: response.message || 'Lexicon imported successfully.',
                timer: 2000,
                showConfirmButton: false
            });
            this.refreshDOM();
        });

        this.dropzoneInstance.on('error', (file, errorMessage) => {
            this.dropzoneInstance.removeFile(file);
            const msg = typeof errorMessage === 'object' && errorMessage.message
                ? errorMessage.message
                : (typeof errorMessage === 'string' ? errorMessage : 'Failed to import lexicon file.');
            window.Swal?.fire({
                icon: 'error',
                title: 'Import Error',
                text: msg
            });
        });
    },

    submitImport() {
        if (!this.dropzoneInstance || this.dropzoneInstance.getQueuedFiles().length === 0) {
            window.Swal?.fire({
                icon: 'warning',
                title: 'Missing File',
                text: 'Please select a JSON file to import.'
            });
            return;
        }
        this.dropzoneInstance.processQueue();
    },

    async refreshDOM(isClearAction = false) {
        if (isClearAction) {
            window.Swal?.update({ title: 'Rebuilding UI...', html: 'Auto-discovering fresh terms. Almost done!' });
            await fetch(window.location.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        }

        window.updateDOM_US(this.$refs, ['lexiconTabsHeader', 'lexiconActiveToolbar', 'lexiconTableContent'], () => {
            this.modals.term.open = false;
            this.modals.addLang.open = false;
            this.modals.importLang.open = false;
            this.modals.editLang.open = false;
            this.initFuse();
        });
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