// resources/js/managers/metadataManager.js

export default function metadataManager() {
    return {
        // 1. State Properties Upfront (Unified Modals & Search Contract)
        modals: {
            form: {
                isOpen: false,
                open: false,
                mode: 'create',
                id: '',
                metadata_title: '',
                name: '',
                is_visible: true,
                action: '/admin/metadata',
                method: 'POST'
            },
            field: {
                isOpen: false,
                open: false,
                mode: 'create',
                id: '',
                metadata_title: '',
                metadata_mode: 'text',
                linked_metadata: '',
                metadata_payloads: '',
                is_required: false,
                name: '',
                type: 'text',
                isRequired: false,
                is_visible: true,
                options: '',
                formLinked: '',
                serviceRecordGroups: [],
                action: '/admin/metadata/fields',
                method: 'POST'
            }
        },
        searchQuery: '',
        searchHelperInstance: null,
        filteredIds: null,
        isLoading: false,

        // 2. Lifecycle Hook
        init() {
            this.initSearch();
        },

        // 3. Search & Filtering (Fuse.js Integration)
        initSearch() {
            if (window.Search && this.$el && this.$el.querySelector('[data-search-item], [data-metadata-item]')) {
                try {
                    this.searchHelperInstance = window.Search.create(
                        '[data-search-item], [data-metadata-item]',
                        ['metadata_title', 'metadata_mode', 'name', 'type'],
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
            const baseUrl = '/admin/metadata';
            if (mode === 'create') {
                window.Modal.create(this.modals.form, {
                    metadata_title: '',
                    name: '',
                    is_visible: true
                }, baseUrl);
            } else {
                const resolvedTitle = data.metadata_title ?? data.name ?? '';
                window.Modal.edit(this.modals.form, {
                    ...data,
                    metadata_title: resolvedTitle,
                    name: resolvedTitle,
                    is_visible: data.is_visible !== undefined ? Boolean(data.is_visible) : true
                }, (id) => `${baseUrl}/${id}`);
            }
        },

        openField(mode = 'create', data = {}) {
            const baseUrl = '/admin/metadata/fields';
            if (mode === 'create') {
                window.Modal.create(this.modals.field, {
                    metadata_title: '',
                    metadata_mode: 'text',
                    linked_metadata: '',
                    metadata_payloads: '',
                    is_required: false,
                    name: '',
                    type: 'text',
                    isRequired: false,
                    is_visible: true,
                    options: '',
                    formLinked: '',
                    serviceRecordGroups: []
                }, baseUrl);
                return;
            }

            const resolvedTitle = data.metadata_title ?? data.name ?? '';
            const resolvedMode = data.metadata_mode ?? data.type ?? 'text';
            const resolvedRequired = Boolean(data.is_required ?? data.isRequired ?? false);
            const resolvedLinked = data.linked_metadata ?? data.formLinked ?? '';
            const resolvedPayloads = data.metadata_payloads ?? data.options ?? '';

            let groups = [];
            const rawGroupSource = data.serviceRecordGroups ?? resolvedPayloads;
            if (Array.isArray(rawGroupSource)) {
                groups = [...rawGroupSource];
            } else if (typeof rawGroupSource === 'string' && rawGroupSource.trim() !== '') {
                try {
                    const parsed = JSON.parse(rawGroupSource);
                    groups = Array.isArray(parsed) ? parsed : [rawGroupSource];
                } catch (e) {
                    groups = rawGroupSource.split(',').map(s => s.trim()).filter(Boolean);
                }
            }

            window.Modal.edit(this.modals.field, {
                ...data,
                metadata_title: resolvedTitle,
                metadata_mode: resolvedMode,
                linked_metadata: resolvedLinked,
                metadata_payloads: resolvedPayloads,
                is_required: resolvedRequired,
                name: resolvedTitle,
                type: resolvedMode,
                isRequired: resolvedRequired,
                is_visible: data.is_visible !== undefined ? Boolean(data.is_visible) : true,
                options: typeof resolvedPayloads === 'string' ? resolvedPayloads : JSON.stringify(resolvedPayloads),
                formLinked: resolvedLinked,
                serviceRecordGroups: groups
            }, (id) => `${baseUrl}/${id}`);
        },

        closeModals() {
            window.Modal.close(this.modals);
        },

        // 5. DOM Refresh Handler (sanitize = false)
        async refreshDOM() {
            if (typeof window.updateDOM === 'function') {
                await window.updateDOM(this.$refs, ['formsTableContent', 'fieldsTableContent', 'metadataTableContent'], () => {
                    this.closeModals();
                    this.$nextTick(() => {
                        this.initSearch();
                    });
                }, false);
            } else {
                this.closeModals();
            }
        }
    };
}