// resources/js/third-party/autosizeHelper.js
import autosize from 'autosize';

/**
 * Global Autosize Wrapper for Dynamic Textareas
 */
window.Autosize = {
    init(elOrSelector) {
        if (typeof elOrSelector === 'string') {
            const elements = document.querySelectorAll(elOrSelector);
            if (elements.length > 0) {
                autosize(elements);
            }
        } else if (elOrSelector instanceof HTMLElement) {
            autosize(elOrSelector);
        } else {
            autosize(document.querySelectorAll('textarea'));
        }
    },

    update(elOrSelector) {
        if (typeof elOrSelector === 'string') {
            const elements = document.querySelectorAll(elOrSelector);
            elements.forEach(el => autosize.update(el));
        } else if (elOrSelector instanceof HTMLElement) {
            autosize.update(elOrSelector);
        }
    },

    destroy(elOrSelector) {
        if (typeof elOrSelector === 'string') {
            const elements = document.querySelectorAll(elOrSelector);
            elements.forEach(el => autosize.destroy(el));
        } else if (elOrSelector instanceof HTMLElement) {
            autosize.destroy(elOrSelector);
        }
    }
};

export default window.Autosize;