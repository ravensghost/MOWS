// resources/js/managers/tableOfContentParametersManager.js
export default () => ({
    formMode: 'create',
    optId: '',
    optType: 'group',
    optValue: '',
    optLabel: '',
    optSortOrder: 0,
    isSubmitting: false,

    searchQuery: '',
    searchHelperInstance: null,
    matchedIds: null,

    init() {
        this.initFuse();
        this.$watch('searchQuery', (val) => {
            if (!val.trim()) {
                this.matchedIds = null;
                return;
            }
            if (this.searchHelperInstance) {
                const results = this.searchHelperInstance.search(val.trim());
                this.matchedIds = results ? results.map(id => Number(id)) : [];
            }
        });
    },

    initFuse() {
        // Leverage the standardized global window.Search helper (powered by Fuse.js)
        if (window.Search) {
            this.searchHelperInstance = window.Search.create('[data-opt-search-item]', ['type', 'value', 'label'], {
                threshold: 0.35
            });
        }
    },

    isOptionVisible(id) {
        if (this.matchedIds === null) return true;
        return this.matchedIds.includes(Number(id));
    },

    async refreshDOM() {
        // Updated to use window.updateDOM with sanitize = false (replacing old _US helper)
        if (typeof window.updateDOM === 'function') {
            await window.updateDOM(this.$refs, ['optionsTableContent'], () => {
                this.$nextTick(() => {
                    this.initFuse();
                });
            }, false);
        } else {
            this.$nextTick(() => {
                this.initFuse();
            });
        }
    },

    resetForm() {
        this.formMode = 'create';
        this.optId = '';
        this.optType = 'group';
        this.optValue = '';
        this.optLabel = '';
        this.optSortOrder = 0;
        this.isSubmitting = false;
    },

    editOption(opt) {
        this.formMode = 'edit';
        this.optId = opt.id;
        this.optType = opt.option_type;
        this.optValue = opt.value;
        this.optLabel = opt.label || '';
        this.optSortOrder = opt.sort_order || 0;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    },

    async submitOption() {
        this.isSubmitting = true;

        const url = this.formMode === 'create'
            ? '/admin/navigation/options'
            : `/admin/navigation/options/${this.optId}`;

        const method = this.formMode === 'create' ? 'POST' : 'PUT';

        const payload = {
            option_type: this.optType,
            value: this.optValue,
            label: this.optLabel || this.optValue,
            sort_order: parseInt(this.optSortOrder, 10) || 0
        };

        const msgSuccessTitle = this.$el.dataset.msgSuccessTitle || 'Success!';
        const msgSuccessText = this.$el.dataset.msgSuccessText || 'Saved successfully.';
        const msgErrorTitle = this.$el.dataset.msgErrorTitle || 'Error';
        const msgValidationFailed = this.$el.dataset.msgValidationFailed || 'Validation failed.';

        try {
            // Leverage standardized window.Fetch helper
            const { ok, data } = await window.Fetch.request(url, method, payload, {
                loading: false,
                showError: false
            });

            if (ok) {
                if (window.Swal && typeof window.Swal.fire === 'function') {
                    window.Swal.fire({
                        icon: 'success',
                        title: msgSuccessTitle,
                        text: data.message || msgSuccessText,
                        showConfirmButton: false,
                        timer: 1500,
                        ...window.Swal.themeConfig()
                    });
                }

                this.resetForm();
                await this.refreshDOM();
            } else {
                let errMsg = msgValidationFailed;
                if (data.errors) {
                    errMsg = Object.values(data.errors).flat().join('<br>');
                } else if (data.message) {
                    errMsg = data.message;
                }

                if (window.Swal && typeof window.Swal.fire === 'function') {
                    window.Swal.fire({
                        icon: 'error',
                        title: msgErrorTitle,
                        html: errMsg,
                        ...window.Swal.themeConfig()
                    });
                }
            }
        } catch (err) {
            // Network errors are captured and handled automatically via window.Fetch
        } finally {
            this.isSubmitting = false;
        }
    }
});