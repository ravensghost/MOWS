// resources/js/managers/assetManager.js

/**
 * Asset Manager Component
 * Handles loading, searching, copying URLs, uploading, and deleting general media assets.
 */
export default function assetManager() {
    return {
        // 1. State Properties Upfront (Unified Modals & Search Contract)
        modals: {
            upload: {
                isOpen: false,
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
            if (window.Search && this.$el && this.$el.querySelector('[data-search-item], [data-asset-item]')) {
                try {
                    this.searchHelperInstance = window.Search.create(
                        '[data-search-item], [data-asset-item]',
                        ['name', 'filename', 'url'],
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
                this.filteredIds = results ? results.map(String) : [];
            });
        },

        isItemVisible(idOrName) {
            const q = this.searchQuery.trim().toLowerCase();
            if (!q) return true;
            if (this.filteredIds !== null && this.filteredIds.length > 0) {
                return this.filteredIds.includes(String(idOrName));
            }
            return String(idOrName || '').toLowerCase().includes(q);
        },

        async loadImages() {
            this.isLoading = true;
            try {
                const { ok, data } = await window.Fetch.get('/admin/general-images', {
                    loading: false,
                    showError: false
                });
                if (ok && data) {
                    this.images = data.images || data.data || [];
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
            if (typeof window.copyToClipboard === 'function') {
                window.copyToClipboard(url, 'Asset URL copied to clipboard!');
            }
        },

        // 4. Modal & Action Handlers (Using window.Modal & window.Fetch)
        openUploadModal() {
            window.Modal.create(this.modals.upload, {}, '/admin/general-images');
        },

        closeUploadModal() {
            window.Modal.close(this.modals.upload);
            this.loadImages();
        },

        async deleteImage(filename) {
            const confirmResult = await window.Swal.confirm(
                'Delete Asset?',
                `Are you sure you want to delete ${filename}?`,
                'Delete'
            );

            if (!confirmResult.isConfirmed) return;

            try {
                const { ok, data } = await window.Fetch.delete('/admin/general-images', { filename }, {
                    loading: false,
                    showError: true
                });

                if (ok) {
                    await this.refreshDOM();
                    if (window.Swal && typeof window.Swal.toast === 'function') {
                        window.Swal.toast('success', data?.message || 'Asset deleted successfully.');
                    }
                } else {
                    window.Swal.error('Error', data?.message || 'Failed to delete asset.');
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
    };
}