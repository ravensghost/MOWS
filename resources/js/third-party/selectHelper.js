// resources/js/third-party/selectHelper.js
import TomSelect from 'tom-select';

/**
 * Global Select Helper Wrapper using Tom Select
 */
window.Select = {
    instances: {},

    init(selectorOrEl, options = {}) {
        const el = typeof selectorOrEl === 'string' ? document.querySelector(selectorOrEl) : selectorOrEl;
        if (!el) return null;

        // Clean up pre-existing instance on the element
        if (el.tomselect) {
            el.tomselect.destroy();
        }

        const defaultOptions = {
            create: false,
            sortField: {
                field: 'text',
                direction: 'asc'
            },
            ...options
        };

        const instance = new TomSelect(el, defaultOptions);

        if (el.id) {
            this.instances[el.id] = instance;
        }

        return instance;
    },

    destroy(selectorOrEl) {
        const el = typeof selectorOrEl === 'string' ? document.querySelector(selectorOrEl) : selectorOrEl;
        if (el && el.tomselect) {
            el.tomselect.destroy();
            if (el.id && this.instances[el.id]) {
                delete this.instances[el.id];
            }
        }
    }
};

window.TomSelect = TomSelect;

export default TomSelect;