// resources/js/third-party/flatpickrHelper.js
import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.min.css';

/**
 * Global Flatpickr Helper Utility
 * Standardises date and time picker initialisation.
 */
window.Flatpickr = {
    init(el, config = {}) {
        if (!el) return null;
        if (el._flatpickr) {
            el._flatpickr.destroy();
        }
        return flatpickr(el, {
            dateFormat: 'Y-m-d',
            ...config
        });
    },
    destroy(el) {
        if (el && el._flatpickr) {
            el._flatpickr.destroy();
        }
    }
};

/**
 * Custom Alpine Directive: x-flatpickr
 */
document.addEventListener('alpine:init', () => {
    window.Alpine.directive('flatpickr', (el, { expression }, { evaluate }) => {
        const config = evaluate(expression) || {};
        const instance = flatpickr(el, {
            dateFormat: 'Y-m-d',
            ...config
        });
        el._flatpickr = instance;
    });
});