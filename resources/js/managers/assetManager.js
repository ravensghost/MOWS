// resources/js/managers/assetManager.js

/**
 * Asset Manager Component
 * Handles loading, copying URLs, uploading, and deleting general media assets.
 */
export default () => ({
    // 1. State Properties Upfront
    images: [],
    isLoading: false,
    uploadModalOpen: false,

    // 2. Lifecycle Hook
    init() {
        this.loadImages();
    },

    // 3. Data Loading / Fetch Operations
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

    // 4. Utility Actions
    copyUrl(url) {
        if (typeof window.copyToClipboard === 'function') {
            window.copyToClipboard(url, 'Asset URL copied to clipboard!');
        }
    },

    // 5. Modal Handlers
    openUploadModal() {
        this.uploadModalOpen = true;
    },

    closeUploadModal() {
        this.uploadModalOpen = false;
        this.loadImages();
    },

    // 6. CRUD Deletion Handler
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
            // Network errors handled by window.Fetch
        }
    }
});