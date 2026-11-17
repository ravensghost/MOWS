// resources/js/managers/accessClearanceRoleManager.js

export default function accessClearanceRoleManager() {
    return {
        // 1. State Properties Upfront (Unified Modals & Search Contract)
        modals: {
            role: {
                isOpen: false,
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
        roleSearchInstance: null,
        auditSearchInstance: null,
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
            if (!window.Search) return;

            if (this.$el && this.$el.querySelector('[data-permission-row]')) {
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

            if (this.$el && this.$el.querySelector('[data-role-row]')) {
                try {
                    this.roleSearchInstance = window.Search.create(
                        '[data-role-row]',
                        ['name', 'permissions'],
                        { threshold: 0.35 }
                    );
                } catch (e) {
                    this.roleSearchInstance = null;
                }
            }

            if (this.$el && this.$el.querySelector('[data-audit-row]')) {
                try {
                    this.auditSearchInstance = window.Search.create(
                        '[data-audit-row]',
                        ['key', 'url', 'location', 'visibility', 'perm'],
                        { threshold: 0.35 }
                    );
                } catch (e) {
                    this.auditSearchInstance = null;
                }
            }
        },

        matchesPermissionSearch(name) {
            const q = this.permissionSearch.trim().toLowerCase();
            if (!q) return true;
            return String(name || '').toLowerCase().includes(q);
        },

        matchesRoleSearch(name, permissions) {
            const q = this.roleSearch.trim().toLowerCase();
            if (!q) return true;
            return String(name || '').toLowerCase().includes(q)
                || String(permissions || '').toLowerCase().includes(q);
        },

        matchesAuditSearch(key, url, location, visibility, perm) {
            const q = this.auditSearch.trim().toLowerCase();
            if (!q) return true;
            return [key, url, location, visibility, perm].some(val =>
                String(val || '').toLowerCase().includes(q)
            );
        },

        isItemVisible(id) {
            if (!this.permissionSearch.trim() || !this.searchHelperInstance) return true;
            const results = this.searchHelperInstance.search(this.permissionSearch.trim()) || [];
            return results.map(Number).includes(Number(id));
        },

        // 4. Modal & Action Handlers (Using window.Modal & window.Select utilities)
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
                const permissions = JSON.parse(payloadEl.textContent || '[]');
                const options = permissions.map(p => ({ value: String(p.id), text: p.name }));

                ['#create-role-permissions', '#edit-role-permissions'].forEach(selector => {
                    const el = document.querySelector(selector);
                    if (el) {
                        window.Select.syncOptions(el, options);
                    }
                });
            } catch (e) {
                // Ignore malformed JSON payload
            }
        },

        openEditModal(roleId, roleName, permissionIds = []) {
            const baseUrl = '/admin/roles';
            const normalisedPermissions = Array.isArray(permissionIds) ? permissionIds.map(String) : [];

            window.Modal.edit(this.modals.role, {
                id: roleId,
                editRoleId: roleId,
                editRoleName: roleName,
                editRolePermissions: normalisedPermissions
            }, (id) => `${baseUrl}/${id}`);

            this.$nextTick(() => {
                const editSelectEl = document.getElementById('edit-role-permissions');
                if (editSelectEl && window.Select) {
                    window.Select.clear(editSelectEl, true);
                    window.Select.setValue(editSelectEl, normalisedPermissions, true);
                }
            });
        },

        closeEditModal() {
            window.Modal.close(this.modals.role);
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
            await this.refreshDOM();
        },

        async refreshDOM() {
            this.closeEditModal();
            const createSelectEl = document.getElementById('create-role-permissions');
            if (createSelectEl && window.Select) {
                window.Select.clear(createSelectEl, true);
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
    };
}