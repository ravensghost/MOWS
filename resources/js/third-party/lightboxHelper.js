// resources/js/third-party/lightboxHelper.js
import GLightbox from 'glightbox';
import 'glightbox/dist/css/glightbox.min.css';

// Expose GLightbox globally so existing managers (like avatarManager.js) work without changes
window.GLightbox = GLightbox;

/**
 * Global Lightbox Utility
 * Standardises GLightbox initialisation, gallery grouping, and cleanup.
 */
window.Lightbox = {
    init(selector = '.glightbox', options = {}) {
        if (!selector) return null;
        return GLightbox({
            selector: selector,
            touchNavigation: true,
            loop: true,
            zoomable: true,
            ...options
        });
    }
};

/**
 * Custom Alpine Directive: x-lightbox
 */
document.addEventListener('alpine:init', () => {
    window.Alpine.directive('lightbox', (el, { expression }, { evaluate }) => {
        const options = evaluate(expression) || {};
        const selector = el.dataset.lightboxSelector || '.glightbox';

        const instance = GLightbox({
            selector: selector,
            touchNavigation: true,
            loop: true,
            ...options
        });

        el._glightbox = instance;
    });
});

/**
 * Alpine Component Helper for dynamic/reactive galleries
 */
export default function lightboxHelper(options = {}) {
    return {
        instance: null,
        init() {
            this.initLightbox();
        },
        initLightbox() {
            if (this.instance) {
                this.instance.destroy();
            }
            this.instance = GLightbox({
                selector: '.glightbox',
                touchNavigation: true,
                loop: true,
                ...options
            });
        },
        destroy() {
            if (this.instance) {
                this.instance.destroy();
            }
        }
    };
}