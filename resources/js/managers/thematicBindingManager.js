// resources/js/managers/thematicBindingManager.js
export default function thematicBindingManager() {
    return {
        modals: {
            uploadSkin: { open: false }
        },
        varSearchQuery: '',
        searchHelperInstance: null,

        init() {
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

        async refreshDOM() {
            this.modals.uploadSkin.open = false;
            if (typeof window.updateDOM === 'function') {
                await window.updateDOM(this.$refs, ['skinsTableWrapper'], () => {
                    this.initFuse();
                }, false);
            }
        }
    };
}