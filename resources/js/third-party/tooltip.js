// resources/js/third-party/tooltip.js
import tippy from 'tippy.js';
import 'tippy.js/dist/tippy.css';

/**
 * Global Tippy.js Tooltip Helper Wrapper
 */
window.TooltipHelper = {
    instances: {},

    init(selectorOrEl, options = {}) {
        const elements = typeof selectorOrEl === 'string'
            ? document.querySelectorAll(selectorOrEl)
            : (selectorOrEl instanceof HTMLElement ? [selectorOrEl] : []);

        if (elements.length === 0) return null;

        const defaultOptions = {
            placement: 'top',
            animation: 'fade',
            arrow: true,
            ...options
        };

        const tippyInstances = tippy(elements, defaultOptions);

        return Array.isArray(tippyInstances) && tippyInstances.length === 1
            ? tippyInstances[0]
            : tippyInstances;
    },

    destroy(selectorOrEl) {
        const elements = typeof selectorOrEl === 'string'
            ? document.querySelectorAll(selectorOrEl)
            : (selectorOrEl instanceof HTMLElement ? [selectorOrEl] : []);

        elements.forEach(el => {
            if (el._tippy) {
                el._tippy.destroy();
            }
        });
    }
};

window.tippy = tippy;

export default tippy;