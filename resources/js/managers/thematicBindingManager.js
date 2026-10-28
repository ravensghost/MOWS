// resources/js/managers/thematicBindingManager.js
export default function thematicBindingManager() {
    return {
        modals: {
            uploadSkin: { open: false }
        },
        varSearchQuery: '',
        searchHelperInstance: null,

        init() {
            // Initialise search helper if a catalog or search element exists
            this.initFuse();
        },

        initFuse() {
            if (window.Search) {
                try {
                    this.searchHelperInstance = window.Search.create('[data-theme-var-item], [data-var-search-item]', ['label', 'key', 'usage'], {
                        threshold: 0.35
                    });
                } catch (e) {
                    this.searchHelperInstance = null;
                }
            }
        },

        matchesVarSearch(label, key, usage) {
            if (!this.varSearchQuery.trim()) return true;

            // Leverage window.Search or fallback to direct Fuse instance check
            if (this.searchHelperInstance) {
                // If using the global helper instance, or evaluate via local Fuse
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

        async refreshDOM() {
            this.modals.uploadSkin.open = false;
            // Updated to use window.updateDOM with sanitize = false (replacing old _US helper)
            if (typeof window.updateDOM === 'function') {
                await window.updateDOM(this.$refs, ['skinsTableWrapper'], () => {
                    this.initFuse();
                }, false);
            }
        }
    };
}