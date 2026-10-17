export default () => ({
    modals: {
        form: { open: false, mode: 'create', id: '', name: '', action: '/admin/forms', method: 'POST' },
        field: {
            open: false,
            mode: 'create',
            id: '',
            name: '',
            type: 'text',
            isRequired: false,
            options: '',
            formLinked: '',
            serviceRecordGroups: [],
            action: '/admin/forms/fields',
            method: 'POST'
        }
    },

    async refreshDOM() {
        // Close modals immediately on success
        this.modals.form.open = false;
        this.modals.field.open = false;

        if (typeof window.updateDOM === 'function') {
            window.updateDOM(this.$refs, ['formsTableContent', 'fieldsTableContent']);
        }
    },

    // Alias in case a Blade view calls refreshDOM_US()
    async refreshDOM_US() {
        return this.refreshDOM();
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

        // Parse service record optgroups from options if field type is service_record
        if (data.type === 'service_record') {
            try {
                let parsed = typeof data.options === 'string' ? JSON.parse(data.options) : data.options;
                this.modals.field.serviceRecordGroups = Array.isArray(parsed) ? parsed : [];
            } catch (e) {
                this.modals.field.serviceRecordGroups = [];
            }
        } else {
            this.modals.field.serviceRecordGroups = [];
        }

        this.modals.field.action = mode === 'edit' ? `/admin/forms/fields/${data.id}` : '/admin/forms/fields';
        this.modals.field.method = mode === 'edit' ? 'PUT' : 'POST';
        this.modals.field.open = true;
    }
});