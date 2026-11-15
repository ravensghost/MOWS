// resources/js/managers/configurationManager.js
export default (initialAiMode = 'cloud', initialMailMailer = 'smtp') => ({
    // 1. State Properties Upfront (Unified Modals & State Contract)
    modals: {
        config: {
            open: false,
            mode: 'edit',
            action: '/admin/configurations/update',
            method: 'POST'
        }
    },
    ai_mode: initialAiMode,
    mail_mailer: initialMailMailer,
    searchQuery: '',
    searchHelperInstance: null,
    filteredIds: null,

    // 2. Lifecycle Hook
    init() {
        this.initSearch();
    },

    // 3. Search & Filtering (Fuse.js Integration)
    initSearch() {
        if (window.Search && this.$el && this.$el.querySelector('[data-config-item]')) {
            try {
                this.searchHelperInstance = window.Search.create(
                    '[data-config-item]',
                    ['key', 'label'],
                    { threshold: 0.35 }
                );
            } catch (e) {
                this.searchHelperInstance = null;
            }
        }
    },

    isItemVisible(id) {
        if (!this.searchQuery.trim() || !this.searchHelperInstance) return true;
        const results = this.searchHelperInstance.search(this.searchQuery.trim());
        const matchedIds = results ? results.map(res => Number(res.id || res)) : [];
        return matchedIds.includes(Number(id));
    },

    // 4. Modal Handlers (Using window.Modal utility)
    openModal() {
        this.modals.config.open = true;
    },

    closeModal() {
        this.modals.config.open = false;
    },

    // 5. DOM Refresh Handler (sanitize = false)
    async refreshDOM() {
        if (typeof window.updateDOM === 'function') {
            await window.updateDOM(this.$refs, ['configurationsWrapper'], () => {
                this.closeModal();
            }, false);
        } else {
            this.closeModal();
        }
    }
});