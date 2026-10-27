// resources/js/third-party/selectHelper.js
import TomSelect from 'tom-select';

/**
 * Global Tom Select Helper Utility
 * Standardises Tom Select initialisation, value syncing, dynamic option reloading, and cleanup.
 */
window.Select = {
    init(el, config = {}) {
        if (!el) return null;
        if (el.tomselect) {
            el.tomselect.destroy();
        }
        return new TomSelect(el, {
            plugins: config.multiple ? ['remove_button'] : [],
            create: false,
            ...config
        });
    },
    setValue(el, value, silent = true) {
        if (el && el.tomselect) {
            el.tomselect.setValue(value, silent);
        }
    },
    clear(el, silent = true) {
        if (el && el.tomselect) {
            el.tomselect.clear(silent);
        }
    },
    syncOptions(el, optionsArray = [], selectedValue = null) {
        if (!el || !el.tomselect) return;
        const ts = el.tomselect;
        ts.clearOptions();
        ts.addOptions(optionsArray);
        ts.refreshOptions(false);
        if (selectedValue !== null) {
            ts.setValue(selectedValue, true);
        }
    },
    destroy(el) {
        if (el && el.tomselect) {
            el.tomselect.destroy();
        }
    }
};