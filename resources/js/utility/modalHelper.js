// resources/js/utility/modalHelper.js

/**
 * Global Universal Modal Utility
 * Standardises open/close, mode switching (create/edit), form action/method binding,
 * and supports both Single-Modal (root state) and Multi-Modal (this.modals.<key>) architectures.
 */
window.Modal = {
    /**
     * Universal open method supporting both 'create' and 'edit' modes.
     *
     * @param {Object} state - Target state object (`this` for single-modal, or `this.modals[modalKey]` for multi-modal)
     * @param {string} [mode='create'] - 'create' or 'edit'
     * @param {Object} [data={}] - Entity payload for 'edit' mode, or initial overrides for 'create' mode
     * @param {Object} [options={}] - Configuration: { baseUrl: '/admin/...', defaultState: {...}, editAction: (id) => '...' }
     */
    open(state, mode = 'create', data = {}, options = {}) {
        if (!state || typeof state !== 'object') return;

        const baseUrl = options.baseUrl || '';
        const isEdit = mode === 'edit';

        // 1. Set mode and visibility flags (canonical is 'isOpen'; legacy 'open'/'modalOpen' bridged for transition)
        state.mode = mode;
        state.isOpen = true;
        if ('open' in state) state.open = true;
        if ('modalOpen' in state) state.modalOpen = true;

        // 2. Set ID and HTTP method
        state.id = isEdit ? (data.id ?? '') : '';
        const method = isEdit ? 'PUT' : 'POST';
        state.method = method;

        // 3. Resolve Action URL (canonical is 'action'; legacy 'formAction' bridged for transition)
        let actionUrl = baseUrl;
        if (isEdit) {
            if (typeof options.editAction === 'function') {
                actionUrl = options.editAction(data.id, data);
            } else if (options.editAction) {
                actionUrl = `${options.editAction}/${data.id}`;
            } else if (baseUrl) {
                actionUrl = `${baseUrl}/${data.id}`;
            }
        }

        state.action = actionUrl;
        if ('formAction' in state) state.formAction = actionUrl;

        // 4. Populate or reset properties
        const reservedKeys = [
            'isOpen', 'open', 'modalOpen', 'mode', 'action', 'formAction', 'method', 'id',
            'modals', 'searchQuery', 'searchHelperInstance', 'filteredIds', 'isLoading'
        ];

        const defaults = options.defaultState || {};

        if (isEdit) {
            // Reset to defaults first if provided, then overlay incoming edit data
            for (const [key, val] of Object.entries(defaults)) {
                if (!reservedKeys.includes(key)) {
                    state[key] = typeof val === 'function' ? val() : val;
                }
            }
            for (const key of Object.keys(state)) {
                if (!reservedKeys.includes(key) && data[key] !== undefined) {
                    state[key] = data[key];
                }
            }
        } else {
            // Apply defaultState, then allow optional create-mode overrides passed via data (e.g. parent_id or shelf_id)
            const mergedCreateState = { ...defaults, ...data };
            for (const [key, val] of Object.entries(mergedCreateState)) {
                if (key in state && !reservedKeys.includes(key)) {
                    state[key] = typeof val === 'function' ? val() : val;
                }
            }
        }

        if (typeof options.onOpen === 'function') {
            options.onOpen(state, mode, data);
        }
    },

    /**
     * Shorthand for opening in 'create' mode.
     */
    create(stateRef, defaultState = {}, createAction = '') {
        this.open(stateRef, 'create', defaultState, { baseUrl: createAction, defaultState });
    },

    /**
     * Shorthand for opening in 'edit' mode.
     */
    edit(stateRef, data = {}, editActionPattern = '', defaultState = {}) {
        const editAction = typeof editActionPattern === 'function' ? editActionPattern : null;
        const baseUrl = typeof editActionPattern === 'string' ? editActionPattern : '';
        this.open(stateRef, 'edit', data, { baseUrl, editAction, defaultState });
    },

    /**
     * Closes a single modal state object, a specific modal inside `this.modals[key]`,
     * or ALL modals if `this.modals` (or component `this` containing `modals`) is passed.
     */
    close(stateOrComponent, modalKey = null) {
        if (!stateOrComponent || typeof stateOrComponent !== 'object') return;

        const closeTarget = (target) => {
            if (!target || typeof target !== 'object') return;
            if ('isOpen' in target) target.isOpen = false;
            if ('open' in target) target.open = false;
            if ('modalOpen' in target) target.modalOpen = false;
        };

        // Case 1: Specific modalKey requested on a component or modals map
        if (modalKey) {
            if (stateOrComponent.modals && stateOrComponent.modals[modalKey]) {
                closeTarget(stateOrComponent.modals[modalKey]);
                return;
            }
            if (stateOrComponent[modalKey]) {
                closeTarget(stateOrComponent[modalKey]);
                return;
            }
        }

        // Case 2: Component root passed — close root flags AND any nested this.modals
        closeTarget(stateOrComponent);

        if (stateOrComponent.modals && typeof stateOrComponent.modals === 'object') {
            Object.values(stateOrComponent.modals).forEach(subModal => closeTarget(subModal));
        } else {
            // Case 3: `this.modals` object passed directly
            Object.values(stateOrComponent).forEach(val => {
                if (val && typeof val === 'object' && ('isOpen' in val || 'open' in val || 'modalOpen' in val)) {
                    closeTarget(val);
                }
            });
        }
    }
};

export default window.Modal;