// resources/js/managers/tableOfContentParametersManager.js
export default () => ({
    // 1. State Properties Upfront
    formMode: 'create',
    parameterId: null,
    parameter_mode: 'group',
    parameter_value: '',
    parameter_label: '',
    parameter_sort_order: 0,
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
        if (window.Search && this.$el.querySelector('[data-search-item]')) {
            try {
                this.searchHelperInstance = window.Search.create(
                    '[data-search-item]',
                    ['parameter_mode', 'parameter_value', 'parameter_label'],
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

    // 4. Form & Parameter Actions
    editParameter(parameter) {
        this.formMode = 'edit';
        this.parameterId = parameter.id;
        this.parameter_mode = parameter.parameter_mode || 'group';
        this.parameter_value = parameter.parameter_value || '';
        this.parameter_label = parameter.parameter_label || '';
        this.parameter_sort_order = parameter.parameter_sort_order ?? 0;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    },

    resetForm() {
        this.formMode = 'create';
        this.parameterId = null;
        this.parameter_mode = 'group';
        this.parameter_value = '';
        this.parameter_label = '';
        this.parameter_sort_order = 0;
    },

    async submitParameter() {
        if (this.isSubmitting) return;
        this.isSubmitting = true;

        const isEdit = this.formMode === 'edit' && this.parameterId;
        const endpoint = isEdit ? `/admin/navigation/options/${this.parameterId}` : '/admin/navigation/options';
        const method = isEdit ? 'PUT' : 'POST';
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    _method: method,
                    parameter_mode: this.parameter_mode,
                    parameter_value: this.parameter_value,
                    parameter_label: this.parameter_label,
                    parameter_sort_order: this.parameter_sort_order
                })
            });

            const data = await response.json();

            if (!response.ok) {
                const errorTitle = this.$el.dataset.msgErrorTitle || 'Error';
                const errorText = data.message || this.$el.dataset.msgValidationFailed || 'Validation failed.';
                if (window.Swal) {
                    window.Swal.fire({ icon: 'error', title: errorTitle, text: errorText });
                }
                return;
            }

            if (window.Swal) {
                window.Swal.fire({
                    icon: 'success',
                    title: this.$el.dataset.msgSuccessTitle || 'Success!',
                    text: data.message || this.$el.dataset.msgSuccessText || 'Option saved successfully.',
                    timer: 1800,
                    showConfirmButton: false
                });
            }

            this.resetForm();
            await this.refreshDOM();
        } catch (error) {
            if (window.Swal) {
                window.Swal.fire({
                    icon: 'error',
                    title: this.$el.dataset.msgNetworkErrorTitle || 'Network Error',
                    text: this.$el.dataset.msgNetworkErrorText || 'Could not connect to the server.'
                });
            }
        } finally {
            this.isSubmitting = false;
        }
    },

    // 5. DOM Refresh Handler (sanitize = false)
    async refreshDOM() {
        if (typeof window.updateDOM === 'function') {
            await window.updateDOM(this.$refs, ['parametersTableContent'], () => {
                this.$nextTick(() => {
                    this.initSearch();
                });
            }, false);
        } else {
            this.$nextTick(() => {
                this.initSearch();
            });
        }
    }
});