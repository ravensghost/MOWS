export default function themeManager() {
    return {
        modals: {
            uploadSkin: { open: false }
        },
        varSearchQuery: '',

        matchesVarSearch(label, key, usage) {
            if (!this.varSearchQuery.trim()) return true;
            const list = [{ label, key, usage }];
            const fuse = new window.Fuse(list, {
                keys: ['label', 'key', 'usage'],
                threshold: 0.35
            });
            return fuse.search(this.varSearchQuery.trim()).length > 0;
        },

        refreshDOM() {
            this.modals.uploadSkin.open = false;
            window.updateDOM_US(this.$refs, ['skinsTableWrapper']);
        }
    };
}