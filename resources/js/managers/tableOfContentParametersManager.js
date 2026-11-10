// resources/js/managers/tableOfContentsManager.js
export default () => ({
    // 1. State Properties Upfront
    isOpen: false,
    mode: 'create',
    id: '',
    action: '/admin/navigation',
    method: 'POST',
    parent_entry_id: '',
    group_title: '',
    group_sort_order: 0,
    translation_code_key: '',
    destination_url: '',
    display_location: 'primary',
    access_clearance: 'public',
    required_permission: '',
    window_target: '_self',
    entry_sort_order: 0,
    is_visible: true,
    searchQuery: '',
    searchHelperInstance: null,
    filteredIds: null,
    isLoading: false,

    // 2. Lifecycle Hook
    init() {
        this.initSearch();
    },

    // 3. Search & Filtering (Fuse.js Integration)
    initSearch() {
        if (window.Search && this.$el.querySelector('[data-search-item]')) {
            try {
                this.searchHelperInstance = window.Search.create(
                    '[data-search-item]',
                    ['translation_code_key', 'destination_url', 'group_title', 'display_location'],
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

    isItemVisible(id, childIds = []) {
        if (!this.searchQuery.trim() || this.filteredIds === null) return true;
        if (this.filteredIds.includes(Number(id))) return true;
        return Array.isArray(childIds) && childIds.some(childId => this.filteredIds.includes(Number(childId)));
    },

    // 4. Modal Handlers (Using window.Modal utility)
    openModal(mode, data = {}) {
        const baseUrl = '/admin/navigation';
        if (mode === 'create') {
            window.Modal.create(this, {
                parent_entry_id: '',
                group_title: '',
                group_sort_order: 0,
                translation_code_key: '',
                destination_url: '',
                display_location: 'primary',
                access_clearance: 'public',
                required_permission: '',
                window_target: '_self',
                entry_sort_order: 0,
                is_visible: true
            }, baseUrl);
        } else {
            window.Modal.edit(this, {
                ...data,
                parent_entry_id: data.parent_entry_id && data.parent_entry_id !== 'null' ? String(data.parent_entry_id) : '',
                group_title: data.group_title && data.group_title !== 'null' ? data.group_title : '',
                required_permission: data.required_permission && data.required_permission !== 'null' ? data.required_permission : '',
                is_visible: data.is_visible !== undefined ? Boolean(data.is_visible) : true
            }, baseUrl);
        }
        this.isOpen = true;
    },

    closeModal() {
        window.Modal.close(this);
    },

    // 5. DOM Refresh Handler (sanitize = false)
    async refreshDOM() {
        if (typeof window.updateDOM === 'function') {
            await window.updateDOM(this.$refs, ['navTableContent', 'navModalDropdowns'], () => {
                this.closeModal();
                this.$nextTick(() => {
                    this.initSearch();
                });
            }, false);
        } else {
            this.closeModal();
            this.$nextTick(() => {
                this.initSearch();
            });
        }
    }
});