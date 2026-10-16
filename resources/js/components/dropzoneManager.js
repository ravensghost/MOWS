export default (uploadUrl, maxFiles = 1, acceptedFiles = null) => ({
    init() {
        if (window.Dropzone) {
            Dropzone.autoDiscover = false;
            let dropzoneEl = this.$el.querySelector('.dropzone');

            if (dropzoneEl && !dropzoneEl.dropzone) {
                let options = {
                    url: uploadUrl,
                    maxFiles: maxFiles,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                    },
                    success: (file, response) => {
                        window.Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'success',
                            title: response.message || 'Upload successful!',
                            showConfirmButton: false,
                            timer: 3000,
                            background: 'var(--theme-bg-panel, #111827)',
                            color: 'var(--theme-text-p, #ffffff)'
                        });
                        this.$dispatch('upload-completed', response);
                    },
                    error: (file, errorMessage) => {
                        let msg = typeof errorMessage === 'object' ? errorMessage.message : errorMessage;
                        window.Swal.fire('Error', msg || 'Upload failed.', 'error');
                    }
                };

                if (acceptedFiles) {
                    options.acceptedFiles = acceptedFiles;
                }

                new Dropzone(dropzoneEl, options);
            }
        }
    }
});