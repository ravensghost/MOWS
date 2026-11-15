// resources/js/managers/tableOfContentParametersManager.js
export default () => ({
    // 1. State Properties Upfront (Unified Modals & Search Contract)
    modals: {
        parameter: {
            open: false,
            mode: 'create',
            parameterId: null,
            parameter_mode: 'group',
            parameter_value: '',
            parameter_label: '',
            parameter_sort_order: 0,
            action: '/admin/toc-parameters',
            method: 'POST'
        }
    },
    isSubmitting: false,
    searchQuery: '',
    searchHelperInstance: null,
    filteredIds: null,

    // 2. Lifecycle Hook
    init() {
        this.initSearch();
    },

    // 3. Search & Filtering (Fuse.js Integration)
    initSearch() {
        if (window.Search && this.$el && this.$el.querySelector('[data-toc-parameter-item]')) {
            try {
                this.searchHelperInstance = window.Search.create(
                    '[data-toc-parameter-item]',
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
    openModal(mode = 'create', data = {}) {
        const baseUrl = '/admin/toc-parameters';
        if (mode === 'edit') {
            window.Modal.edit(this.modals.parameter, data, (id) => `${baseUrl}/${id}`);
            this.modals.parameter.mode = 'edit';
            this.modals.parameter.method = 'PUT';
            this.modals.parameter.action = `${baseUrl}/${data.parameterId || data.id}`;
        } else {
            window.Modal.create(this.modals.parameter, {
                parameter_mode: 'group',
                parameter_value: '',
                parameter_label: '',
                parameter_sort_order: 0
            }, baseUrl);
            this.modals.parameter.mode = 'create';
            this.modals.parameter.method = 'POST';
            this.modals.parameter.action = baseUrl;
        }
        this.modals.parameter.open = true;
    },

    closeModal() {
        window.Modal.close(this.modals.parameter);
        this.modals.parameter.open = false;
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
});