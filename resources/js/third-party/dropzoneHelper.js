// resources/js/third-party/dropzoneHelper.js
import Dropzone from 'dropzone';

// Prevent Dropzone from auto-discovering elements on DOM load
Dropzone.autoDiscover = false;

/**
 * Global Dropzone Helper Wrapper & Configuration
 */
window.Dropzone = Dropzone;

window.DropzoneHelper = {
    create(element, options = {}) {
        const el = typeof element === 'string' ? document.querySelector(element) : element;
        if (!el) return null;

        // Clean up any pre-existing instance on the element
        if (el.dropzone) {
            el.dropzone.destroy();
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        const defaultOptions = {
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            dictDefaultMessage: 'Drop files here or click to upload',
            ...options
        };

        return new Dropzone(el, defaultOptions);
    }
};

export default Dropzone;