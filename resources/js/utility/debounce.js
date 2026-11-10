// resources/js/utils/debounce.js

/**
 * Global Debounce Utility
 * Delays invoking a function until after 'wait' milliseconds have elapsed since the last time it was invoked.
 */
window.debounce = function (func, wait = 300) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
};

export default window.debounce;