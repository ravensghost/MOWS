// resources/js/managers/metadataManager.js
export default () => ({
    // 1. State Properties Upfront (Unified Modals & State Contract)
    modals: {
        form: {
            open: false,
            mode: 'create',
            id: '',
            name: '',
            is_visible: true,
            action: '/admin/forms',
            method: 'POST'
        },
        field: {
            open: false,
            mode: 'create',
            id: '',
            name: '',
            type: 'text',
            isRequired: false,
            is_visible: true,
            options: '',
            formLinked: '',
            serviceRecordGroups: [],
            action: '/admin/forms/fields',
            method: 'POST'
        }
    },
    searchQuery: '',
    searchHelperInstance: null,
    filteredIds: null,

    // 2. Lifecycle Hook
    init() {
        this.initSearch();
    },

    // 3. Search & Filtering (Fuse.js Integration)
    initSearch() {
        if (window.Search && this.$el && this.$el.querySelector('[data-metadata-item]')) {
            try {
                this.searchHelperInstance = window.Search.create(
                    '[data-metadata-item]',
                    ['name', 'type'],
                    { threshold: 0.35 }
                );
            } catch (e) {
                this.searchHelperInstance = null;
            }
        }
        this.$watch('searchQuery', (val) => {
            if (!val.trim() || !this.searchHelperInstance) {
                this.filteredIds = null;
                return;
            }
            const results = this.searchHelperInstance.search(val.trim());
            this.filteredIds = results ? results.map(id => Number(id)) : [];
        });
    },

    isItemVisible(id) {
        if (!this.searchQuery.trim() || this.filteredIds === null) return true;
        return this.filteredIds.includes(Number(id));
    },

    // 4. Modal Handlers (Using window.Modal utility)
    openForm(mode = 'create', data = {}) {
        const baseUrl = '/admin/forms';
        if (mode === 'create') {
            window.Modal.create(this.modals.form, { is_visible: true }, baseUrl);
            this.modals.form.method = 'POST';
            this.modals.form.action = baseUrl;
        } else {
            window.Modal.edit(this.modals.form, data, (id) => `${baseUrl}/${id}`);
            this.modals.form.method = 'PUT';
            this.modals.form.action = `${baseUrl}/${data.id}`;
        }
        this.modals.form.open = true;
    },

    openField(mode = 'create', data = {}) {
        const baseUrl = '/admin/forms/fields';
        if (mode === 'create') {
            window.Modal.create(this.modals.field, {
                type: 'text',
                isRequired: false,
                is_visible: true,
                serviceRecordGroups: []
            }, baseUrl);
            this.modals.field.method = 'POST';
            this.modals.field.action = baseUrl;
        } else {
            window.Modal.edit(this.modals.field, data, (id) => `${baseUrl}/${id}`);
            this.modals.field.method = 'PUT';
            this.modals.field.action = `${baseUrl}/${data.id}`;
        }

        let groups = [];
        if (Array.isArray(data.serviceRecordGroups)) {
            groups = [...data.serviceRecordGroups];
        } else if (typeof data.serviceRecordGroups === 'string' && data.serviceRecordGroups.trim() !== '') {
            try {
                const parsed = JSON.parse(data.serviceRecordGroups);
                groups = Array.isArray(parsed) ? parsed : [data.serviceRecordGroups];
            } catch (e) {
                groups = data.serviceRecordGroups.split(',').map(s => s.trim()).filter(Boolean);
            }
        } else if (typeof data.options === 'string' && data.options.trim() !== '') {
            try {
                const parsed = JSON.parse(data.options);
                groups = Array.isArray(parsed) ? parsed : [];
            } catch (e) {
                groups = [];
            }
        }
        this.modals.field.serviceRecordGroups = groups;
        this.modals.field.open = true;
    },

    closeModals() {
        window.Modal.close(this.modals.form);
        window.Modal.close(this.modals.field);
        this.modals.form.open = false;
        this.modals.field.open = false;
    },

    // 5. DOM Refresh Handler (sanitize = false)
    async refreshDOM() {
        if (typeof window.updateDOM === 'function') {
            await window.updateDOM(this.$refs, ['formsTableContent', 'fieldsTableContent'], () => {
                this.closeModals();
                this.$nextTick(() => {
                    this.initSearch();
                });
            }, false);
        } else {
            this.closeModals();
        }
    }
});