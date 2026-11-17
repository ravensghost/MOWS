// resources/js/managers/publicationManager.js

export default function publicationManager() {
    return {
        // 1. State Properties Upfront (Unified Modals & Search Contract)
        modals: {
            shelf: {
                isOpen: false,
                open: false,
                mode: 'create',
                id: '',
                shelf_title: '',
                action: '/admin/shelves',
                method: 'POST'
            },
            volume: {
                isOpen: false,
                open: false,
                mode: 'create',
                id: '',
                volume_title: '',
                volume_synopsis: '',
                publication_state_id: '',
                ic_day: '',
                ic_time: '',
                shelf_id: '',
                action: '/admin/volumes',
                method: 'POST'
            },
            move: {
                isOpen: false,
                open: false,
                mode: 'edit',
                id: '',
                folio_id: '',
                volume_id: '',
                action: '',
                method: 'PUT'
            },
            status: {
                isOpen: false,
                open: false,
                mode: 'create',
                id: '',
                state_title: '',
                book_url: 'primary',
                state_sort_order: 0,
                action: '/admin/publication-states',
                method: 'POST'
            }
        },
        searchQuery: '',
        searchHelperInstance: null,
        filteredIds: null,
        matchedShelfIds: null,
        isLoading: false,

        // 2. Lifecycle Hook
        init() {
            this.initSearch();
            this.$watch('searchQuery', () => this.filterLibrary());

            window.addEventListener('open-volume-modal', (event) => {
                if (event.detail) {
                    this.openModal(event.detail.mode || 'edit', event.detail, 'volume');
                }
            });

            window.addEventListener('open-move-folio-modal', (event) => {
                if (event.detail) {
                    this.openModal('edit', event.detail, 'move');
                }
            });
        },

        // 3. Search & Filtering (Fuse.js Integration)
        initSearch() {
            if (this.$refs?.publicationSearchData && window.Search) {
                try {
                    const items = JSON.parse(this.$refs.publicationSearchData.textContent || '[]');
                    this.searchHelperInstance = window.Search.create(
                        items,
                        ['shelf_title', 'volumes.volume_title', 'volumes.volume_synopsis'],
                        { threshold: 0.35 }
                    );
                } catch (e) {
                    this.searchHelperInstance = null;
                }
            }
        },

        initFuse() {
            this.initSearch();
        },

        filterLibrary() {
            const q = this.searchQuery.trim();
            if (!q || !this.searchHelperInstance) {
                this.matchedShelfIds = null;
                this.filteredIds = null;
                return;
            }
            const results = this.searchHelperInstance.search(q);
            const ids = results ? results.map(Number) : [];
            this.matchedShelfIds = ids;
            this.filteredIds = ids;
        },

        isItemVisible(id) {
            return this.matchesShelf(id);
        },

        matchesShelf(id, title = '') {
            const q = this.searchQuery.trim().toLowerCase();
            if (!q) return true;
            return this.matchedShelfIds !== null
                ? this.matchedShelfIds.includes(Number(id))
                : String(title || '').toLowerCase().includes(q);
        },

        matchesVolume(title = '', synopsis = '') {
            const q = this.searchQuery.trim().toLowerCase();
            if (!q) return true;
            return String(title || '').toLowerCase().includes(q)
                || String(synopsis || '').toLowerCase().includes(q);
        },

        // 4. Modal Handlers (Using window.Modal utility)
        openModal(mode = 'create', data = {}, type = 'shelf') {
            if (type === 'shelf') {
                const baseUrl = '/admin/shelves';
                if (mode === 'edit') {
                    window.Modal.edit(this.modals.shelf, data, (id) => `${baseUrl}/${id}`);
                } else {
                    window.Modal.create(this.modals.shelf, { shelf_title: '' }, baseUrl);
                }
                return;
            }

            if (type === 'status' || type === 'publication-state') {
                const baseUrl = '/admin/publication-states';
                if (mode === 'edit') {
                    window.Modal.edit(this.modals.status, data, (id) => `${baseUrl}/${id}`);
                } else {
                    window.Modal.create(this.modals.status, {
                        state_title: '',
                        book_url: 'primary',
                        state_sort_order: 0
                    }, baseUrl);
                }
                return;
            }

            if (type === 'volume') {
                const baseUrl = '/admin/volumes';
                if (mode === 'edit') {
                    window.Modal.edit(this.modals.volume, {
                        ...data,
                        publication_state_id: String(data.publication_state_id ?? ''),
                        shelf_id: String(data.shelf_id ?? '')
                    }, (id) => `${baseUrl}/${id}`);
                } else {
                    window.Modal.create(this.modals.volume, {
                        volume_title: '',
                        volume_synopsis: '',
                        publication_state_id: '',
                        ic_day: '',
                        ic_time: '',
                        shelf_id: String(data.shelf_id ?? '')
                    }, baseUrl);
                }

                this.$nextTick(() => {
                    if (this.$refs?.volumeSynopsisTextarea && window.Autosize) {
                        window.Autosize.update(this.$refs.volumeSynopsisTextarea);
                    }
                });
                return;
            }

            if (type === 'move') {
                const folioId = data.id ?? data.folio_id ?? '';
                window.Modal.edit(this.modals.move, {
                    id: folioId,
                    folio_id: folioId,
                    volume_id: String(data.volume_id ?? '')
                }, (id) => `/admin/folios/${id}/move`);
            }
        },

        closeModal(type = null) {
            window.Modal.close(this.modals, type);
        },

        // 5. DOM Refresh Handler (sanitize = false)
        async refreshDOM(responseData = {}) {
            const refs = ['publicationSearchData', 'shelvesWrapper', 'looseVolumesWrapper', 'statusesWrapper'];
            if (typeof window.updateDOM === 'function') {
                await window.updateDOM(this.$refs, refs, () => {
                    this.closeModal();
                    this.initSearch();
                    this.filterLibrary();

                    if (responseData?.trigger_review && responseData?.data) {
                        window.dispatchEvent(new CustomEvent('open-volume-review', {
                            detail: {
                                volumeId: responseData.data.id,
                                volumeTitle: responseData.data.volume_title
                            }
                        }));
                    }
                }, false);
            } else {
                this.closeModal();
                this.initSearch();
                this.filterLibrary();
            }
        }
    };
}