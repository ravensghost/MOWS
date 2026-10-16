/**
 * Global Debounce Utility
 * Delays invoking a function until after 'wait' milliseconds have elapsed 
 * since the last time it was invoked.
 */
window.debounce = function (func, wait = 300) {
    let timeout;
    return function (...args) {
        const context = this;
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(context, args), wait);
    };
};