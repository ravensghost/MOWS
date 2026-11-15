// resources/js/managers/chronologicalRegisterManager.js
export default (awardsList = []) => ({
    // 1. State Properties Upfront (Unified Modals & Search Contract)
    modals: {
        register: {
            open: false,
            mode: 'create',
            id: '',
            action: '/admin/chronological-registers',
            method: 'POST'
        }
    },
    issueModalOpen: false,
    actionTab: 'award',
    recipientType: 'user',
    awards: awardsList || [],
    searchQuery: '',
    searchHelperInstance: null,
    matchedRecordIds: null,

    // 2. Lifecycle Hook
    init() {
        this.initFuse();
        this.$watch('searchQuery', () => {
            this.performSearch();
        });
    },

    // 3. Search & Filtering (Fuse.js Integration)
    initFuse() {
        if (this.$refs.serviceRecordsSearchData && window.Search) {
            try {
                this.searchHelperInstance = window.Search.create(
                    '[data-service-record-item]',
                    ['recipient', 'event_type', 'award_name', 'description'],
                    { threshold: 0.35 }
                );
            } catch (e) {
                this.searchHelperInstance = null;
            }
        }
    },

    performSearch() {
        const q = this.searchQuery.trim();
        if (!q || !this.searchHelperInstance) {
            this.matchedRecordIds = null;
            return;
        }
        const results = this.searchHelperInstance.search(q);
        this.matchedRecordIds = results ? results.map(id => Number(id)) : [];
    },

    isItemVisible(id) {
        if (!this.searchQuery.trim() || this.matchedRecordIds === null) return true;
        return this.matchedRecordIds.includes(Number(id));
    },

    get filteredAwards() {
        return this.awards.filter(award => {
            if (this.recipientType === 'user') {
                return award.master_group === 'OOC' || award.master_group === 'BOTH';
            } else {
                return award.master_group === 'IC' || award.master_group === 'BOTH';
            }
        });
    },

    // 4. Modal Handlers (Using window.Modal utility)
    openModal(tab = 'award', mode = 'create', data = {}) {
        this.actionTab = tab;
        const baseUrl = '/admin/chronological-registers';
        if (mode === 'edit') {
            window.Modal.edit(this.modals.register, data, (id) => `${baseUrl}/${id}`);
            this.modals.register.method = 'PUT';
        } else {
            window.Modal.create(this.modals.register, {}, baseUrl);
            this.modals.register.method = 'POST';
        }
        this.issueModalOpen = true;
        this.modals.register.open = true;
    },

    closeModal() {
        window.Modal.close(this.modals.register);
        this.issueModalOpen = false;
        this.modals.register.open = false;
    },

    // 5. DOM Refresh Handler (sanitize = false)
    async refreshDOM() {
        if (typeof window.updateDOM === 'function') {
            await window.updateDOM(this.$refs, ['serviceRecordsSearchData', 'chronologicalRegistersWrapper'], () => {
                this.closeModal();
                this.$nextTick(() => {
                    this.initFuse();
                    this.performSearch();
                });
            }, false);
        } else {
            this.closeModal();
        }
    }
});