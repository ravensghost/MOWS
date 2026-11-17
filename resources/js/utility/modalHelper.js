// resources/js/utility/modalHelper.js

/**
 * Global Universal Modal Utility
 * Standardises open/close, mode switching (create/edit), form action/method binding,
 * and supports the unified this.modals.<key> state contract across all Alpine managers.
 */
window.Modal = {
    defaultState: {},

    /**
     * Universal open method supporting both 'create' and 'edit' modes.
     * @param {Object} state - Target modal state object (this.modals[modalKey])
     * @param {string} [mode='create'] - 'create' or 'edit'
     * @param {Object} [data={}] - Entity payload for 'edit' mode, or initial overrides for 'create' mode
     * @param {Object} [options={}] - Configuration: { baseUrl: '/admin/...', defaultState: {...}, editAction: (id) => '...' }
     */
    open(state, mode = 'create', data = {}, options = {}) {
        if (!state || typeof state !== 'object') return;

        const baseUrl = options.baseUrl || '';
        const isEdit = mode === 'edit';

        // 1. Set mode and synchronise visibility flags ('isOpen' and 'open')
        state.mode = mode;
        state.isOpen = true;
        state.open = true;
        if ('modalOpen' in state) {
            state.modalOpen = true;
        }

        // 2. Set ID and HTTP method
        state.id = isEdit ? (data.id ?? '') : '';
        state.method = isEdit ? (options.method || 'PUT') : (options.method || 'POST');

        // 3. Resolve Action URL
        let actionUrl = baseUrl;
        if (isEdit) {
            const recordId = data.id ?? data.code ?? '';
            if (typeof options.editAction === 'function') {
                actionUrl = options.editAction(recordId, data);
            } else if (options.editAction) {
                actionUrl = `${options.editAction}/${recordId}`;
            } else if (baseUrl && recordId !== '') {
                actionUrl = `${baseUrl}/${recordId}`;
            }
        }

        state.action = actionUrl;
        if ('formAction' in state) {
            state.formAction = actionUrl;
        }

        // 4. Populate or reset properties
        const reservedKeys = [
            'isOpen', 'open', 'modalOpen', 'mode', 'action', 'formAction', 'method',
            'modals', 'searchQuery', 'searchHelperInstance', 'filteredIds', 'isLoading'
        ];

        const defaults = options.defaultState || {};

        if (isEdit) {
            for (const [key, val] of Object.entries(defaults)) {
                if (!reservedKeys.includes(key)) {
                    state[key] = typeof val === 'function' ? val() : val;
                }
            }
            for (const [key, val] of Object.entries(data)) {
                if (!reservedKeys.includes(key) && val !== undefined) {
                    state[key] = val;
                }
            }
        } else {
            const mergedCreateState = { ...defaults, ...data };
            for (const [key, val] of Object.entries(mergedCreateState)) {
                if (!reservedKeys.includes(key)) {
                    state[key] = typeof val === 'function' ? val() : val;
                }
            }
        }

        if (typeof options.onOpen === 'function') {
            options.onOpen(state, mode, data);
        }
    },

    /**
     * Shorthand for opening a modal in 'create' mode.
     */
    create(stateRef, defaultState = {}, createAction = '') {
        this.open(stateRef, 'create', defaultState, {
            baseUrl: createAction,
            defaultState
        });
    },

    /**
     * Shorthand for opening a modal in 'edit' mode.
     */
    edit(stateRef, data = {}, editActionPattern = '', defaultState = {}) {
        const editAction = typeof editActionPattern === 'function' ? editActionPattern : null;
        const baseUrl = typeof editActionPattern === 'string' ? editActionPattern : '';
        this.open(stateRef, 'edit', data, {
            baseUrl,
            editAction,
            defaultState
        });
    },

    /**
     * Closes a single modal state object, a specific modal inside this.modals[key],
     * or all modals if a parentmodals object/component is passed.
     */
    close(stateOrComponent, modalKey = null) {
        if (!stateOrComponent || typeof stateOrComponent !== 'object') return;

        const closeTarget = (target) => {
            if (!target || typeof target !== 'object') return;
            target.isOpen = false;
            target.open = false;
            if ('modalOpen' in target) {
                target.modalOpen = false;
            }
        };

        if (modalKey && stateOrComponent.modals && stateOrComponent.modals[modalKey]) {
            closeTarget(stateOrComponent.modals[modalKey]);
            return;
        }

        if (modalKey && stateOrComponent[modalKey]) {
            closeTarget(stateOrComponent[modalKey]);
            return;
        }

        if (stateOrComponent.modals && typeof stateOrComponent.modals === 'object') {
            Object.values(stateOrComponent.modals).forEach(closeTarget);
            closeTarget(stateOrComponent);
            return;
        }

        if ('isOpen' in stateOrComponent || 'open' in stateOrComponent || 'mode' in stateOrComponent) {
            closeTarget(stateOrComponent);
            return;
        }

        Object.values(stateOrComponent).forEach(closeTarget);
    }
};

export default window.Modal;