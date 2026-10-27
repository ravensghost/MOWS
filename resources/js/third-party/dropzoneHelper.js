// resources/js/third-party/dropzoneHelper.js
import Dropzone from 'dropzone';

Dropzone.autoDiscover = false;

/**
 * Global Dropzone Utility
 */
window.DropzoneHelper = {
    init(element, options = {}) {
        if (!element) return null;
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        return new Dropzone(element, {
            headers: { 'X-CSRF-TOKEN': csrfToken },
            dictDefaultMessage: 'Drop files here or click to upload',
            ...options
        });
    }
};

/**
 * Alpine component for Dropzone integration with preset support
 */
export default function dropzoneHelper(options = {}) {
    return {
        dropzoneInstance: null,
        init() {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            // Handle built-in presets (e.g., skin uploader replacement)
            let resolvedOptions = { ...options };
            if (options.preset === 'skin') {
                resolvedOptions = {
                    acceptedFiles: '.zip,application/zip,application/x-zip-compressed',
                    maxFilesize: 10,
                    paramName: 'skin_package',
                    success: (file, response) => {
                        if (this.dropzoneInstance) {
                            this.dropzoneInstance.removeFile(file);
                        }
                        window.dispatchEvent(new CustomEvent('ajax-success', { detail: response }));
                    },
                    error: (file, errorMessage) => {
                        if (this.dropzoneInstance) {
                            this.dropzoneInstance.removeFile(file);
                        }
                        const message = (typeof errorMessage === 'object' && errorMessage !== null && errorMessage.message)
                            ? errorMessage.message
                            : String(errorMessage);

                        window.Swal.fire({
                            icon: 'error',
                            title: 'Upload Error',
                            text: message,
                            background: 'var(--theme-panel-bg, #111827)',
                            color: 'var(--theme-text-p, #ffffff)'
                        });
                    },
                    ...options
                };
            }

            this.dropzoneInstance = new Dropzone(this.$el, {
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    ...(resolvedOptions.headers || {})
                },
                autoProcessQueue: resolvedOptions.autoProcessQueue ?? true,
                ...resolvedOptions
            });

            // Default generic success/error handlers if no preset overrides them
            if (!options.preset) {
                this.dropzoneInstance.on('success', (file, response) => {
                    if (typeof options.onSuccess === 'function') {
                        options.onSuccess(file, response);
                    }
                });

                this.dropzoneInstance.on('error', (file, errorMessage) => {
                    const message = typeof errorMessage === 'string' ? errorMessage : (errorMessage.message || 'Upload failed');
                    window.Swal.fire({ icon: 'error', title: 'Upload Error', text: message });
                    if (typeof options.onError === 'function') {
                        options.onError(file, errorMessage);
                    }
                });
            }
        },
        destroy() {
            if (this.dropzoneInstance) {
                this.dropzoneInstance.destroy();
            }
        }
    };
}