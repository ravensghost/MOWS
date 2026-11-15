// resources/js/managers/assetManager.js
/**
 * Asset Manager Component
 * Handles loading, copying URLs, uploading, and deleting general media assets.
 */
export default () => ({
    // 1. State Properties Upfront (Unified Modals & Search Contract)
    modals: {
        upload: {
            open: false,
            mode: 'create',
            id: '',
            action: '/admin/general-images',
            method: 'POST'
        }
    },
    images: [],
    isLoading: false,
    searchQuery: '',
    searchHelperInstance: null,
    filteredIds: null,

    // 2. Lifecycle Hook
    init() {
        this.loadImages();
        this.initSearch();
    },

    // 3. Search & Filtering (Fuse.js Integration)
    initSearch() {
        if (window.Search && this.$el && this.$el.querySelector('[data-asset-item]')) {
            try {
                this.searchHelperInstance = window.Search.create(
                    '[data-asset-item]',
                    ['filename', 'url'],
                    { threshold: 0.35 }
                );
            } catch (e) {
                this.searchHelperInstance = null;
            }
        }
        this.$watch('searchQuery', (val) => {
            if (!val.trim() || !this.searchHelperInstance) {
                this.filteredIds = null;
                return;
            }
            const results = this.searchHelperInstance.search(val.trim());
            this.filteredIds = results ? results.map(id => Number(id)) : [];
        });
    },

    isItemVisible(id) {
        if (!this.searchQuery.trim() || this.filteredIds === null) return true;
        return this.filteredIds.includes(Number(id));
    },

    // Data Loading / Fetch Operations
    async loadImages() {
        this.isLoading = true;
        try {
            const { ok, data } = await window.Fetch.get('/admin/general-images', { loading: false, showError: false });
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

    // Utility Actions
    copyUrl(url) {
        if (typeof window.copyToClipboard === 'function') {
            window.copyToClipboard(url, 'Asset URL copied to clipboard!');
        }
    },

    // 4. Modal Handlers (Using window.Modal utility)
    openUploadModal() {
        window.Modal.create(this.modals.upload, {}, '/admin/general-images');
        this.modals.upload.open = true;
    },

    closeUploadModal() {
        window.Modal.close(this.modals.upload);
        this.modals.upload.open = false;
        this.loadImages();
    },

    // CRUD Deletion Handler
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
                await this.refreshDOM();
                if (window.Swal && typeof window.Swal.toast === 'function') {
                    window.Swal.toast('success', data.message || 'Asset deleted successfully.');
                }
            } else {
                window.Swal.error('Error', data.message || 'Failed to delete asset.');
            }
        } catch (err) {
            // Network errors handled by window.Fetch
        }
    },

    // 5. DOM Refresh Handler (sanitize = false)
    async refreshDOM() {
        this.closeUploadModal();
        if (typeof window.updateDOM === 'function') {
            await window.updateDOM(this.$refs, ['assetsTableContent'], () => {
                this.loadImages();
                this.$nextTick(() => {
                    this.initSearch();
                });
            }, false);
        } else {
            this.loadImages();
        }
    }
});