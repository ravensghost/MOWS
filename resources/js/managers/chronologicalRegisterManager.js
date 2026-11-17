// resources/js/managers/chronologicalRegisterManager.js

export default function chronologicalRegisterManager(awardsList = []) {
    return {
        // 1. State Properties Upfront (Unified Modals & Search Contract)
        modals: {
            register: {
                isOpen: false,
                open: false,
                mode: 'create',
                id: '',
                persona_id: '',
                user_id: '',
                distinction_id: '',
                scope_target: 'IC',
                register_event_mode: 'award',
                chronicle_text: '',
                action: '/admin/chronological-registers/award',
                method: 'POST'
            }
        },
        actionTab: 'award',
        recipientType: 'character',
        awards: Array.isArray(awardsList) ? awardsList : [],
        searchQuery: '',
        searchHelperInstance: null,
        filteredIds: null,
        isLoading: false,

        // 2. Lifecycle Hook
        init() {
            this.initSearch();
            this.$watch('searchQuery', () => {
                this.performSearch();
            });
            this.$watch('actionTab', (tab) => {
                if (this.modals.register.mode === 'create') {
                    this.modals.register.action = this.resolveTabActionUrl(tab);
                }
            });
        },

        // 3. Search & Filtering (Fuse.js Integration)
        initSearch() {
            if (window.Search && this.$el && this.$el.querySelector('[data-search-item], [data-service-record-item]')) {
                try {
                    this.searchHelperInstance = window.Search.create(
                        '[data-search-item], [data-service-record-item]',
                        ['recipient', 'event_type', 'register_event_mode', 'award_name', 'description', 'chronicle_text'],
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

        performSearch() {
            const q = this.searchQuery.trim();
            if (!q || !this.searchHelperInstance) {
                this.filteredIds = null;
                return;
            }
            const results = this.searchHelperInstance.search(q);
            this.filteredIds = results ? results.map(id => Number(id)) : [];
        },

        isItemVisible(id) {
            if (!this.searchQuery.trim() || this.filteredIds === null) return true;
            return this.filteredIds.includes(Number(id));
        },

        get filteredAwards() {
            return this.awards.filter(award => {
                const scope = (award.scope_target || award.master_group || 'BOTH').toUpperCase();
                if (this.recipientType === 'user') {
                    return scope === 'OOC' || scope === 'BOTH';
                }
                return scope === 'IC' || scope === 'BOTH';
            });
        },

        resolveTabActionUrl(tab) {
            return matchTabUrl(tab);
        },

        // 4. Modal & Action Handlers (Using window.Modal utility)
        openModal(tab = 'award', mode = 'create', data = {}) {
            this.actionTab = tab;
            const baseUrl = '/admin/chronological-registers';

            if (mode === 'edit') {
                window.Modal.edit(this.modals.register, {
                    id: data.id,
                    persona_id: data.persona_id ?? '',
                    user_id: data.user_id ?? '',
                    distinction_id: data.distinction_id ?? '',
                    scope_target: data.scope_target ?? 'IC',
                    register_event_mode: data.register_event_mode ?? tab,
                    chronicle_text: data.chronicle_text ?? ''
                }, (id) => `${baseUrl}/${id}`);
            } else {
                const createUrl = this.resolveTabActionUrl(tab);
                window.Modal.create(this.modals.register, {
                    persona_id: '',
                    user_id: '',
                    distinction_id: '',
                    scope_target: this.recipientType === 'user' ? 'OOC' : 'IC',
                    register_event_mode: tab,
                    chronicle_text: '',
                    ...data
                }, createUrl);
            }
        },

        closeModal() {
            window.Modal.close(this.modals.register);
        },

        // 5. DOM Refresh Handler (sanitize = false)
        async refreshDOM() {
            if (typeof window.updateDOM === 'function') {
                await window.updateDOM(this.$refs, ['serviceRecordsSearchData', 'chronologicalRegistersWrapper'], () => {
                    this.closeModal();
                    this.$nextTick(() => {
                        this.initSearch();
                        this.performSearch();
                    });
                }, false);
            } else {
                this.closeModal();
            }
        }
    };
}

function matchTabUrl(tab) {
    switch (tab) {
        case 'promotion':
        case 'rank':
            return '/admin/chronological-registers/promotion';
        case 'transfer':
            return '/admin/chronological-registers/transfer';
        case 'status':
        case 'status_change':
            return '/admin/chronological-registers/status-change';
        case 'manual':
        case 'manual_entry':
            return '/admin/chronological-registers/manual-entry';
        case 'award':
        default:
            return '/admin/chronological-registers/award';
    }
}