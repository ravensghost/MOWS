// resources/js/managers/candidatePetitionManager.js

export default function candidatePetitionManager(config = {}) {
    return {
        // 1. State Properties Upfront (Unified Modals & Search Contract)
        modals: {
            review: {
                isOpen: false,
                open: false,
                mode: 'edit',
                id: '',
                petitionId: '',
                personaId: '',
                personaName: '',
                applicantName: '',
                submittedAt: '',
                answers: [],
                hasParseError: false,
                action: '',
                method: 'POST'
            },
            reassign: {
                isOpen: false,
                open: false,
                mode: 'edit',
                id: '',
                personaId: '',
                personaName: '',
                currentPosId: '',
                currentRankId: '',
                assignment_id: '',
                insignia_id: '',
                action: '',
                method: 'POST'
            }
        },
        i18n: {
            rejectConfirm: config.rejectConfirm || 'Are you sure you want to reject this candidate petition?',
            confirmRejectBtn: config.confirmRejectBtn || 'Yes, Reject',
            rejectFailed: config.rejectFailed || 'Failed to reject candidate petition.'
        },
        activeTab: 'pending',
        searchQuery: '',
        searchHelperInstance: null,
        filteredIds: null,
        isLoading: false,

        // 2. Lifecycle Hook
        init() {
            this.initSearch();
            this.$watch('searchQuery', (val) => {
                if (!val.trim() || !this.searchHelperInstance) {
                    this.filteredIds = null;
                    return;
                }
                const results = this.searchHelperInstance.search(val.trim());
                this.filteredIds = results ? results.map(id => Number(id)) : [];
            });
        },

        // 3. Search & Filtering (Fuse.js Integration)
        initSearch() {
            if (window.Search && this.$el && this.$el.querySelector('[data-search-item], [data-petition-item]')) {
                try {
                    this.searchHelperInstance = window.Search.create(
                        '[data-search-item], [data-petition-item]',
                        ['proposed_persona_name', 'persona_name', 'applicant', 'status', 'petition_status'],
                        { threshold: 0.35 }
                    );
                } catch (e) {
                    this.searchHelperInstance = null;
                }
            }
        },

        isItemVisible(id) {
            if (!this.searchQuery.trim() || this.filteredIds === null) return true;
            return this.filteredIds.includes(Number(id));
        },

        // 4. Modal & Action Handlers (Using window.Modal & window.Fetch)
        openModal(mode = 'edit', data = {}, context = 'review') {
            if (context === 'reassign') {
                const personaId = data.personaId ?? data.persona_id ?? data.id ?? '';
                const currentPosId = String(data.currentPosId ?? data.assignment_id ?? '');
                const currentRankId = String(data.currentRankId ?? data.insignia_id ?? '');

                window.Modal.edit(this.modals.reassign, {
                    id: personaId,
                    personaId: personaId,
                    personaName: data.personaName ?? data.persona_name ?? '',
                    currentPosId: currentPosId,
                    currentRankId: currentRankId,
                    assignment_id: currentPosId,
                    insignia_id: currentRankId
                }, (id) => `/admin/candidate-petitions/personae/${id}/reassign`);
                this.modals.reassign.method = 'POST';
                return;
            }

            const petitionId = data.petitionId ?? data.id ?? '';
            let parsedAnswers = [];
            let hasParseError = false;

            try {
                if (data.base64Answers) {
                    const decoded = JSON.parse(atob(data.base64Answers));
                    parsedAnswers = typeof decoded === 'string' ? JSON.parse(decoded) : decoded;
                } else if (Array.isArray(data.answers)) {
                    parsedAnswers = data.answers;
                }
            } catch (e) {
                parsedAnswers = [];
                hasParseError = true;
            }

            window.Modal.edit(this.modals.review, {
                id: petitionId,
                petitionId: petitionId,
                personaId: data.personaId ?? data.persona_id ?? '',
                personaName: data.personaName ?? data.proposed_persona_name ?? '',
                applicantName: data.applicantName ?? '',
                submittedAt: data.submittedAt ?? data.lodged_at ?? '',
                answers: parsedAnswers,
                hasParseError: hasParseError
            }, (id) => `/admin/candidate-petitions/${id}/approve`); this.modals.review.method = 'POST';
        }, closeModal(context = null) { window.Modal.close(this.modals, context); }, async rejectPetition(petitionId = null) {
            const targetId = petitionId || this.modals.review.petitionId || this.modals.review.id; if (!targetId) return; const result = await window.Swal.confirm(this.i18n.rejectConfirm, 'You cannot revert this action!', this.i18n.confirmRejectBtn); if (!result.isConfirmed) return; try {
                const { ok, data } = await window.Fetch.post(`/admin/candidate-petitions/${targetId}/reject`, {}, {
                    loading: true,
                    loadingTitle: 'Processing...',
                    loadingHtml: 'Rejecting candidate petition...',
                    showError: false
                });

                if (ok) {
                    if (window.Swal && typeof window.Swal.toast === 'function') {
                        window.Swal.toast('success', data?.message || 'Candidate petition rejected.');
                    }
                    await this.refreshDOM();
                } else {
                    window.Swal.error('Error', data?.message || this.i18n.rejectFailed);
                }
            } catch (error) {
                window.Swal.error('Error', this.i18n.rejectFailed);
            }
        },

        // 5. DOM Refresh Handler (sanitize = false)
        async refreshDOM() {
            if (typeof window.updateDOM === 'function') {
                await window.updateDOM(this.$refs, ['pendingAppsTableContent', 'activeRosterTableContent', 'petitionsTableContent'], () => {
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