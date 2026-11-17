// resources/js/components/volumeReview.js

export default function volumeReview() {
    return {
        modals: {
            review: {
                isOpen: false,
                open: false,
                mode: 'edit',
                id: null,
                volumeId: null,
                volumeTitle: '',
                recipients: [],
                selectedRecipients: [],
                registerEventMode: 'milestone',
                chronicleText: '',
                action: '',
                method: 'POST'
            }
        },
        isLoading: false,

        init() {
            window.addEventListener('open-volume-review', (event) => {
                if (event.detail) {
                    this.openModal('edit', event.detail);
                }
            });
        },

        async openModal(mode = 'edit', data = {}) {
            const volumeId = data.volumeId ?? data.volume_id ?? data.id ?? null;
            const volumeTitle = data.volumeTitle ?? data.volume_title ?? '';

            if (!volumeId) return;

            this.isLoading = true;

            if (window.Modal) {
                window.Modal.edit(this.modals.review, {
                    id: volumeId,
                    volumeId: volumeId,
                    volumeTitle: volumeTitle,
                    recipients: [],
                    selectedRecipients: [],
                    registerEventMode: 'milestone',
                    chronicleText: ''
                }, (id) => `/admin/volumes/${id}/review`);
                this.modals.review.method = 'POST';
            }

            try {
                const { ok, data: responseData } = await window.Fetch.get(`/admin/volumes/${volumeId}/review`, {
                    loading: false,
                    showError: false
                });

                if (ok && responseData) {
                    const payload = responseData.data ?? responseData;
                    this.modals.review.recipients = payload.recipients || [];
                    this.modals.review.selectedRecipients = this.modals.review.recipients.map(r => `${r.type}:${r.id}`);
                    if (payload.volume_synopsis) {
                        this.modals.review.chronicleText = payload.volume_synopsis;
                    }
                } else {
                    window.Swal.error('Error', 'Failed to load participating characters and co-writers.');
                    this.closeModal();
                }
            } catch (error) {
                window.Swal.error('Error', 'A network error occurred.');
                this.closeModal();
            } finally {
                this.isLoading = false;
            }
        },

        closeModal() {
            if (window.Modal) {
                window.Modal.close(this.modals.review);
            }
        },

        async submitReview() {
            if (this.modals.review.selectedRecipients.length === 0) {
                window.Swal.fire({
                    icon: 'warning',
                    title: 'Warning',
                    text: 'Please select at least one recipient.',
                    ...window.Swal.themeConfig()
                });
                return;
            }

            const formattedRecipients = this.modals.review.selectedRecipients.map(item => {
                const [type, id] = String(item).split(':');
                return { type, id: parseInt(id, 10) };
            });

            window.Swal.loading('Issuing Service Records...', 'Please wait while records are bulk-issued...');

            try {
                const { ok, data } = await window.Fetch.post(`/admin/volumes/${this.modals.review.volumeId}/review`, {
                    register_event_mode: this.modals.review.registerEventMode,
                    chronicle_text: this.modals.review.chronicleText,
                    recipients: formattedRecipients
                }, {
                    loading: false,
                    showError: false
                });

                if (ok) {
                    if (window.Swal && typeof window.Swal.fire === 'function') {
                        await window.Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: data?.message || 'Service records issued successfully.',
                            showConfirmButton: false,
                            timer: 1200,
                            ...window.Swal.themeConfig()
                        });
                    }
                    this.closeModal();
                    this.$dispatch('ajax-success', data);
                } else {
                    window.Swal.error('Error', data?.message || 'Failed to issue service records.');
                }
            } catch (error) {
                window.Swal.error('Error', 'A network error occurred.');
            }
        }
    };
}