// resources/js/managers/accessClearanceRoleManager.js
export default () => ({
    // 1. State Properties Upfront (Unified Modals & Search Contract)
    modals: {
        role: {
            open: false,
            mode: 'edit',
            id: '',
            action: '',
            method: 'PUT',
            editRoleId: '',
            editRoleName: '',
            editRolePermissions: []
        }
    },
    createTomSelect: null,
    editTomSelect: null,
    permissionSearch: '',
    roleSearch: '',
    auditSearch: '',
    searchHelperInstance: null,
    filteredIds: null,
    isLoading: false,

    // 2. Lifecycle Hook
    init() {
        this.initSearch();
        this.$nextTick(() => {
            this.initTomSelects();
        });
    },

    // 3. Search & Filtering (Fuse.js Integration)
    initSearch() {
        if (window.Search && this.$el.querySelector('[data-permission-row]')) {
            try {
                this.searchHelperInstance = window.Search.create(
                    '[data-permission-row]',
                    ['name'],
                    { threshold: 0.35 }
                );
            } catch (e) {
                this.searchHelperInstance = null;
            }
        }
    },

    matchesPermissionSearch(name) {
        if (!this.permissionSearch.trim()) return true;
        const fuse = new window.Fuse([{ name }], { keys: ['name'], threshold: 0.35 });
        return fuse.search(this.permissionSearch.trim()).length > 0;
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

    // 4. Modal Handlers (Using window.Modal utility)
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
        const baseUrl = '/admin/roles';
        const data = {
            id: roleId,
            editRoleId: roleId,
            editRoleName: roleName,
            editRolePermissions: permissionIds.map(String)
        };

        window.Modal.edit(this.modals.role, data, (id) => `${baseUrl}/${id}`);
        this.modals.role.editRoleId = roleId;
        this.modals.role.editRoleName = roleName;
        this.modals.role.editRolePermissions = permissionIds.map(String);
        this.modals.role.action = `${baseUrl}/${roleId}`;
        this.modals.role.method = 'PUT';
        this.modals.role.open = true;

        this.$nextTick(() => {
            const editSelectEl = document.getElementById('edit-role-permissions');
            if (editSelectEl && window.Select) {
                window.Select.clear(editSelectEl, true);
                window.Select.setValue(editSelectEl, this.modals.role.editRolePermissions, true);
            }
        });
    },

    closeEditModal() {
        window.Modal.close(this.modals.role);
        this.modals.role.open = false;
        this.modals.role.editRoleId = '';
        this.modals.role.editRoleName = '';
        this.modals.role.editRolePermissions = [];

        const editSelectEl = document.getElementById('edit-role-permissions');
        if (editSelectEl && window.Select) {
            window.Select.clear(editSelectEl, true);
        }
    },

    // 5. DOM Refresh Handler (sanitize = false)
    async handleAjaxSuccess() {
        this.closeEditModal();
        if (this.createTomSelect && window.Select) {
            const createSelectEl = document.getElementById('create-role-permissions');
            if (createSelectEl) window.Select.clear(createSelectEl, true);
        }

        if (typeof window.updateDOM === 'function') {
            await window.updateDOM(this.$refs, ['tableWrapper'], () => {
                this.$nextTick(() => {
                    this.syncTomSelectOptions();
                    this.initSearch();
                });
            }, false);
        } else {
            this.syncTomSelectOptions();
        }
    }
});