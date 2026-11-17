// resources/js/third-party/selectHelper.js
import TomSelect from 'tom-select';
import 'tom-select/dist/css/tom-select.default.css';

/**
 * Global Tom Select Helper Wrapper
 * Standardises searchable selects, multi-select tags, and dynamic option syncing across Alpine managers.
 */
window.Select = {
    instances: {},

    /**
     * Resolve a selector string, HTMLElement, or TomSelect instance into the underlying DOM element.
     */
    resolveElement(selectorOrEl) {
        if (!selectorOrEl) return null;
        if (typeof selectorOrEl === 'string') {
            return document.querySelector(selectorOrEl);
        }
        if (selectorOrEl instanceof HTMLElement) {
            return selectorOrEl;
        }
        if (selectorOrEl.input instanceof HTMLElement) {
            return selectorOrEl.input;
        }
        return null;
    },

    /**
     * Retrieve the active TomSelect instance for a given selector or element.
     */
    getInstance(selectorOrEl) {
        if (!selectorOrEl) return null;
        if (selectorOrEl instanceof TomSelect) {
            return selectorOrEl;
        }
        const el = this.resolveElement(selectorOrEl);
        if (!el) return null;

        if (el.tomselect) {
            return el.tomselect;
        }

        const key = el.id || el.getAttribute('name');
        return key && this.instances[key] ? this.instances[key] : null;
    },

    /**
     * Initialise a Tom Select instance on a <select> or <input> element.
     */
    init(selectorOrEl, options = {}) {
        const el = this.resolveElement(selectorOrEl);
        if (!el) return null;

        // Destroy any existing instance on this element before re-initialising
        if (el.tomselect) {
            el.tomselect.destroy();
        }

        const isMultiple = el.multiple || el.hasAttribute('multiple');
        const defaultPlugins = isMultiple ? ['remove_button'] : [];

        const defaultOptions = {
            plugins: options.plugins || defaultPlugins,
            create: false,
            persist: false,
            allowEmptyOption: true,
            maxOptions: null,
            ...options,
        };

        const instance = new TomSelect(el, defaultOptions);

        // Bridge wrapper.tomselect reference used by accessClearanceRoleManager.js
        if (instance.wrapper) {
            instance.wrapper.tomselect = instance;
        }

        const key = el.id || (typeof selectorOrEl === 'string' ? selectorOrEl : null);
        if (key) {
            this.instances[key] = instance;
        }

        return instance;
    },

    /**
     * Alias for init() to maintain parity with other MOWS third-party helpers.
     */
    create(selectorOrEl, options = {}) {
        return this.init(selectorOrEl, options);
    },

    /**
     * Set the selected value(s) on a Tom Select instance.
     */
    setValue(selectorOrEl, value, silent = true) {
        const instance = this.getInstance(selectorOrEl);
        if (!instance) return;

        const normalized = Array.isArray(value)
            ? value.map(v => String(v))
            : (value !== null && value !== undefined ? String(value) : '');

        instance.setValue(normalized, silent);
    },

    /**
     * Get the current value(s) from a Tom Select instance.
     */
    getValue(selectorOrEl) {
        const instance = this.getInstance(selectorOrEl);
        return instance ? instance.getValue() : null;
    },

    /**
     * Clear the current selection on a Tom Select instance.
     */
    clear(selectorOrEl, silent = true) {
        const instance = this.getInstance(selectorOrEl);
        if (instance) {
            instance.clear(silent);
        }
    },

    /**
     * Replace the option list on a Tom Select instance (used after DOM updates).
     * Expects an array of { value, text } objects.
     */
    syncOptions(selectorOrEl, options = []) {
        const instance = this.getInstance(selectorOrEl);
        if (!instance) return;

        const currentValue = instance.getValue();
        instance.clearOptions();
        instance.addOptions(options);
        instance.refreshOptions(false);

        if (currentValue && (Array.isArray(currentValue) ? currentValue.length > 0 : currentValue !== '')) {
            instance.setValue(currentValue, true);
        }
    },

    /**
     * Destroy a Tom Select instance and clean up its registry entry.
     */
    destroy(selectorOrEl) {
        const el = this.resolveElement(selectorOrEl);
        const instance = this.getInstance(selectorOrEl);

        if (instance) {
            instance.destroy();
        }

        const key = el && el.id ? el.id : (typeof selectorOrEl === 'string' ? selectorOrEl : null);
        if (key && this.instances[key]) {
            delete this.instances[key];
        }
    },
};

window.TomSelect = TomSelect;
export default window.Select;