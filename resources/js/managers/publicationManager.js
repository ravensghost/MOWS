// resources/js/managers/publicationManager.js
export default () => ({
    // 1. State Properties Upfront (Unified Modals & Search Contract)
    modals: {
        shelf: {
            open: false,
            mode: 'create',
            id: '',
            shelf_title: '',
            action: '/admin/shelves',
            method: 'POST'
        },
        volume: {
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
            open: false,
            folio_id: '',
            volume_id: '',
            action: ''
        },
        status: {
            open: false,
            mode: 'create',
            id: '',
            state_title: '',
            action: '/admin/statuses',
            method: 'POST'
        }
    },
    searchQuery: '',
    searchHelperInstance: null,
    matchedShelfIds: null,

    // 2. Lifecycle Hook
    init() {
        this.initFuse();
        this.$watch('searchQuery', () => this.filterLibrary());
    },

    // 3. Search & Filtering (Fuse.js Integration)
    initFuse() {
        if (this.$refs.publicationSearchData && window.Search) {
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

    filterLibrary() {
        const q = this.searchQuery.trim();
        if (!q || !this.searchHelperInstance) {
            this.matchedShelfIds = null;
            return;
        }
        const results = this.searchHelperInstance.search(q);
        this.matchedShelfIds = results ? results.map(Number) : [];
    },

    matchesShelf(id, title = '') {
        const q = this.searchQuery.trim().toLowerCase();
        if (!q) return true;
        return this.matchedShelfIds !== null
            ? this.matchedShelfIds.includes(Number(id))
            : String(title).toLowerCase().includes(q);
    },

    matchesVolume(title = '', synopsis = '') {
        const q = this.searchQuery.trim().toLowerCase();
        if (!q) return true;
        return String(title).toLowerCase().includes(q) || String(synopsis).toLowerCase().includes(q);
    },

    // 4. Modal Handlers (Using window.Modal utility)
    openModal(mode = 'create', data = {}, type = 'shelf') {
        if (type === 'shelf') {
            if (mode === 'edit') {
                window.Modal.edit(this.modals.shelf, data, (id) => `/admin/shelves/${id}`);
                this.modals.shelf.method = 'PUT';
            } else {
                window.Modal.create(this.modals.shelf, { shelf_title: '' }, '/admin/shelves');
                this.modals.shelf.method = 'POST';
            }
            this.modals.shelf.open = true;
            return;
        }

        if (type === 'status') {
            if (mode === 'edit') {
                window.Modal.edit(this.modals.status, data, (id) => `/admin/statuses/${id}`);
                this.modals.status.method = 'PUT';
            } else {
                window.Modal.create(this.modals.status, { state_title: '' }, '/admin/statuses');
                this.modals.status.method = 'POST';
            }
            this.modals.status.open = true;
            return;
        }

        if (type === 'volume') {
            if (mode === 'edit') {
                window.Modal.edit(this.modals.volume, {
                    ...data,
                    publication_state_id: String(data.publication_state_id ?? ''),
                    shelf_id: String(data.shelf_id ?? '')
                }, (id) => `/admin/volumes/${id}`);
                this.modals.volume.method = 'PUT';
            } else {
                window.Modal.create(this.modals.volume, {
                    volume_title: '',
                    volume_synopsis: '',
                    publication_state_id: '',
                    ic_day: '',
                    ic_time: '',
                    shelf_id: String(data.shelf_id ?? '')
                }, '/admin/volumes');
                this.modals.volume.method = 'POST';
            }

            this.modals.volume.open = true;
            this.$nextTick(() => {
                if (this.$refs.volumeSynopsisTextarea && window.Autosize) {
                    window.Autosize.update(this.$refs.volumeSynopsisTextarea);
                }
            });
            return;
        }

        if (type === 'move') {
            this.modals.move.folio_id = data.id || '';
            this.modals.move.volume_id = String(data.volume_id ?? '');
            this.modals.move.action = `/admin/folios/${this.modals.move.folio_id}/move`;
            this.modals.move.open = true;
        }
    },

    // 5. DOM Refresh Handler (sanitize = false)
    async refreshDOM(responseData = {}) {
        const refs = ['publicationSearchData', 'shelvesWrapper', 'looseVolumesWrapper', 'statusesWrapper'];
        if (typeof window.updateDOM === 'function') {
            await window.updateDOM(this.$refs, refs, () => {
                window.Modal.close(this.modals.shelf);
                window.Modal.close(this.modals.volume);
                window.Modal.close(this.modals.move);
                window.Modal.close(this.modals.status);
                this.modals.shelf.open = false;
                this.modals.volume.open = false;
                this.modals.move.open = false;
                this.modals.status.open = false;

                this.initFuse();
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
            window.Modal.close(this.modals.shelf);
            window.Modal.close(this.modals.volume);
            window.Modal.close(this.modals.move);
            window.Modal.close(this.modals.status);
            this.initFuse();
            this.filterLibrary();
        }
    }
});