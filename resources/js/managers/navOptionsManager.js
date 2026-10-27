import Fuse from 'fuse.js';

export default () => ({
    formMode: 'create',
    optId: '',
    optType: 'group',
    optValue: '',
    optLabel: '',
    optSortOrder: 0,
    isSubmitting: false,

    searchQuery: '',
    fuse: null,
    matchedIds: null,

    init() {
        this.initFuse();
        this.$watch('searchQuery', (val) => {
            if (!val.trim()) {
                this.matchedIds = null;
                return;
            }
            if (this.fuse) {
                this.matchedIds = this.fuse.search(val.trim()).map(res => Number(res.item.id));
            }
        });
    },

    initFuse() {
        const rows = Array.from(document.querySelectorAll('[data-opt-search-item]'));
        const items = rows.map(row => ({
            id: Number(row.dataset.id),
            type: row.dataset.type || '',
            value: row.dataset.value || '',
            label: row.dataset.label || ''
        }));

        this.fuse = new Fuse(items, {
            keys: ['type', 'value', 'label'],
            threshold: 0.35,
            ignoreLocation: true
        });
    },

    isOptionVisible(id) {
        if (this.matchedIds === null) return true;
        return this.matchedIds.includes(Number(id));
    },

    async refreshDOM() {
        await window.updateDOM_US(this.$refs, ['optionsTableContent']);
        this.$nextTick(() => {
            this.initFuse();
        });
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

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            || document.querySelector('input[name="_token"]')?.value;

        const url = this.formMode === 'create'
            ? '/admin/navigation/options'
            : `/admin/navigation/options/${this.optId}`;

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
        const msgNetworkErrorTitle = this.$el.dataset.msgNetworkErrorTitle || 'Network Error';
        const msgNetworkErrorText = this.$el.dataset.msgNetworkErrorText || 'Could not connect to the server.';

        try {
            const response = await fetch(url, {
                method: this.formMode === 'create' ? 'POST' : 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            const data = await response.json();

            if (response.ok) {
                window.Swal.fire({
                    icon: 'success',
                    title: msgSuccessTitle,
                    text: data.message || msgSuccessText,
                    showConfirmButton: false,
                    timer: 1500,
                    background: 'var(--theme-bg-panel)',
                    color: 'var(--theme-text-p)'
                });

                this.resetForm();
                await this.refreshDOM();
            } else {
                let errMsg = msgValidationFailed;
                if (data.errors) {
                    errMsg = Object.values(data.errors).flat().join('<br>');
                } else if (data.message) {
                    errMsg = data.message;
                }

                window.Swal.fire({
                    icon: 'error',
                    title: msgErrorTitle,
                    html: errMsg,
                    background: 'var(--theme-bg-panel)',
                    color: 'var(--theme-text-p)'
                });
            }
        } catch (err) {
            window.Swal.fire({
                icon: 'error',
                title: msgNetworkErrorTitle,
                text: msgNetworkErrorText,
                background: 'var(--theme-bg-panel)',
                color: 'var(--theme-text-p)'
            });
        } finally {
            this.isSubmitting = false;
        }
    }
});