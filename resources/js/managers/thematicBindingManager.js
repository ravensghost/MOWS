// resources/js/managers/thematicBindingManager.js

export default function thematicBindingManager() {
    return {
        // 1. State Properties Upfront (Unified Modals & Search Contract)
        modals: {
            uploadSkin: {
                isOpen: false,
                open: false,
                mode: 'create',
                id: '',
                action: '/admin/thematic-bindings/upload',
                method: 'POST'
            }
        },
        searchQuery: '',
        varSearchQuery: '',
        searchHelperInstance: null,
        filteredIds: null,
        isLoading: false,

        // 2. Lifecycle Hook
        init() {
            this.initSearch();
            this.$watch('varSearchQuery', (val) => {
                this.searchQuery = val;
                if (!val.trim() || !this.searchHelperInstance) {
                    this.filteredIds = null;
                    return;
                }
                const results = this.searchHelperInstance.search(val.trim());
                this.filteredIds = results ? results.map(String) : [];
            });
        },

        // 3. Search & Filtering (Fuse.js Integration)
        initSearch() {
            if (window.Search && this.$el && this.$el.querySelector('[data-search-item], [data-theme-var-item], [data-var-search-item]')) {
                try {
                    this.searchHelperInstance = window.Search.create(
                        '[data-search-item], [data-theme-var-item], [data-var-search-item]',
                        ['label', 'key', 'usage'],
                        { threshold: 0.35 }
                    );
                } catch (e) {
                    this.searchHelperInstance = null;
                }
            }
        },

        initFuse() {
            this.initSearch();
        },

        isItemVisible(idOrKey) {
            const q = (this.varSearchQuery || this.searchQuery).trim().toLowerCase();
            if (!q) return true;
            if (this.filteredIds !== null && this.filteredIds.length > 0) {
                return this.filteredIds.includes(String(idOrKey));
            }
            return String(idOrKey || '').toLowerCase().includes(q);
        },

        matchesVarSearch(label = '', key = '', usage = '') {
            const q = (this.varSearchQuery || this.searchQuery).trim().toLowerCase();
            if (!q) return true;
            if (this.filteredIds !== null && this.filteredIds.length > 0 && key && this.filteredIds.includes(String(key))) {
                return true;
            }
            return [label, key, usage].some(val =>
                String(val || '').toLowerCase().includes(q)
            );
        },

        // 4. Modal Handlers (Using window.Modal utility)
        openUploadModal(baseUrl = '/admin/thematic-bindings/upload') {
            window.Modal.create(this.modals.uploadSkin, {}, baseUrl);
        },

        closeUploadModal() {
            window.Modal.close(this.modals.uploadSkin);
        },

        // 5. DOM Refresh Handler (sanitize = false)
        async refreshDOM() {
            this.closeUploadModal();
            if (typeof window.updateDOM === 'function') {
                await window.updateDOM(this.$refs, ['skinsTableWrapper', 'thematicBindingsWrapper'], () => {
                    this.$nextTick(() => {
                        this.initSearch();
                    });
                }, false);
            }
        }
    };
}