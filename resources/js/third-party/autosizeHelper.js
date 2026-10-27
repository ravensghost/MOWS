// resources/js/third-party/autosizeHelper.js
import autosize from 'autosize';

/**
 * Global Autosize Helper Utility
 * Automatically adjusts textarea height based on content.
 */
window.Autosize = {
    init(el) {
        if (el) autosize(el);
    },
    update(el) {
        if (el) autosize.update(el);
    },
    destroy(el) {
        if (el) autosize.destroy(el);
    }
};

/**
 * Custom Alpine Directive: x-autosize
 */
document.addEventListener('alpine:init', () => {
    window.Alpine.directive('autosize', (el) => {
        autosize(el);
    });
});