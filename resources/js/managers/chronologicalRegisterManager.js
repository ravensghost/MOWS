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
            this.filterRecords();
        });
    },

    initFuse() {
        // Leverage the standardized global window.Search helper (powered by Fuse.js)
        if (this.$refs.serviceRecordsSearchData && window.Search) {
            try {
                // If serviceRecordsSearchData holds a catalog array, we can use Search or initialise via Fuse directly
                this.searchHelperInstance = window.Search.create('[data-service-record-item]', ['recipient', 'event_type', 'award_name', 'description'], {
                    threshold: 0.35
                });
            } catch (e) {
                this.searchHelperInstance = null;
            }
        }
    },

    filterRecords() {
        const q = this.searchQuery.trim();
        if (!q || !this.searchHelperInstance) {
            this.matchedRecordIds = null;
            return;
        }
        const results = this.searchHelperInstance.search(q);
        this.matchedRecordIds = results ? results.map(id => Number(id)) : [];
    },

    matchesRecord(id) {
        if (!this.searchQuery.trim() || this.matchedRecordIds === null) return true;
        return this.matchedRecordIds.includes(Number(id));
    },

    openIssueModal(tab = 'award') {
        this.actionTab = tab;
        this.issueModalOpen = true;
    },

    get filteredAwards() {
        return this.awards.filter(award => {
            if (this.recipientType === 'user') {
                return award.master_group === 'OOC' || award.master_group === 'BOTH';
            } else {
                return award.master_group === 'IC' || award.master_group === 'BOTH';
            }
        });
    }
});