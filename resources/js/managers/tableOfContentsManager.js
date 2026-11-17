// resources/js/managers/tableOfContentsManager.js

export default function tableOfContentsManager() {
    return {
        // 1. State Properties Upfront (Unified Modals & Search Contract)
        modals: {
            entry: {
                isOpen: false,
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
                action: '/admin/table-of-contents',
                method: 'POST'
            }
        },
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
            if (window.Search && this.$el && this.$el.querySelector('[data-search-item], [data-navigation-item]')) {
                try {
                    this.searchHelperInstance = window.Search.create(
                        '[data-search-item], [data-navigation-item]',
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
        openModal(mode = 'create', data = {}, baseUrl = '/admin/table-of-contents') {
            if (mode === 'edit') {
                window.Modal.edit(this.modals.entry, {
                    ...data,
                    parent_entry_id: String(data.parent_entry_id ?? ''),
                    is_visible: data.is_visible !== undefined ? Boolean(data.is_visible) : true
                }, (id) => `${baseUrl}/${id}`);
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
                    is_visible: true,
                    ...data
                }, baseUrl);
            }
        },

        closeModal() {
            window.Modal.close(this.modals.entry);
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
    };
}