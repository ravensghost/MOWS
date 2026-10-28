// resources/js/managers/configurationManager.js
export default (initialAiMode = 'cloud', initialMailMailer = 'smtp') => ({
    // Initialise with the variables passed from Blade
    ai_mode: initialAiMode,
    mail_mailer: initialMailMailer,

    // Fuzzy search state (Fuse.js)
    searchQuery: '',
    searchHelperInstance: null,
    matchedKeys: null,
    matchedGroups: null,

    // TomSelect instance reference
    tomSelectInstance: null,

    // State objects
    modal: { open: false, key: '', title: '', value: '', type: 'select', options: {} },
    tsModal: { open: false, isEdit: false, data: {} },

    init() {
        this.initFuse();
        this.$watch('searchQuery', () => {
            this.filterSettings();
        });
    },

    initFuse() {
        // Leverage the standardized global window.Search helper (powered by Fuse.js)
        if (this.$refs.settingsSearchData && window.Search) {
            try {
                this.searchHelperInstance = window.Search.create('#settings-search-catalog [data-setting-item], [data-settings-search-item]', ['label', 'key', 'group'], {
                    threshold: 0.35
                });
            } catch (e) {
                this.searchHelperInstance = null;
            }
        }
    },

    filterSettings() {
        const q = this.searchQuery.trim();
        if (!q || !this.searchHelperInstance) {
            this.matchedKeys = null;
            this.matchedGroups = null;
            return;
        }
        const results = this.searchHelperInstance.search(q);
        this.matchedKeys = results ? results.map(id => String(id)) : [];
        // If results return item keys, ensure matchedGroups extracts properly from matching data elements if needed
        this.matchedGroups = null;
    },

    matchesSetting(key) {
        if (!this.searchQuery.trim() || this.matchedKeys === null) return true;
        return this.matchedKeys.includes(String(key));
    },

    matchesGroup(group) {
        if (!this.searchQuery.trim() || this.matchedGroups === null) return true;
        return this.matchedGroups.includes(group);
    },

    initModalSelect() {
        if (this.tomSelectInstance && window.Select) {
            window.Select.destroy(this.$refs.modalSelect);
            this.tomSelectInstance = null;
        }

        if (this.modal.type === 'select') {
            this.$nextTick(() => {
                const selectEl = this.$refs.modalSelect;
                if (selectEl && window.Select) {
                    this.tomSelectInstance = window.Select.init(selectEl, {
                        searchField: ['text'],
                        maxOptions: null,
                        onChange: (val) => {
                            this.modal.value = val;
                        }
                    });
                    window.Select.setValue(selectEl, String(this.modal.value ?? ''), true);
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
        if (this.modal.open && this.modal.key === 'mail_mailer') {
            this.mail_mailer = this.modal.value;
        }

        // Updated to use window.updateDOM with sanitize = false (replacing old _US helper)
        if (typeof window.updateDOM === 'function') {
            await window.updateDOM(this.$refs, ['mainSettingsTable', 'timeSystemsTable'], () => {
                if (this.tomSelectInstance && window.Select) {
                    window.Select.destroy(this.$refs.modalSelect);
                    this.tomSelectInstance = null;
                }
                this.modal.open = false;
                this.tsModal.open = false;
                this.filterSettings();
            }, false);
        } else {
            if (this.tomSelectInstance && window.Select) {
                window.Select.destroy(this.$refs.modalSelect);
                this.tomSelectInstance = null;
            }
            this.modal.open = false;
            this.tsModal.open = false;
            this.filterSettings();
        }
    }
});