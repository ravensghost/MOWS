// resources/js/managers/tableOfContentParametersManager.js

export default function tableOfContentParametersManager() {
    return {
        // 1. State Properties Upfront (Unified Modals & Search Contract)
        modals: {
            parameter: {
                isOpen: false,
                open: false,
                mode: 'create',
                id: null,
                parameterId: null,
                parameter_mode: 'group',
                parameter_value: '',
                parameter_label: '',
                parameter_sort_order: 0,
                action: '/admin/table-of-contents/options',
                method: 'POST'
            }
        },
        isSubmitting: false,
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
            if (window.Search && this.$el && this.$el.querySelector('[data-search-item], [data-toc-parameter-item]')) {
                try {
                    this.searchHelperInstance = window.Search.create(
                        '[data-search-item], [data-toc-parameter-item]',
                        ['parameter_label', 'parameter_value', 'parameter_mode'],
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
        openModal(mode = 'create', data = {}, baseUrl = '/admin/table-of-contents/options') {
            if (mode === 'edit') {
                const recordId = data.parameterId ?? data.id ?? null;
                window.Modal.edit(this.modals.parameter, {
                    ...data,
                    id: recordId,
                    parameterId: recordId
                }, (id) => `${baseUrl}/${id}`);
            } else {
                window.Modal.create(this.modals.parameter, {
                    parameterId: null,
                    parameter_mode: data.parameter_mode || 'group',
                    parameter_value: '',
                    parameter_label: '',
                    parameter_sort_order: 0
                }, baseUrl);
            }
        },

        closeModal() {
            window.Modal.close(this.modals.parameter);
        },

        // 5. DOM Refresh Handler (sanitize = false)
        async refreshDOM() {
            if (typeof window.updateDOM === 'function') {
                await window.updateDOM(this.$refs, ['tocParametersTableWrapper'], () => {
                    this.closeModal();
                    this.$nextTick(() => {
                        this.initSearch();
                    });
                }, false);
            } else {
                this.closeModal();
            }
        }
    };
}