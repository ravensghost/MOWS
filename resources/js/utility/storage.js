// resources/js/utility/storage.js

/**
 * Global Local Storage Utility
 * Safe wrappers for persisting zero-DB client state (scroll percentages, read badges, soundtrack settings) with JSON parsing.
 */
window.Storage = {
    get(key, fallback = null) {
        try {
            const item = localStorage.getItem(key);
            return item !== null ? JSON.parse(item) : fallback;
        } catch (e) {
            console.error('[Storage] Read Error:', e);
            return fallback;
        }
    },

    set(key, value) {
        try {
            localStorage.setItem(key, JSON.stringify(value));
        } catch (e) {
            console.error('[Storage] Write Error:', e);
        }
    },

    remove(key) {
        try {
            localStorage.removeItem(key);
        } catch (e) {
            console.error('[Storage] Remove Error:', e);
        }
    }
};

window.appStorage = window.Storage;

export default window.Storage;