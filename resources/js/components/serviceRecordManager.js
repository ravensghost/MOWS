export default (awardsList = []) => ({
    issueModalOpen: false,
    actionTab: 'award',
    recipientType: 'user',
    awards: awardsList,

    searchQuery: '',
    fuseInstance: null,
    matchedRecordIds: null,

    init() {
        this.initFuse();
    },

    initFuse() {
        if (this.$refs.serviceRecordsSearchData && window.Fuse) {
            try {
                const catalog = JSON.parse(this.$refs.serviceRecordsSearchData.textContent || '[]');
                this.fuseInstance = new window.Fuse(catalog, {
                    keys: ['recipient', 'event_type', 'award_name', 'description'],
                    threshold: 0.35,
                    ignoreLocation: true
                });
            } catch (e) {
                this.fuseInstance = null;
            }
        }
    },

    filterRecords() {
        const q = this.searchQuery.trim();
        if (!q || !this.fuseInstance) {
            this.matchedRecordIds = null;
            return;
        }
        const results = this.fuseInstance.search(q);
        this.matchedRecordIds = results.map(r => Number(r.item.id));
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