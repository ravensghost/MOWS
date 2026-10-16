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

    refreshDOM() {
        window.updateDOM_US(this.$refs, ['pendingAppsTableContent', 'activeRosterTableContent'], () => {
            this.reviewModalOpen = false;
            this.reassignModalOpen = false;
        });
    },

    rejectApplication() {
        window.Swal.fire({
            title: this.i18n.rejectConfirm,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: 'var(--theme-danger, #ef4444)',
            cancelButtonColor: 'var(--theme-panel-header-bg, #1f2937)',
            confirmButtonText: this.i18n.confirmRejectBtn,
            background: 'var(--theme-panel-bg, #111827)',
            color: 'var(--theme-text-main, #ffffff)'
        }).then((result) => {
            if (result.isConfirmed) {
                window.Swal.showLoading();
                fetch(`/admin/applications/${this.appId}/reject`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                        'Accept': 'application/json'
                    }
                }).then(response => {
                    if (response.ok) {
                        this.refreshDOM();
                    } else {
                        window.Swal.fire('Error', this.i18n.rejectFailed, 'error');
                    }
                }).catch(() => {
                    window.Swal.fire('Error', this.i18n.rejectFailed, 'error');
                });
            }
        });
    }
});