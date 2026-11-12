// resources/js/managers/bookBuilderManager.js
export default (initialGrids = [], availableModules = [], moduleRegistry = {}) => ({
    availableGrids: initialGrids,
    moduleData: Object.keys(moduleRegistry).length > 0 ? moduleRegistry : availableModules,
    isSyncing: false,

    async syncModules() {
        this.isSyncing = true;

        if (window.Swal && typeof window.Swal.loading === 'function') {
            window.Swal.loading('Scanning Modules...', 'Please wait while the registry is updated.');
        }

        try {
            const { ok, data } = await window.Fetch.post('/admin/modules/sync', {}, {
                loading: false,
                showError: false
            });

            if (ok && data && data.status === 'success') {
                this.moduleData = data.data;

                if (window.Swal && typeof window.Swal.toast === 'function') {
                    window.Swal.toast('success', data.message || 'Modules synchronised successfully.');
                }
            } else {
                window.Swal.error('Error', data?.message || 'Synchronisation failed.');
            }
        } catch (e) {
            window.Swal.error('Error', 'Network error occurred.');
        } finally {
            this.isSyncing = false;
        }
    },

    modals: {
        book: {
            open: false,
            mode: 'create',
            id: '',
            book_title: '',
            book_url: '',
            grid_id: '',
            is_published: true,
            formAction: '',
            method: '',
            gridModules: {},
            selectedModules: {},
            selectedVisible: {},
            moduleSortOrders: {},
            accessClearances: {},
            bookConfigs: {},
            configuringModuleId: null
        },
        grid: {
            open: false,
            mode: 'create',
            id: '',
            grid_title: '',
            grid_synopsis: '',
            grid_modules: [],
            formAction: '',
            method: ''
        },
        module: {
            open: false,
            mode: 'create',
            id: '',
            module_title: '',
            module_token: '',
            module_default_settings: '',
            dynamicValues: [],
            action: '/admin/modules',
            method: 'POST'
        }
    },

    async refreshDOM() {
        this.modals.book.open = false;
        this.modals.grid.open = false;
        this.modals.module.open = false;

        if (typeof window.updateDOM === 'function') {
            await window.updateDOM(this.$refs, ['booksTableContent', 'gridsTableContent', 'modulesTableContent'], (doc) => {
                const freshDataEl = doc?.getElementById('pb-fresh-data');
                if (freshDataEl) {
                    this.availableGrids = JSON.parse(freshDataEl.getAttribute('data-grids') || '[]');
                    this.moduleData = JSON.parse(freshDataEl.getAttribute('data-modules') || '{}');
                }
            }, false);
        }
    },

    openModal(mode, data = {}, type = 'book') {
        if (type === 'book') {
            this.modals.book.mode = mode;
            this.modals.book.id = data.id || '';
            this.modals.book.book_title = data.book_title || '';
            this.modals.book.book_url = data.book_url || '';
            this.modals.book.grid_id = data.grid_id || '';
            this.modals.book.is_published = mode === 'edit' ? Boolean(data.is_published) : true;
            this.modals.book.formAction = mode === 'edit' ? `/admin/books/${data.id}` : '/admin/books';
            this.modals.book.method = mode === 'edit' ? 'PUT' : 'POST';

            this.modals.book.gridModules = {};
            this.modals.book.selectedModules = {};
            this.modals.book.selectedVisible = {};
            this.modals.book.moduleSortOrders = {};
            this.modals.book.accessClearances = {};
            this.modals.book.bookConfigs = {};

            if (Array.isArray(data.modules)) {
                data.modules.forEach(m => {
                    const pivot = m.pivot || m;
                    this.modals.book.selectedModules[m.id] = true;
                    this.modals.book.selectedVisible[m.id] = pivot.is_visible !== undefined ? Boolean(pivot.is_visible) : true;
                    this.modals.book.moduleSortOrders[m.id] = pivot.module_sort_order ?? 0;
                    this.modals.book.gridModules[m.id] = pivot.grid_module_id ?? '';
                    this.modals.book.accessClearances[m.id] = pivot.access_clearance || '';

                    let cfg = pivot.book_config || {};
                    if (typeof cfg === 'string') {
                        try { cfg = JSON.parse(cfg); } catch (e) { cfg = {}; }
                    }
                    if (Array.isArray(cfg) || typeof cfg !== 'object' || cfg === null) {
                        cfg = {};
                    }
                    this.modals.book.bookConfigs[m.id] = cfg;
                });
            }

            this.modals.book.open = true;
            return;
        }

        if (type === 'grid') {
            this.modals.grid.mode = mode;
            this.modals.grid.id = data.id || '';
            this.modals.grid.grid_title = data.grid_title || '';
            this.modals.grid.grid_synopsis = data.grid_synopsis || '';
            this.modals.grid.grid_modules = data.grid_modules
                ? JSON.parse(JSON.stringify(data.grid_modules))
                : [{ id: '', grid_module_title: 'content', column_span: 12, row_span: 1 }];
            this.modals.grid.formAction = mode === 'edit' ? `/admin/grids/${data.id}` : '/admin/grids';
            this.modals.grid.method = mode === 'edit' ? 'PUT' : 'POST';
            this.modals.grid.open = true;
            return;
        }

        if (type === 'module') {
            this.modals.module.mode = mode;
            this.modals.module.id = data.id || '';
            this.modals.module.module_title = data.module_title || '';
            this.modals.module.module_token = data.module_token || '';

            let rawSettings = data.module_default_settings || '';
            if (typeof rawSettings === 'object' && rawSettings !== null) {
                try { rawSettings = JSON.stringify(rawSettings, null, 2); } catch (e) { rawSettings = ''; }
            }
            this.modals.module.module_default_settings = rawSettings;

            this.modals.module.action = mode === 'edit' ? `/admin/modules/${data.id}` : '/admin/modules';
            this.modals.module.method = mode === 'edit' ? 'PUT' : 'POST';

            if (mode === 'create') {
                this.initModuleSettings();
            }

            this.modals.module.open = true;
        }
    },

    getActiveGridModules() {
        const gridId = parseInt(this.modals.book.grid_id, 10);
        if (!gridId) return [];
        const grid = this.availableGrids.find(g => g.id === gridId);
        return grid && grid.grid_modules ? grid.grid_modules : [];
    },

    addGridModule() {
        this.modals.grid.grid_modules.push({ id: '', grid_module_title: '', column_span: 12, row_span: 1 });
    },

    removeGridModule(index) {
        this.modals.grid.grid_modules.splice(index, 1);
    },

    openConfigModal(moduleId, moduleToken) {
        this.modals.book.configuringModuleId = moduleId;
        this.modals.module.module_token = moduleToken;

        if (!this.modals.book.bookConfigs[moduleId]) {
            this.modals.book.bookConfigs[moduleId] = {};
        }

        const fields = this.currentModuleFields;
        for (let key in fields) {
            if (this.modals.book.bookConfigs[moduleId][key] === undefined) {
                this.modals.book.bookConfigs[moduleId][key] = fields[key].default || '';
            }
        }

        if (
            this.modals.module.module_default_settings &&
            typeof this.modals.module.module_default_settings === 'object' &&
            this.modals.module.module_default_settings[moduleId]
        ) {
            this.modals.book.bookConfigs[moduleId] = {
                ...this.modals.book.bookConfigs[moduleId],
                ...this.modals.module.module_default_settings[moduleId]
            };
        }

        this.modals.module.open = true;
    },

    get currentModuleFields() {
        if (!this.modals.module.module_token || !this.moduleData) return {};
        let data = this.moduleData[this.modals.module.module_token];
        if (!data) {
            const searchKey = this.modals.module.module_token.toLowerCase().replace('module_', '').replace('widget_', '');
            const foundKey = Object.keys(this.moduleData).find(k =>
                k.toLowerCase() === this.modals.module.module_token.toLowerCase() ||
                k.toLowerCase().replace('module_', '').replace('widget_', '') === searchKey
            );
            if (foundKey) data = this.moduleData[foundKey];
        }
        return data && data.fields ? data.fields : {};
    },

    initModuleSettings() {
        const fields = this.currentModuleFields;
        this.modals.module.module_default_settings = {};
        for (let key in fields) {
            this.modals.module.module_default_settings[key] = fields[key].default || '';
        }
    }
});