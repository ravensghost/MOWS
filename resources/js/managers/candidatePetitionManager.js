// resources/js/managers/candidatePetitionManager.js
export default (config = {}) => ({
    reviewModalOpen: false,
    appId: '',
    charName: '',
    applicantName: '',
    submittedAt: '',
    answers: [],
    hasParseError: false,

    reassignModalOpen: false,
    charId: '',
    currentPosId: '',
    currentRankId: '',

    i18n: {
        rejectConfirm: config.rejectConfirm || 'Are you sure you want to reject this application?',
        confirmRejectBtn: config.confirmRejectBtn || 'Yes, Reject',
        rejectFailed: config.rejectFailed || 'Failed to reject application.',
    },

    openReviewModal(data = {}) {
        this.appId = data.appId || '';
        this.charName = data.charName || '';
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
    },

    openReassignModal(data = {}) {
        this.charId = data.charId || '';
        this.charName = data.charName || '';
        this.currentPosId = data.currentPosId || '';
        this.currentRankId = data.currentRankId || '';
        this.reassignModalOpen = true;
    },

    async refreshDOM() {
        // Updated to use window.updateDOM with sanitize = false (replacing old _US helper)
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

    async rejectApplication() {
        const result = await window.Swal.confirm(
            this.i18n.rejectConfirm,
            'You cannot revert this action!',
            this.i18n.confirmRejectBtn
        );

        if (result.isConfirmed) {
            try {
                const { ok, data } = await window.Fetch.post(`/admin/applications/${this.appId}/reject`, {}, {
                    loading: true,
                    loadingTitle: 'Processing...',
                    loadingHtml: 'Rejecting application...',
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