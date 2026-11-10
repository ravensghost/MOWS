// resources/js/third-party/lightboxHelper.js
import GLightbox from 'glightbox';
import 'glightbox/dist/css/glightbox.min.css';

/**
 * Global GLightbox Helper Wrapper
 */
window.LightboxHelper = {
    instances: {},

    init(selector = '.glightbox', options = {}) {
        const defaultOptions = {
            touchNavigation: true,
            loop: true,
            autoplayVideos: true,
            ...options
        };

        // Clean up pre-existing instance if tracked
        if (this.instances[selector]) {
            this.instances[selector].destroy();
        }

        const lightboxInstance = GLightbox({
            selector: selector,
            ...defaultOptions
        });

        this.instances[selector] = lightboxInstance;
        return lightboxInstance;
    },

    destroy(selector = '.glightbox') {
        if (this.instances[selector]) {
            this.instances[selector].destroy();
            delete this.instances[selector];
        }
    }
};

window.GLightbox = GLightbox;

export default GLightbox;