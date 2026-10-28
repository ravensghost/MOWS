// resources/js/managers/accessClearanceRoleManager.js
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
    roleSearchHelper: null,
    permissionSearchHelper: null,
    auditSearchHelper: null,

    init() {
        this.$nextTick(() => {
            this.initTomSelects();
        });
    },

    initTomSelects() {
        const createSelectEl = document.getElementById('create-role-permissions');
        if (createSelectEl && !this.createTomSelect && window.Select) {
            this.createTomSelect = window.Select.init(createSelectEl, {
                plugins: ['remove_button']
            });
        }

        const editSelectEl = document.getElementById('edit-role-permissions');
        if (editSelectEl && !this.editTomSelect && window.Select) {
            this.editTomSelect = window.Select.init(editSelectEl, {
                plugins: ['remove_button']
            });
        }
    },

    syncTomSelectOptions() {
        const payloadEl = document.querySelector('.permissions-json-payload');
        if (!payloadEl || !window.Select) return;

        try {
            const permissions = JSON.parse(payloadEl.textContent);
            const options = permissions.map(p => ({ value: String(p.id), text: p.name }));

            [this.createTomSelect, this.editTomSelect].forEach(ts => {
                if (ts && ts.wrapper && ts.wrapper.tomselect) {
                    const el = ts.wrapper.tomselect.input;
                    if (el) {
                        const currentValues = el.tomselect.getValue();
                        window.Select.syncOptions(el, options);
                        if (Array.isArray(currentValues) && currentValues.length > 0) {
                            window.Select.setValue(el, currentValues, true);
                        }
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
            const editSelectEl = document.getElementById('edit-role-permissions');
            if (editSelectEl && window.Select) {
                window.Select.clear(editSelectEl, true);
                window.Select.setValue(editSelectEl, this.editRolePermissions, true);
            }
        });
    },

    closeEditModal() {
        this.showEditModal = false;
        this.editRoleId = '';
        this.editRoleName = '';
        this.editRolePermissions = [];

        const editSelectEl = document.getElementById('edit-role-permissions');
        if (editSelectEl && window.Select) {
            window.Select.clear(editSelectEl, true);
        }
    },

    matchesPermissionSearch(name) {
        if (!this.permissionSearch.trim()) return true;
        // Leverage standardized window.Search helper or local instance
        const searchHelper = window.Search ? window.Search.create('[data-permission-row]', ['name'], { threshold: 0.35 }) : null;
        if (searchHelper) {
            // Fallback lightweight check if selector isn't mapped, or evaluate directly:
            const fuse = new window.Fuse([{ name }], { keys: ['name'], threshold: 0.35 });
            return fuse.search(this.permissionSearch.trim()).length > 0;
        }
        return true;
    },

    matchesRoleSearch(name, permissions) {
        if (!this.roleSearch.trim()) return true;
        const fuse = new window.Fuse([{ name, permissions }], { keys: ['name', 'permissions'], threshold: 0.35 });
        return fuse.search(this.roleSearch.trim()).length > 0;
    },

    matchesAuditSearch(key, url, location, visibility, perm) {
        if (!this.auditSearch.trim()) return true;
        const fuse = new window.Fuse([{ key, url, location, visibility, perm }], { keys: ['key', 'url', 'location', 'visibility', 'perm'], threshold: 0.35 });
        return fuse.search(this.auditSearch.trim()).length > 0;
    },

    async handleAjaxSuccess() {
        this.closeEditModal();
        if (this.createTomSelect && window.Select) {
            const createSelectEl = document.getElementById('create-role-permissions');
            if (createSelectEl) window.Select.clear(createSelectEl, true);
        }

        // Updated to use refreshDOM with sanitize = false (replacing old _US helper)
        if (typeof window.refreshDOM === 'function') {
            await window.refreshDOM('.table-wrapper', () => {
                this.syncTomSelectOptions();
            }, false);
        } else {
            this.syncTomSelectOptions();
        }
    }
});