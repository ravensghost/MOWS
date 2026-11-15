// resources/js/managers/tableOfContentsManager.js
export default () => ({
    // 1. State Properties Upfront (Unified Modals & Search Contract)
    modals: {
        entry: {
            open: false,
            mode: 'create',
            id: '',
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
            action: '/admin/navigation',
            method: 'POST'
        }
    },
    isOpen: false,
    open: false,
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
        if (window.Search && this.$el && this.$el.querySelector('[data-navigation-item]')) {
            try {
                this.searchHelperInstance = window.Search.create(
                    '[data-navigation-item]',
                    ['group_title', 'destination_url', 'translation_code_key'],
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

    // 4. Modal Handlers (Using window.Modal utility)
    openModal(mode = 'create', data = {}) {
        const baseUrl = '/admin/navigation';
        if (mode === 'edit') {
            window.Modal.edit(this.modals.entry, data, (id) => `${baseUrl}/${id}`);
            this.modals.entry.mode = 'edit';
            this.modals.entry.method = 'PUT';
            this.modals.entry.action = `${baseUrl}/${data.id}`;
        } else {
            window.Modal.create(this.modals.entry, {
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
            this.modals.entry.mode = 'create';
            this.modals.entry.method = 'POST';
            this.modals.entry.action = baseUrl;
        }
        this.modals.entry.open = true;
        this.open = true;
        this.isOpen = true;
    },

    closeModal() {
        window.Modal.close(this.modals.entry);
        this.modals.entry.open = false;
        this.open = false;
        this.isOpen = false;
    },

    // 5. DOM Refresh Handler (sanitize = false)
    async refreshDOM() {
        if (typeof window.updateDOM === 'function') {
            await window.updateDOM(this.$refs, ['navigationTableContent', 'navigationTreeWrapper'], () => {
                this.closeModal();
                this.$nextTick(() => {
                    this.initSearch();
                });
            }, false);
        } else {
            this.closeModal();
        }
    }
});