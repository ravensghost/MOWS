// resources/js/managers/publicationManager.js
export default () => ({
    searchQuery: '',
    searchHelperInstance: null,
    matchedShelfIds: null,

    shelfModal: {
        open: false,
        mode: 'create',
        id: '',
        shelf_title: '',
        action: '/admin/shelves',
        method: 'POST'
    },

    volumeModal: {
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

    moveModal: {
        open: false,
        folio_id: '',
        volume_id: '',
        action: ''
    },

    statusModal: {
        open: false,
        mode: 'create',
        id: '',
        state_title: '',
        action: '/admin/statuses',
        method: 'POST'
    },

    init() {
        this.initFuse();
        this.$watch('searchQuery', () => this.filterLibrary());
    },

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

    openModal(mode = 'create', data = {}, type = 'shelf') {
        if (type === 'shelf') {
            mode === 'edit'
                ? window.Modal.edit(this.shelfModal, data, '/admin/shelves')
                : window.Modal.create(this.shelfModal, { shelf_title: '' }, '/admin/shelves');
            return;
        }

        if (type === 'status') {
            mode === 'edit'
                ? window.Modal.edit(this.statusModal, data, '/admin/statuses')
                : window.Modal.create(this.statusModal, { state_title: '' }, '/admin/statuses');
            return;
        }

        if (type === 'volume') {
            if (mode === 'edit') {
                window.Modal.edit(this.volumeModal, {
                    ...data,
                    publication_state_id: String(data.publication_state_id ?? ''),
                    shelf_id: String(data.shelf_id ?? '')
                }, '/admin/volumes');
            } else {
                window.Modal.create(this.volumeModal, {
                    volume_title: '',
                    volume_synopsis: '',
                    publication_state_id: '',
                    ic_day: '',
                    ic_time: '',
                    shelf_id: String(data.shelf_id ?? '')
                }, '/admin/volumes');
            }

            this.$nextTick(() => {
                if (this.$refs.volumeSynopsisTextarea && window.Autosize) {
                    window.Autosize.update(this.$refs.volumeSynopsisTextarea);
                }
            });
            return;
        }

        if (type === 'move') {
            this.moveModal.folio_id = data.id || '';
            this.moveModal.volume_id = String(data.volume_id ?? '');
            this.moveModal.action = `/admin/folios/${this.moveModal.folio_id}/move`;
            this.moveModal.open = true;
        }
    },

    async refreshDOM(responseData = {}) {
        const refs = ['publicationSearchData', 'shelvesWrapper', 'looseVolumesWrapper', 'statusesWrapper'];
        await window.updateDOM(this.$refs, refs, () => {
            window.Modal.close(this.shelfModal);
            window.Modal.close(this.volumeModal);
            window.Modal.close(this.moveModal);
            window.Modal.close(this.statusModal);
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
    }
});