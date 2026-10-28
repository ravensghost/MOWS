// resources/js/managers/assetManager.js
export default function assetManager() {
    return {
        images: [],
        isLoading: false,
        uploadModalOpen: false,

        init() {
            this.loadImages();
        },

        async loadImages() {
            this.isLoading = true;
            try {
                const { ok, data } = await window.Fetch.get('/admin/general-images', {
                    loading: false,
                    showError: false
                });

                if (ok && data) {
                    this.images = data.images || [];
                } else {
                    this.images = [];
                }
            } catch (e) {
                this.images = [];
            } finally {
                this.isLoading = false;
            }
        },

        copyUrl(url) {
            // copyToClipboard already handles copying and triggers the success toast via window.Swal.toast
            if (typeof window.copyToClipboard === 'function') {
                window.copyToClipboard(url, 'Asset URL copied to clipboard!');
            }
        },

        async deleteImage(filename) {
            const confirmResult = await window.Swal.confirm(
                'Delete Asset?',
                `Are you sure you want to delete ${filename}?`,
                'Delete'
            );

            if (!confirmResult.isConfirmed) return;

            try {
                const { ok, data } = await window.Fetch.delete('/admin/general-images', { filename: filename }, {
                    loading: false,
                    showError: true
                });

                if (ok) {
                    this.loadImages();
                    if (window.Swal && typeof window.Swal.toast === 'function') {
                        window.Swal.toast('success', data.message || 'Asset deleted successfully.');
                    }
                } else {
                    window.Swal.error('Error', data.message || 'Failed to delete asset.');
                }
            } catch (err) {
                // Network errors are automatically handled by window.Fetch
            }
        }
    };
}