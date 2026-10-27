// resources/js/utility/storage.js

/**
 * Global Local Storage Utility
 * Safe wrappers for persisting state with JSON parsing.
 */
window.appStorage = {
    get(key, fallback = null) {
        try {
            const item = localStorage.getItem(key);
            return item ? JSON.parse(item) : fallback;
        } catch (e) {
            console.error('Storage Read Error:', e);
            return fallback;
        }
    },
    set(key, value) {
        try {
            localStorage.setItem(key, JSON.stringify(value));
        } catch (e) {
            console.error('Storage Write Error:', e);
        }
    },
    remove(key) {
        localStorage.removeItem(key);
    }
};