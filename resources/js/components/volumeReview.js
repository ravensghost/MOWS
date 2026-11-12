// resources/js/components/volumeReview.js
export default () => ({
    reviewModalOpen: false,
    volumeId: null,
    volumeTitle: '',
    recipients: [],
    selectedRecipients: [],
    registerEventMode: 'milestone',
    chronicleText: '',
    isLoading: false,

    async openModal(mode = 'edit', data = {}) {
        this.volumeId = data.volumeId ?? data.volume_id ?? null;
        this.volumeTitle = data.volumeTitle ?? data.volume_title ?? '';
        this.isLoading = true;
        this.reviewModalOpen = true;
        this.selectedRecipients = [];
        this.chronicleText = '';
        this.registerEventMode = 'milestone';

        try {
            const { ok, data: responseData } = await window.Fetch.get(`/admin/volumes/${this.volumeId}/review`, {
                loading: false,
                showError: false
            });

            if (ok && responseData) {
                this.recipients = responseData.recipients || [];
                this.selectedRecipients = this.recipients.map(r => `${r.type}:${r.id}`);
                if (responseData.volume_synopsis) {
                    this.chronicleText = responseData.volume_synopsis;
                }
            } else {
                window.Swal.error('Error', 'Failed to load participating characters and co-writers.');
                this.reviewModalOpen = false;
            }
        } catch (error) {
            window.Swal.error('Error', 'A network error occurred.');
            this.reviewModalOpen = false;
        } finally {
            this.isLoading = false;
        }
    },

    closeModal() {
        this.reviewModalOpen = false;
    },

    async submitReview() {
        if (this.selectedRecipients.length === 0) {
            window.Swal.fire({
                icon: 'warning',
                title: 'Warning',
                text: 'Please select at least one recipient.',
                ...window.Swal.themeConfig()
            });
            return;
        }

        const formattedRecipients = this.selectedRecipients.map(item => {
            const [type, id] = item.split(':');
            return { type, id: parseInt(id, 10) };
        });

        window.Swal.loading('Issuing Service Records...', 'Please wait while records are bulk-issued...');

        try {
            const { ok, data } = await window.Fetch.post(`/admin/volumes/${this.volumeId}/review`, {
                register_event_mode: this.registerEventMode,
                chronicle_text: this.chronicleText,
                recipients: formattedRecipients
            }, {
                loading: false,
                showError: false
            });

            if (ok) {
                if (window.Swal && typeof window.Swal.fire === 'function') {
                    window.Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: data.message || 'Service records issued successfully.',
                        showConfirmButton: false,
                        timer: 1200,
                        ...window.Swal.themeConfig()
                    }).then(() => {
                        this.reviewModalOpen = false;
                        this.$dispatch('ajax-success');
                    });
                }
            } else {
                const errorMsg = data.message || 'Failed to issue service records.';
                window.Swal.error('Error', errorMsg);
            }
        } catch (error) {
            window.Swal.error('Error', 'A network error occurred.');
        }
    }
});