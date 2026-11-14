// resources/js/managers/candidatePetitionManager.js
export default (config = {}) => ({
    routes: {
        index: config.routes?.index || '/admin/candidate-petitions',
        store: config.routes?.store || '/admin/candidate-petitions',
        baseUrl: config.routes?.baseUrl || '/admin/candidate-petitions',
        evaluationsUrl: config.routes?.evaluationsUrl || '/admin/candidate-petitions',
    },

    petitions: config.initialPetitions || [],
    insignias: config.insignias || [],
    assignments: config.assignments || [],
    personas: config.personas || [],

    statusFilter: 'all',
    searchQuery: '',

    isModalOpen: false,
    modalView: 'review',
    reviewModalOpen: false,
    reassignModalOpen: false,

    activePetition: {
        petitionId: '',
        personaId: '',
        personaName: '',
        petition_status: 'pending',
        insignia_id: '',
        assignment_id: '',
        entries: [],
        evaluations: [],
    },

    evaluationNotes: '',

    petitionId: '',
    personaName: '',
    applicantName: '',
    submittedAt: '',
    answers: [],
    hasParseError: false,

    personaId: '',
    currentPosId: '',
    currentRankId: '',

    i18n: {
        rejectConfirm: config.rejectConfirm || 'Are you sure you want to reject this candidate petition?',
        confirmRejectBtn: config.confirmRejectBtn || 'Yes, Reject',
        rejectFailed: config.rejectFailed || 'Failed to reject candidate petition.',
        approveFailed: config.approveFailed || 'Failed to approve candidate petition.',
        saveFailed: config.saveFailed || 'Failed to update candidate petition.',
    },

    openModal(mode = 'edit', data = {}, context = 'review') {
        this.modalView = context;
        this.evaluationNotes = '';

        this.activePetition = {
            petitionId: data.petitionId || '',
            personaId: data.personaId || '',
            personaName: data.personaName || '',
            petition_status: data.petition_status || 'pending',
            insignia_id: data.insignia_id || data.currentRankId || '',
            assignment_id: data.assignment_id || data.currentPosId || '',
            entries: Array.isArray(data.entries) ? data.entries : [],
            evaluations: Array.isArray(data.evaluations) ? data.evaluations : [],
        };

        if (context === 'review') {
            this.petitionId = data.petitionId || '';
            this.personaName = data.personaName || '';
            this.applicantName = data.applicantName || '';
            this.submittedAt = data.submittedAt || '';

            if (data.base64Answers) {
                try {
                    const decoded = JSON.parse(atob(data.base64Answers));
                    this.answers = typeof decoded === 'string' ? JSON.parse(decoded) : decoded;
                    this.activePetition.entries = this.answers;
                    this.hasParseError = false;
                } catch (e) {
                    this.answers = [];
                    this.hasParseError = true;
                }
            } else {
                this.answers = this.activePetition.entries;
                this.hasParseError = false;
            }

            this.reviewModalOpen = true;
            this.reassignModalOpen = false;
        } else if (context === 'reassign') {
            this.personaId = data.personaId || '';
            this.personaName = data.personaName || '';
            this.currentPosId = data.assignment_id || data.currentPosId || '';
            this.currentRankId = data.insignia_id || data.currentRankId || '';
            this.reassignModalOpen = true;
            this.reviewModalOpen = false;
        }

        this.isModalOpen = true;
    },

    closeModal() {
        this.isModalOpen = false;
        this.reviewModalOpen = false;
        this.reassignModalOpen = false;
        this.evaluationNotes = '';
    },

    async refreshPetitions() {
        await this.refreshDOM();
    },

    async refreshDOM() {
        if (typeof window.updateDOM === 'function') {
            await window.updateDOM(
                this.$refs,
                ['candidate-petitions-tbody', 'pendingAppsTableContent', 'activeRosterTableContent'],
                () => {
                    this.closeModal();
                },
                false
            );
        } else {
            this.closeModal();
            window.location.reload();
        }
    },

    async approvePetition(targetId = null) {
        const id = targetId || this.activePetition.petitionId || this.petitionId;
        if (!id) return;

        const payload = {
            assignment_id: this.activePetition.assignment_id || this.assignments[0]?.id || null,
            insignia_id: this.activePetition.insignia_id || this.insignias[0]?.id || null,
            evaluation_commentary: this.evaluationNotes || null,
        };

        try {
            const { ok, data } = await window.Fetch.post(`${this.routes.baseUrl}/${id}/approve`, payload, {
                loading: true,
                loadingTitle: 'Processing...',
                loadingHtml: 'Approving candidate petition...',
                showError: false,
            });

            if (ok) {
                await this.refreshDOM();
            } else {
                window.Swal?.error('Error', data?.message || this.i18n.approveFailed);
            }
        } catch (error) {
            window.Swal?.error('Error', this.i18n.approveFailed);
        }
    },

    async rejectPetition(targetId = null) {
        const id = targetId || this.activePetition.petitionId || this.petitionId;
        if (!id) return;

        const result = await window.Swal.confirm(
            this.i18n.rejectConfirm,
            'You cannot revert this action!',
            this.i18n.confirmRejectBtn
        );

        if (result.isConfirmed) {
            try {
                const { ok, data } = await window.Fetch.post(
                    `${this.routes.baseUrl}/${id}/reject`,
                    { evaluation_commentary: this.evaluationNotes || null },
                    {
                        loading: true,
                        loadingTitle: 'Processing...',
                        loadingHtml: 'Rejecting candidate petition...',
                        showError: false,
                    }
                );

                if (ok) {
                    await this.refreshDOM();
                } else {
                    window.Swal?.error('Error', data?.message || this.i18n.rejectFailed);
                }
            } catch (error) {
                window.Swal?.error('Error', this.i18n.rejectFailed);
            }
        }
    },

    async submitPetition(targetId = null) {
        const id = targetId || this.activePetition.petitionId || this.petitionId;
        if (!id) return;

        const payload = {
            persona_id: this.activePetition.personaId || null,
            proposed_persona_name: this.activePetition.personaName,
            insignia_id: this.activePetition.insignia_id || null,
            assignment_id: this.activePetition.assignment_id || null,
        };

        try {
            const { ok, data } = await window.Fetch.put(`${this.routes.baseUrl}/${id}`, payload, {
                loading: true,
                loadingTitle: 'Saving...',
                loadingHtml: 'Updating candidate petition reassignment...',
                showError: false,
            });

            if (ok) {
                await this.refreshDOM();
            } else {
                window.Swal?.error('Error', data?.message || this.i18n.saveFailed);
            }
        } catch (error) {
            window.Swal?.error('Error', this.i18n.saveFailed);
        }
    },
});