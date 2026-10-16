export default () => ({
    modals: {
        form: { open: false, mode: 'create', id: '', name: '', action: '/admin/forms', method: 'POST' },
        field: { open: false, mode: 'create', id: '', name: '', type: 'text', isRequired: false, options: '', formLinked: '', action: '/admin/forms/fields', method: 'POST' }
    },

    async refreshDOM() {
        window.updateDOM(this.$refs, ['formsTableContent', 'fieldsTableContent'], () => {
            this.modals.form.open = false;
            this.modals.field.open = false;
        });
    },

    openForm(mode, data = {}) {
        this.modals.form.mode = mode;
        this.modals.form.id = data.id || '';
        this.modals.form.name = data.name || '';
        this.modals.form.action = mode === 'edit' ? `/admin/forms/${data.id}` : '/admin/forms';
        this.modals.form.method = mode === 'edit' ? 'PUT' : 'POST';
        this.modals.form.open = true;
    },

    openField(mode, data = {}) {
        this.modals.field.mode = mode;
        this.modals.field.id = data.id || '';
        this.modals.field.name = data.name || '';
        this.modals.field.type = data.type || 'text';
        this.modals.field.isRequired = data.isRequired || false;
        this.modals.field.options = data.options || '';
        this.modals.field.formLinked = data.formLinked || '';
        this.modals.field.action = mode === 'edit' ? `/admin/forms/fields/${data.id}` : '/admin/forms/fields';
        this.modals.field.method = mode === 'edit' ? 'PUT' : 'POST';
        this.modals.field.open = true;
    }
});