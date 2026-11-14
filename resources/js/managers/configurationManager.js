// resources/js/managers/configurationManager.js
export default (initialAiMode = 'cloud', initialMailMailer = 'smtp') => ({
    ai_mode: initialAiMode,
    mail_mailer: initialMailMailer,

    searchQuery: '',
    searchHelperInstance: null,
    matchedKeys: null,
    matchedGroups: null,

    tomSelectInstance: null,

    modal: {
        open: false,
        key: '',
        configuration_token: '',
        title: '',
        value: '',
        configuration_payload: '',
        type: 'select',
        options: {}
    },
    tsModal: { open: false, isEdit: false, data: {} },

    init() {
        this.initFuse();
        this.$watch('searchQuery', () => {
            this.filterSettings();
        });
    },

    initFuse() {
        if (this.$refs.settingsSearchData && window.Search) {
            try {
                this.searchHelperInstance = window.Search.create('#settings-search-catalog [data-setting-item], [data-settings-search-item]', ['label', 'configuration_token', 'key', 'group'], {
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
                            this.modal.configuration_payload = val;
                        }
                    });
                    window.Select.setValue(selectEl, String(this.modal.configuration_payload ?? ''), true);
                }
            });
        }
    },

    openModal(key, title, value, type = 'select', options = {}) {
        this.modal.key = key;
        this.modal.configuration_token = key;
        this.modal.title = title;
        this.modal.value = value;
        this.modal.configuration_payload = value;
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
                engine_title: '',
                calculation_algorithm: 'real_world',
                date_prefix: '',
                date_suffix: '',
                base_earth_year: '',
                base_earth_modifier: 0,
                base_system_value: '',
                temporal_multiplier: 1
            };
        }
        this.tsModal.open = true;
    },

    async refreshDOM() {
        const activeToken = this.modal.configuration_token || this.modal.key;
        const activePayload = this.modal.configuration_payload ?? this.modal.value;

        if (this.modal.open && activeToken === 'ai_mode') {
            this.ai_mode = activePayload;
        }
        if (this.modal.open && activeToken === 'mail_mailer') {
            this.mail_mailer = activePayload;
        }

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