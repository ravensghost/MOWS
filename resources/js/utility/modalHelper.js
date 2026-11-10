// resources/js/utility/modalHelper.js

/**
 * Global Universal Modal Utility
 * Standardises open/close, mode switching (create/edit), form binding, 
 * and automatically bridges property naming differences (isOpen/open/modalOpen, action/formAction).
 */
window.Modal = {
    /**
     * Universal open method supporting both create and edit modes.
     * 
     * @param {Object} state - The modal state object or component reference (this or this.wikiModal)
     * @param {string} mode - 'create' or 'edit'
     * @param {Object} [data={}] - Incoming data for edit mode, or defaults for create mode
     * @param {Object} [options={}] - Configuration options (baseUrl, defaultState, editAction)
     */
    open(state, mode = 'create', data = {}, options = {}) {
        const baseUrl = options.baseUrl || '';
        const isEdit = mode === 'edit';

        // 1. Set mode and visibility flags (supporting 'isOpen', 'open', and 'modalOpen')
        state.mode = mode;
        if ('isOpen' in state) state.isOpen = true;
        if ('open' in state) state.open = true;
        if ('modalOpen' in state) state.modalOpen = true;

        // 2. Set ID and HTTP method
        state.id = isEdit ? (data.id || '') : '';
        const method = isEdit ? 'PUT' : 'POST';
        if ('method' in state) state.method = method;

        // 3. Resolve Action URL (supporting both 'action' and 'formAction')
        let actionUrl = baseUrl;
        if (isEdit) {
            if (typeof options.editAction === 'function') {
                actionUrl = options.editAction(data.id);
            } else if (options.editAction) {
                actionUrl = `${options.editAction}/${data.id}`;
            } else if (baseUrl) {
                actionUrl = `${baseUrl}/${data.id}`;
            }
        }

        if ('action' in state) state.action = actionUrl;
        if ('formAction' in state) state.formAction = actionUrl;

        // 4. Populate or reset properties
        const reservedKeys = ['isOpen', 'open', 'modalOpen', 'mode', 'action', 'formAction', 'method', 'id'];
        if (isEdit) {
            for (const key of Object.keys(state)) {
                if (!reservedKeys.includes(key)) {
                    if (data[key] !== undefined) {
                        state[key] = data[key];
                    }
                }
            }
        } else {
            const defaults = options.defaultState || data;
            for (const [key, val] of Object.entries(defaults)) {
                if (key in state && !reservedKeys.includes(key)) {
                    state[key] = typeof val === 'function' ? val() : val;
                }
            }
        }
    },

    create(stateRef, defaultState = {}, createAction = '') {
        this.open(stateRef, 'create', defaultState, { baseUrl: createAction, defaultState });
    },

    edit(stateRef, data = {}, editActionPattern = '') {
        const editAction = typeof editActionPattern === 'function' ? editActionPattern : null;
        const baseUrl = typeof editActionPattern === 'string' ? editActionPattern : '';
        this.open(stateRef, 'edit', data, { baseUrl, editAction });
    },

    close(state) {
        if ('isOpen' in state) state.isOpen = false;
        if ('open' in state) state.open = false;
        if ('modalOpen' in state) state.modalOpen = false;
    }
};

export default window.Modal;