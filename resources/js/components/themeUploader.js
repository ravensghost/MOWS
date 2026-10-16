export default (csrfToken) => ({
    dropzoneInstance: null,

    init() {
        this.dropzoneInstance = new window.Dropzone(this.$el, {
            url: this.$el.action,
            acceptedFiles: '.zip,application/zip,application/x-zip-compressed',
            maxFilesize: 10, // Enforce InfinityFree 10 MB hard limit
            paramName: 'skin_package',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            success: (file, response) => {
                if (this.dropzoneInstance) {
                    this.dropzoneInstance.removeFile(file);
                }
                window.dispatchEvent(new CustomEvent('ajax-success', { detail: response }));
            },
            error: (file, msg) => {
                if (this.dropzoneInstance) {
                    this.dropzoneInstance.removeFile(file);
                }
                const errorText = (typeof msg === 'object' && msg !== null && msg.message)
                    ? msg.message
                    : String(msg);

                window.Swal.fire({
                    icon: 'error',
                    title: 'Upload Error',
                    text: errorText,
                    background: 'var(--theme-panel-bg, #111827)',
                    color: 'var(--theme-text-main, #ffffff)'
                });
            }
        });
    }
});