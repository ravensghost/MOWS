export default () => ({
    showEditModal: false,
    editRoleId: '',
    editRoleName: '',
    editRolePermissions: [],
    createTomSelect: null,
    editTomSelect: null,
    permissionSearch: '',
    roleSearch: '',
    auditSearch: '',

    init() {
        this.$nextTick(() => {
            this.initTomSelects();
        });
    },

    initTomSelects() {
        const createSelectEl = document.getElementById('create-role-permissions');
        if (createSelectEl && !this.createTomSelect) {
            this.createTomSelect = new window.TomSelect(createSelectEl, {
                plugins: ['remove_button']
            });
        }

        const editSelectEl = document.getElementById('edit-role-permissions');
        if (editSelectEl && !this.editTomSelect) {
            this.editTomSelect = new window.TomSelect(editSelectEl, {
                plugins: ['remove_button']
            });
        }
    },

    syncTomSelectOptions() {
        const payloadEl = document.querySelector('.permissions-json-payload');
        if (!payloadEl) return;

        try {
            const permissions = JSON.parse(payloadEl.textContent);
            const options = permissions.map(p => ({ value: String(p.id), text: p.name }));

            [this.createTomSelect, this.editTomSelect].forEach(ts => {
                if (ts) {
                    const currentValues = ts.getValue();
                    ts.clearOptions();
                    ts.addOptions(options);
                    ts.refreshOptions(false);
                    if (Array.isArray(currentValues) && currentValues.length > 0) {
                        ts.setValue(currentValues, true);
                    }
                }
            });
        } catch (e) {
            // Silently ignore malformed JSON payload
        }
    },

    openEditModal(roleId, roleName, permissionIds) {
        this.editRoleId = roleId;
        this.editRoleName = roleName;
        this.editRolePermissions = permissionIds.map(String);
        this.showEditModal = true;

        this.$nextTick(() => {
            if (this.editTomSelect) {
                this.editTomSelect.clear(true);
                this.editTomSelect.setValue(this.editRolePermissions, true);
            }
        });
    },

    closeEditModal() {
        this.showEditModal = false;
        this.editRoleId = '';
        this.editRoleName = '';
        this.editRolePermissions = [];
        if (this.editTomSelect) {
            this.editTomSelect.clear(true);
        }
    },

    matchesPermissionSearch(name) {
        if (!this.permissionSearch.trim()) return true;
        const list = [{ name }];
        const fuse = new window.Fuse(list, {
            keys: ['name'],
            threshold: 0.35
        });
        return fuse.search(this.permissionSearch.trim()).length > 0;
    },

    matchesRoleSearch(name, permissions) {
        if (!this.roleSearch.trim()) return true;
        const list = [{ name, permissions }];
        const fuse = new window.Fuse(list, {
            keys: ['name', 'permissions'],
            threshold: 0.35
        });
        return fuse.search(this.roleSearch.trim()).length > 0;
    },

    matchesAuditSearch(key, url, location, visibility, perm) {
        if (!this.auditSearch.trim()) return true;
        const list = [{ key, url, location, visibility, perm }];
        const fuse = new window.Fuse(list, {
            keys: ['key', 'url', 'location', 'visibility', 'perm'],
            threshold: 0.35
        });
        return fuse.search(this.auditSearch.trim()).length > 0;
    },

    async handleAjaxSuccess() {
        this.closeEditModal();
        if (this.createTomSelect) {
            this.createTomSelect.clear(true);
        }
        await window.refreshDOM_US('.table-wrapper');
        this.syncTomSelectOptions();
    }
});