export default (initialAiMode = 'cloud') => ({
    // Initialise with the variable passed from Blade
    ai_mode: initialAiMode,

    // Fuzzy search state (Fuse.js)
    searchQuery: '',
    fuseInstance: null,
    matchedKeys: null,
    matchedGroups: null,

    // TomSelect instance reference
    tomSelectInstance: null,

    // State objects
    modal: { open: false, key: '', title: '', value: '', type: 'select', options: {} },
    statusModal: { open: false, id: '', name: '', slug: '', sort_order: 0, isEdit: false },
    tsModal: { open: false, isEdit: false, data: {} },

    init() {
        this.initFuse();
    },

    initFuse() {
        if (this.$refs.settingsSearchData && window.Fuse) {
            try {
                const catalog = JSON.parse(this.$refs.settingsSearchData.textContent || '[]');
                this.fuseInstance = new window.Fuse(catalog, {
                    keys: ['label', 'key', 'group'],
                    threshold: 0.35,
                    ignoreLocation: true
                });
            } catch (e) {
                this.fuseInstance = null;
            }
        }
    },

    filterSettings() {
        const q = this.searchQuery.trim();
        if (!q || !this.fuseInstance) {
            this.matchedKeys = null;
            this.matchedGroups = null;
            return;
        }
        const results = this.fuseInstance.search(q);
        this.matchedKeys = results.map(r => r.item.key);
        this.matchedGroups = [...new Set(results.map(r => r.item.group))];
    },

    matchesSetting(key) {
        if (!this.searchQuery.trim() || this.matchedKeys === null) return true;
        return this.matchedKeys.includes(key);
    },

    matchesGroup(group) {
        if (!this.searchQuery.trim() || this.matchedGroups === null) return true;
        return this.matchedGroups.includes(group);
    },

    initModalSelect() {
        if (this.tomSelectInstance) {
            this.tomSelectInstance.destroy();
            this.tomSelectInstance = null;
        }

        if (this.modal.type === 'select') {
            this.$nextTick(() => {
                const selectEl = this.$refs.modalSelect;
                if (selectEl && window.TomSelect) {
                    this.tomSelectInstance = new window.TomSelect(selectEl, {
                        searchField: ['text'],
                        maxOptions: null,
                        onChange: (val) => {
                            this.modal.value = val;
                        }
                    });
                    this.tomSelectInstance.setValue(String(this.modal.value ?? ''), true);
                }
            });
        }
    },

    // Methods
    openModal(key, title, value, type = 'select', options = {}) {
        this.modal.key = key;
        this.modal.title = title;
        this.modal.value = value;
        this.modal.type = type;
        this.modal.options = options;
        this.modal.open = true;

        this.initModalSelect();
    },

    openStatusModal(id = '', name = '', slug = '', sort_order = 0) {
        this.statusModal.id = id;
        this.statusModal.name = name;
        this.statusModal.slug = slug;
        this.statusModal.sort_order = sort_order;
        this.statusModal.isEdit = !!id;
        this.statusModal.open = true;
    },

    openTsModal(ts = null) {
        if (ts) {
            this.tsModal.isEdit = true;
            this.tsModal.data = { ...ts };
        } else {
            this.tsModal.isEdit = false;
            this.tsModal.data = {
                id: '',
                name: '',
                engine_type: 'real_world',
                prefix: '',
                suffix: '',
                base_earth_year: '',
                base_system_value: '',
                multiplier: 1
            };
        }
        this.tsModal.open = true;
    },

    async refreshDOM() {
        if (this.modal.open && this.modal.key === 'ai_mode') {
            this.ai_mode = this.modal.value;
        }

        window.updateDOM_US(this.$refs, ['mainSettingsTable', 'bookStatusesTable', 'timeSystemsTable'], () => {
            if (this.tomSelectInstance) {
                this.tomSelectInstance.destroy();
                this.tomSelectInstance = null;
            }
            this.modal.open = false;
            this.statusModal.open = false;
            this.tsModal.open = false;
            this.filterSettings();
        });
    }
});