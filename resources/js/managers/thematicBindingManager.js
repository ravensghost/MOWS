// resources/js/managers/thematicBindingManager.js
export default function thematicBindingManager() {
    return {
        // 1. State Properties Upfront (Unified Modals & Search Contract)
        modals: {
            uploadSkin: {
                open: false,
                mode: 'create',
                action: '/admin/themes/skins/upload',
                method: 'POST'
            }
        },
        varSearchQuery: '',
        searchHelperInstance: null,

        // 2. Lifecycle Hook
        init() {
            this.initFuse();
            this.$watch('varSearchQuery', () => {
                // Reactive trigger for searching variables
            });
        },

        // 3. Search & Filtering (Fuse.js Integration)
        initFuse() {
            if (window.Search) {
                try {
                    this.searchHelperInstance = window.Search.create(
                        '[data-theme-var-item], [data-var-search-item]',
                        ['label', 'key', 'usage'],
                        { threshold: 0.35 }
                    );
                } catch (e) {
                    this.searchHelperInstance = null;
                }
            }
        },

        matchesVarSearch(label, key, usage) {
            if (!this.varSearchQuery.trim()) return true;

            if (this.searchHelperInstance) {
                const fuse = new window.Fuse([{ label, key, usage }], {
                    keys: ['label', 'key', 'usage'],
                    threshold: 0.35
                });
                return fuse.search(this.varSearchQuery.trim()).length > 0;
            }

            const list = [{ label, key, usage }];
            const fuse = new window.Fuse(list, {
                keys: ['label', 'key', 'usage'],
                threshold: 0.35
            });
            return fuse.search(this.varSearchQuery.trim()).length > 0;
        },

        // 4. Modal Handlers (Using window.Modal utility)
        openUploadModal() {
            const baseUrl = '/admin/themes/skins/upload';
            window.Modal.create(this.modals.uploadSkin, {}, baseUrl);
            this.modals.uploadSkin.method = 'POST';
            this.modals.uploadSkin.action = baseUrl;
            this.modals.uploadSkin.open = true;
        },

        closeUploadModal() {
            window.Modal.close(this.modals.uploadSkin);
            this.modals.uploadSkin.open = false;
        },

        // 5. DOM Refresh Handler (sanitize = false)
        async refreshDOM() {
            this.closeUploadModal();
            if (typeof window.updateDOM === 'function') {
                await window.updateDOM(this.$refs, ['skinsTableWrapper'], () => {
                    this.$nextTick(() => {
                        this.initFuse();
                    });
                }, false);
            }
        }
    };
}