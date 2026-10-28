// resources/js/managers/tableOfContentsManager.js
export default () => ({
    searchQuery: '',
    searchHelperInstance: null,
    matchedIds: null,

    linkModal: {
        open: false,
        mode: 'create',
        id: '',
        parent_id: '',
        group_name: '',
        group_order: 0,
        lexicon_key: '',
        url: '',
        location: 'primary',
        visibility: 'public',
        required_permission: '',
        target: '_self',
        order: 0,
        is_visible: true,
        action: '/admin/navigation',
        method: 'POST'
    },

    init() {
        this.initFuse();
        this.$watch('searchQuery', (val) => {
            if (!val.trim()) {
                this.matchedIds = null;
                return;
            }
            if (this.searchHelperInstance) {
                const results = this.searchHelperInstance.search(val.trim());
                this.matchedIds = results ? results.map(id => Number(id)) : [];
            }
        });
    },

    initFuse() {
        // Leverage the standardized global window.Search helper (powered by Fuse.js)
        if (window.Search) {
            this.searchHelperInstance = window.Search.create('[data-nav-search-item]', ['lexicon_key', 'url', 'group_name', 'location'], {
                threshold: 0.35
            });
        }
    },

    isLinkVisible(id, childIds = []) {
        if (this.matchedIds === null) return true;
        if (this.matchedIds.includes(Number(id))) return true;
        return childIds.some(childId => this.matchedIds.includes(Number(childId)));
    },

    async refreshDOM() {
        // Updated to use window.updateDOM with sanitize = false (replacing old _US helper)
        if (typeof window.updateDOM === 'function') {
            await window.updateDOM(this.$refs, ['navTableContent', 'navModalDropdowns'], () => {
                this.linkModal.open = false;
                this.$nextTick(() => {
                    this.initFuse();
                });
            }, false);
        } else {
            this.linkModal.open = false;
            this.$nextTick(() => {
                this.initFuse();
            });
        }
    },

    openLinkModal(mode, data = {}) {
        if (mode === 'create') {
            window.Modal.create(this.linkModal, {
                location: 'primary',
                visibility: 'public',
                target: '_self',
                is_visible: true,
                group_order: 0,
                order: 0
            }, '/admin/navigation');
        } else {
            window.Modal.edit(this.linkModal, data, (id) => `/admin/navigation/${id}`);
        }

        // Handle specific nullable/formatting edge cases cleanly
        this.linkModal.group_name = data.group_name && data.group_name !== 'null' ? data.group_name : '';
        this.linkModal.required_permission = data.required_permission && data.required_permission !== 'null' ? data.required_permission : '';
        this.linkModal.is_visible = data.is_visible !== undefined ? Boolean(data.is_visible) : true;
    },

    closeLinkModal() {
        window.Modal.close(this.linkModal);
    }
});