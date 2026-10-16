import Fuse from 'fuse.js';

export default () => ({
    searchQuery: '',
    fuse: null,
    matchedIds: null,

    // Link Modal State
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
        formAction: '/admin/navigation',
        method: 'POST'
    },

    init() {
        this.initFuse();
        this.$watch('searchQuery', (val) => {
            if (!val.trim()) {
                this.matchedIds = null;
                return;
            }
            if (this.fuse) {
                this.matchedIds = this.fuse.search(val.trim()).map(res => Number(res.item.id));
            }
        });
    },

    initFuse() {
        const rows = Array.from(document.querySelectorAll('[data-nav-search-item]'));
        const items = rows.map(row => ({
            id: Number(row.dataset.id),
            parent_id: row.dataset.parentId ? Number(row.dataset.parentId) : null,
            lexicon_key: row.dataset.lexiconKey || '',
            url: row.dataset.url || '',
            group_name: row.dataset.groupName || '',
            location: row.dataset.location || ''
        }));

        this.fuse = new Fuse(items, {
            keys: ['lexicon_key', 'url', 'group_name', 'location'],
            threshold: 0.35,
            ignoreLocation: true
        });
    },

    isLinkVisible(id, childIds = []) {
        if (this.matchedIds === null) return true;
        if (this.matchedIds.includes(Number(id))) return true;
        return childIds.some(childId => this.matchedIds.includes(Number(childId)));
    },

    async refreshDOM() {
        // Refresh the main table and the dropdowns inside the link modal while preserving Alpine bindings
        await window.updateDOM_US(this.$refs, ['navTableContent', 'navModalDropdowns']);
        this.$nextTick(() => {
            this.initFuse();
        });
    },

    // --- Link Modal Methods ---

    openLinkModal(mode, data = {}) {
        this.linkModal.mode = mode;
        this.linkModal.id = data.id || '';
        this.linkModal.parent_id = data.parent_id || '';
        this.linkModal.group_name = data.group_name && data.group_name !== 'null' ? data.group_name : '';
        this.linkModal.group_order = data.group_order || 0;
        this.linkModal.lexicon_key = data.lexicon_key || '';
        this.linkModal.url = data.url || '';
        this.linkModal.location = data.location || 'primary';
        this.linkModal.visibility = data.visibility || 'public';
        this.linkModal.required_permission = data.required_permission && data.required_permission !== 'null' ? data.required_permission : '';
        this.linkModal.target = data.target || '_self';
        this.linkModal.order = data.order || 0;
        this.linkModal.formAction = mode === 'edit' ? `/admin/navigation/${this.linkModal.id}` : '/admin/navigation';
        this.linkModal.method = mode === 'edit' ? 'PUT' : 'POST';
        this.linkModal.open = true;
    },

    closeLinkModal() {
        this.linkModal.open = false;
    }
});