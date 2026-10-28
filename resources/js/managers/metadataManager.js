// resources/js/managers/metadataManager.js
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

        // Updated to use window.updateDOM with sanitize = false (replacing old _US helper)
        if (typeof window.updateDOM === 'function') {
            await window.updateDOM(this.$refs, ['formsTableContent', 'fieldsTableContent'], null, false);
        }
    },

    async refreshDOM_US() {
        return this.refreshDOM();
    },

    openForm(mode, data = {}) {
        if (mode === 'create') {
            window.Modal.create(this.modals.form, { is_visible: true }, '/admin/forms');
        } else {
            window.Modal.edit(this.modals.form, data, (id) => `/admin/forms/${id}`);
        }
    },

    openField(mode, data = {}) {
        if (mode === 'create') {
            window.Modal.create(this.modals.field, { type: 'text', isRequired: false, is_visible: true, serviceRecordGroups: [] }, '/admin/forms/fields');
        } else {
            window.Modal.edit(this.modals.field, data, (id) => `/admin/forms/fields/${id}`);
        }

        // Domain-specific serviceRecordGroups parsing logic handled cleanly here
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
    }
});