// resources/js/managers/chronologicalRegisterManager.js
export default (awardsList = []) => ({
    issueModalOpen: false,
    actionTab: 'award',
    recipientType: 'user',
    awards: awardsList,

    searchQuery: '',
    searchHelperInstance: null,
    matchedRecordIds: null,

    init() {
        this.initFuse();
        this.$watch('searchQuery', () => {
            this.performSearch();
        });
    },

    initFuse() {
        if (this.$refs.chronologicalRegistersSearchData && window.Search) {
            try {
                this.searchHelperInstance = window.Search.create('[data-chronological-register-item]', ['recipient', 'register_event_mode', 'distinction_title', 'chronicle_text'], {
                    threshold: 0.35
                });
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

    openModal(tab = 'award') {
        this.actionTab = tab;
        this.issueModalOpen = true;
    },

    get filteredAwards() {
        return this.awards.filter(award => {
            const target = award.scope_target || award.master_group || 'BOTH';
            if (this.recipientType === 'user') {
                return target === 'OOC' || target === 'BOTH';
            } else {
                return target === 'IC' || target === 'BOTH';
            }
        });
    }
});