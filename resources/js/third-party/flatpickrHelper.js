// resources/js/third-party/flatpickrHelper.js
import flatpickr from 'flatpickr';

/**
 * Global Flatpickr Helper Wrapper
 */
window.Flatpickr = {
    instances: {},

    init(selectorOrEl, options = {}) {
        const elements = typeof selectorOrEl === 'string'
            ? document.querySelectorAll(selectorOrEl)
            : (selectorOrEl instanceof HTMLElement ? [selectorOrEl] : []);

        if (elements.length === 0) return null;

        const defaultOptions = {
            enableTime: true,
            dateFormat: 'Y-m-d H:i',
            ...options
        };

        const createdInstances = [];
        elements.forEach((el, index) => {
            // Clean up existing instance if attached
            if (el._flatpickr) {
                el._flatpickr.destroy();
            }

            const instance = flatpickr(el, defaultOptions);
            createdInstances.push(instance);

            if (el.id) {
                this.instances[el.id] = instance;
            }
        });

        return createdInstances.length === 1 ? createdInstances[0] : createdInstances;
    },

    destroy(selectorOrEl) {
        const elements = typeof selectorOrEl === 'string'
            ? document.querySelectorAll(selectorOrEl)
            : (selectorOrEl instanceof HTMLElement ? [selectorOrEl] : []);

        elements.forEach(el => {
            if (el._flatpickr) {
                el._flatpickr.destroy();
                if (el.id && this.instances[el.id]) {
                    delete this.instances[el.id];
                }
            }
        });
    }
};

window.flatpickr = flatpickr;

export default flatpickr;