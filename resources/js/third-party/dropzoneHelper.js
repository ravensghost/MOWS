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

        if (el.dropzone) {
            el.dropzone.destroy();
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        const defaultOptions = {
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            dictDefaultMessage: 'Drag and drop files or click to browse',
            ...options
        };

        return new Dropzone(el, defaultOptions);
    }
};

/**
 * Canonical Alpine.js x-data="dropzoneHelper(...)" Component Factory for <x-dropzone>
 */
export default function dropzoneHelper(
    url = '',
    maxFiles = 1,
    acceptedFiles = null,
    paramName = 'file',
    maxFilesize = 10,
    extraParams = {}
) {
    return {
        dropzoneInstance: null,

        init() {
            this.$nextTick(() => {
                const targetEl = this.$el.querySelector('.dropzone') || this.$el;
                if (!targetEl || !url) return;

                this.dropzoneInstance = window.DropzoneHelper.create(targetEl, {
                    url: url,
                    paramName: paramName || 'file',
                    maxFiles: Number(maxFiles) || 1,
                    maxFilesize: Number(maxFilesize) || 10,
                    acceptedFiles: acceptedFiles || null,
                    params: extraParams && typeof extraParams === 'object' ? extraParams : {},
                    init: function () {
                        this.on('maxfilesexceeded', function (file) {
                            this.removeAllFiles();
                            this.addFile(file);
                        });
                    }
                });

                if (this.dropzoneInstance) {
                    this.dropzoneInstance.on('success', (file, response) => {
                        this.dropzoneInstance.removeFile(file);
                        if (window.Swal && typeof window.Swal.toast === 'function') {
                            window.Swal.toast('success', response?.message || 'File uploaded successfully.');
                        }
                        window.dispatchEvent(new CustomEvent('ajax-success', { detail: response }));
                        this.$dispatch('dropzone-success', response);
                    });

                    this.dropzoneInstance.on('error', (file, errorMessage) => {
                        this.dropzoneInstance.removeFile(file);
                        const msg = typeof errorMessage === 'object' && errorMessage?.message
                            ? errorMessage.message
                            : (typeof errorMessage === 'string' ? errorMessage : 'File upload failed.');
                        if (window.Swal && typeof window.Swal.error === 'function') {
                            window.Swal.error('Upload Error', msg);
                        }
                        this.$dispatch('dropzone-error', { message: msg, error: errorMessage });
                    });
                }
            });
        },

        destroy() {
            if (this.dropzoneInstance) {
                this.dropzoneInstance.destroy();
                this.dropzoneInstance = null;
            }
        }
    };
}