// resources/js/managers/bookBuilderManager.js

export default function bookBuilderManager(
    initialGrids = [],
    availableModules = [],
    moduleRegistry = {}
) {
    return {
        // 1. State Properties Upfront (Unified Modals & State Contract)
        modals: {
            grid: {
                isOpen: false,
                open: false,
                mode: 'create',
                id: '',
                grid_title: '',
                grid_synopsis: '',
                action: '/admin/grids',
                method: 'POST'
            },
            book: {
                isOpen: false,
                open: false,
                mode: 'create',
                id: '',
                book_title: '',
                book_url: '',
                grid_id: '',
                is_published: true,
                action: '/admin/books',
                method: 'POST'
            },
            module: {
                isOpen: false,
                open: false,
                mode: 'edit',
                id: '',
                module_title: '',
                module_token: '',
                access_clearance: '',
                is_visible: true,
                book_config: {},
                action: '/admin/modules',
                method: 'PUT'
            }
        },
        availableGrids: initialGrids || [],
        moduleData: Object.keys(moduleRegistry || {}).length > 0 ? moduleRegistry : (availableModules || {}),
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
            if (window.Search && this.$el && this.$el.querySelector('[data-search-item], [data-grid-item], [data-book-item]')) {
                try {
                    this.searchHelperInstance = window.Search.create(
                        '[data-search-item], [data-grid-item], [data-book-item]',
                        ['title', 'name', 'book_title', 'grid_title', 'book_url'],
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

        // 4. Modal & Action Handlers (Using window.Modal & window.Fetch)
        openModal(mode = 'create', data = {}, target = 'grid') {
            if (target === 'book') {
                const baseUrl = '/admin/books';
                if (mode === 'edit') {
                    window.Modal.edit(this.modals.book, data, (id) => `${baseUrl}/${id}`);
                } else {
                    window.Modal.create(this.modals.book, {
                        book_title: '',
                        book_url: '',
                        grid_id: '',
                        is_published: true
                    }, baseUrl);
                }
                return;
            }

            if (target === 'module') {
                const baseUrl = '/admin/modules';
                window.Modal.edit(this.modals.module, data, (id) => `${baseUrl}/${id}`);
                return;
            }

            const baseUrl = '/admin/grids';
            if (mode === 'edit') {
                window.Modal.edit(this.modals.grid, data, (id) => `${baseUrl}/${id}`);
            } else {
                window.Modal.create(this.modals.grid, {
                    grid_title: '',
                    grid_synopsis: ''
                }, baseUrl);
            }
        },

        closeModal(target = null) {
            window.Modal.close(this.modals, target);
        },

        async syncModules() {
            if (this.isSyncing) return;
            this.isSyncing = true;

            try {
                const { ok, data } = await window.Fetch.post('/admin/modules/sync', {}, {
                    loading: true,
                    loadingTitle: 'Synchronising Modules...',
                    loadingHtml: 'Scanning resources/views/modules for manifest declarations...'
                });

                if (ok) {
                    if (window.Swal && typeof window.Swal.toast === 'function') {
                        window.Swal.toast('success', data?.message || 'Modules synchronised successfully.');
                    }
                    await this.refreshDOM();
                }
            } catch (err) {
                console.error('[BookBuilderManager] Module sync error:', err);
            } finally {
                this.isSyncing = false;
            }
        },

        // 5. DOM Refresh Handler (sanitize = false)
        async refreshDOM() {
            if (typeof window.updateDOM === 'function') {
                await window.updateDOM(this.$refs, ['bookBuilderWrapper', 'booksTableContent', 'gridsTableContent'], () => {
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