// resources/js/managers/candidatePetitionManager.js
export default (config = {}) => ({
    reviewModalOpen: false,
    petitionId: '',
    personaName: '',
    applicantName: '',
    submittedAt: '',
    answers: [],
    hasParseError: false,

    reassignModalOpen: false,
    personaId: '',
    currentPosId: '',
    currentRankId: '',

    i18n: {
        rejectConfirm: config.rejectConfirm || 'Are you sure you want to reject this candidate petition?',
        confirmRejectBtn: config.confirmRejectBtn || 'Yes, Reject',
        rejectFailed: config.rejectFailed || 'Failed to reject candidate petition.',
    },

    openModal(mode = 'edit', data = {}, context = 'review') {
        if (context === 'review') {
            this.petitionId = data.petitionId || '';
            this.personaName = data.personaName || '';
            this.applicantName = data.applicantName || '';
            this.submittedAt = data.submittedAt || '';

            try {
                const decoded = data.base64Answers ? JSON.parse(atob(data.base64Answers)) : [];
                this.answers = typeof decoded === 'string' ? JSON.parse(decoded) : decoded;
                this.hasParseError = false;
            } catch (e) {
                this.answers = [];
                this.hasParseError = true;
            }

            this.reviewModalOpen = true;
        } else if (context === 'reassign') {
            this.personaId = data.personaId || '';
            this.personaName = data.personaName || '';
            this.currentPosId = data.currentPosId || '';
            this.currentRankId = data.currentRankId || '';
            this.reassignModalOpen = true;
        }
    },

    async refreshDOM() {
        if (typeof window.updateDOM === 'function') {
            await window.updateDOM(this.$refs, ['pendingAppsTableContent', 'activeRosterTableContent'], () => {
                this.reviewModalOpen = false;
                this.reassignModalOpen = false;
            }, false);
        } else {
            this.reviewModalOpen = false;
            this.reassignModalOpen = false;
        }
    },

    async rejectPetition() {
        const result = await window.Swal.confirm(
            this.i18n.rejectConfirm,
            'You cannot revert this action!',
            this.i18n.confirmRejectBtn
        );

        if (result.isConfirmed) {
            try {
                const { ok, data } = await window.Fetch.post(`/admin/applications/${this.petitionId}/reject`, {}, {
                    loading: true,
                    loadingTitle: 'Processing...',
                    loadingHtml: 'Rejecting candidate petition...',
                    showError: false
                });

                if (ok) {
                    await this.refreshDOM();
                } else {
                    window.Swal.error('Error', data.message || this.i18n.rejectFailed);
                }
            } catch (error) {
                window.Swal.error('Error', this.i18n.rejectFailed);
            }
        }
    }
});