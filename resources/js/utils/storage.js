/**
 * Global Local Storage Utility
 * Safe wrappers for persisting state with JSON parsing.
 */
window.mowsStorage = {
    get(key, fallback = null) {
        try {
            const item = localStorage.getItem('mows_' + key);
            return item ? JSON.parse(item) : fallback;
        } catch (e) {
            console.error('Storage Read Error:', e);
            return fallback;
        }
    },
    set(key, value) {
        try {
            localStorage.setItem('mows_' + key, JSON.stringify(value));
        } catch (e) {
            console.error('Storage Write Error:', e);
        }
    },
    remove(key) {
        localStorage.removeItem('mows_' + key);
    }
};