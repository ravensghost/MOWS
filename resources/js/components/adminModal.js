export default () => ({
    modals: {
        form: { open: false, mode: 'create', id: '', name: '', is_visible: true, action: '/admin/forms', method: 'POST' },
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

    async refreshDOM() {
        this.modals.form.open = false;
        this.modals.field.open = false;

        if (typeof window.updateDOM_US === 'function') {
            window.updateDOM_US(this.$refs, ['formsTableContent', 'fieldsTableContent']);
        } else if (typeof window.updateDOM === 'function') {
            window.updateDOM(this.$refs, ['formsTableContent', 'fieldsTableContent']);
        }
    },

    async refreshDOM_US() {
        return this.refreshDOM();
    },

    openForm(mode, data = {}) {
        this.modals.form.mode = mode;
        this.modals.form.id = data.id || '';
        this.modals.form.name = data.name || '';
        this.modals.form.is_visible = data.is_visible !== undefined ? Boolean(data.is_visible) : true;
        this.modals.form.action = mode === 'edit' ? `/admin/forms/${data.id}` : '/admin/forms';
        this.modals.form.method = mode === 'edit' ? 'PUT' : 'POST';
        this.modals.form.open = true;
    },

    openField(mode, data = {}) {
        this.modals.field.mode = mode;
        this.modals.field.id = data.id || '';
        this.modals.field.name = data.name || '';
        this.modals.field.type = data.type || 'text';
        this.modals.field.isRequired = Boolean(data.isRequired);
        this.modals.field.is_visible = data.is_visible !== undefined ? Boolean(data.is_visible) : true;
        this.modals.field.options = data.options || '';
        this.modals.field.formLinked = data.formLinked || '';

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
        this.modals.field.action = mode === 'edit' ? `/admin/forms/fields/${data.id}` : '/admin/forms/fields';
        this.modals.field.method = mode === 'edit' ? 'PUT' : 'POST';
        this.modals.field.open = true;
    }
});