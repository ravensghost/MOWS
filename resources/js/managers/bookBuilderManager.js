// resources/js/managers/bookBuilderManager.js
export default (initialGrids = [], availableModules = [], moduleRegistry = {}) => ({
    // 1. State Properties Upfront (Unified Modals & State Contract)
    modals: {
        grid: {
            open: false,
            mode: 'create',
            id: '',
            action: '/admin/grids',
            method: 'POST'
        }
    },
    availableGrids: initialGrids || [],
    moduleData: Object.keys(moduleRegistry).length > 0 ? moduleRegistry : (availableModules || {}),
    isSyncing: false,
    searchQuery: '',
    searchHelperInstance: null,
    filteredIds: null,

    // 2. Lifecycle Hook
    init() {
        this.initSearch();
    },

    // 3. Search & Filtering (Fuse.js Integration)
    initSearch() {
        if (window.Search && this.$el && this.$el.querySelector('[data-grid-item]')) {
            try {
                this.searchHelperInstance = window.Search.create(
                    '[data-grid-item]',
                    ['title', 'name'],
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
        const baseUrl = '/admin/grids';
        if (mode === 'edit') {
            window.Modal.edit(this.modals.grid, data, (id) => `${baseUrl}/${id}`);
            this.modals.grid.method = 'PUT';
        } else {
            window.Modal.create(this.modals.grid, {}, baseUrl);
            this.modals.grid.method = 'POST';
        }
        this.modals.grid.open = true;
    },

    closeModal() {
        window.Modal.close(this.modals.grid);
        this.modals.grid.open = false;
    },

    // 5. DOM Refresh Handler (sanitize = false)
    async refreshDOM() {
        if (typeof window.updateDOM === 'function') {
            await window.updateDOM(this.$refs, ['bookBuilderWrapper'], () => {
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