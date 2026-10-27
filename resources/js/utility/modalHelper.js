// resources/js/utility/modalHelper.js

/**
 * Global Modal State Utility
 * Standardises open/close, mode switching (create/edit), and form binding.
 */
window.Modal = {
    create(modalStateRef, defaultState = {}, createAction = '') {
        modalStateRef.open = true;
        modalStateRef.mode = 'create';
        modalStateRef.id = '';
        modalStateRef.action = createAction;
        modalStateRef.method = 'POST';

        // Apply clean defaults
        for (const [key, val] of Object.entries(defaultState)) {
            modalStateRef[key] = typeof val === 'function' ? val() : val;
        }
    },
    edit(modalStateRef, data = {}, editActionPattern = '') {
        modalStateRef.open = true;
        modalStateRef.mode = 'edit';
        modalStateRef.id = data.id || '';
        modalStateRef.action = typeof editActionPattern === 'function'
            ? editActionPattern(data.id)
            : `${editActionPattern}/${data.id}`;
        modalStateRef.method = 'PUT';

        // Map incoming data properties directly to state
        for (const key of Object.keys(modalStateRef)) {
            if (key !== 'open' && key !== 'mode' && key !== 'action' && key !== 'method') {
                if (data[key] !== undefined) {
                    modalStateRef[key] = data[key];
                }
            }
        }
    },
    close(modalStateRef) {
        modalStateRef.open = false;
    }
};