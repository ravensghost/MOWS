// resources/js/managers/candidatePetitionManager.js
export default (config = {}) => ({
    // 1. State Properties Upfront (Unified Modals & State Contract)
    modals: {
        review: {
            open: false,
            mode: 'edit',
            petitionId: '',
            personaName: '',
            applicantName: '',
            submittedAt: '',
            answers: [],
            hasParseError: false,
            action: '',
            method: 'POST'
        },
        reassign: {
            open: false,
            mode: 'edit',
            personaId: '',
            personaName: '',
            currentPosId: '',
            currentRankId: '',
            action: '',
            method: 'POST'
        }
    },
    i18n: {
        rejectConfirm: config.rejectConfirm || 'Are you sure you want to reject this candidate petition?',
        confirmRejectBtn: config.confirmRejectBtn || 'Yes, Reject',
        rejectFailed: config.rejectFailed || 'Failed to reject candidate petition.',
    },

    // 2. Lifecycle Hook
    init() {
        // No asynchronous search initialization required for this manager view
    },

    // 3. Search & Filtering (Placeholder per contract)
    isItemVisible(id) {
        return true;
    },

    // 4. Modal Handlers (Using window.Modal utility)
    openModal(mode = 'edit', data = {}, context = 'review') {
        if (context === 'review') {
            this.modals.review.petitionId = data.petitionId || '';
            this.modals.review.personaName = data.personaName || '';
            this.modals.review.applicantName = data.applicantName || '';
            this.modals.review.submittedAt = data.submittedAt || '';

            try {
                const decoded = data.base64Answers ? JSON.parse(atob(data.base64Answers)) : [];
                this.modals.review.answers = typeof decoded === 'string' ? JSON.parse(decoded) : decoded;
                this.modals.review.hasParseError = false;
            } catch (e) {
                this.modals.review.answers = [];
                this.modals.review.hasParseError = true;
            }

            this.modals.review.open = true;
        } else if (context === 'reassign') {
            this.modals.reassign.personaId = data.personaId || '';
            this.modals.reassign.personaName = data.personaName || '';
            this.modals.reassign.currentPosId = data.currentPosId || '';
            this.modals.reassign.currentRankId = data.currentRankId || '';
            this.modals.reassign.open = true;
        }
    },

    closeModal(context = 'review') {
        if (context === 'review') {
            window.Modal.close(this.modals.review);
            this.modals.review.open = false;
        } else if (context === 'reassign') {
            window.Modal.close(this.modals.reassign);
            this.modals.reassign.open = false;
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
                const { ok, data } = await window.Fetch.post(`/admin/applications/${this.modals.review.petitionId}/reject`, {}, {
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
    },

    // 5. DOM Refresh Handler (sanitize = false)
    async refreshDOM() {
        if (typeof window.updateDOM === 'function') {
            await window.updateDOM(this.$refs, ['pendingAppsTableContent', 'activeRosterTableContent'], () => {
                this.closeModal('review');
                this.closeModal('reassign');
            }, false);
        } else {
            this.closeModal('review');
            this.closeModal('reassign');
        }
    }
});