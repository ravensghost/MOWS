// resources/js/components/volumeReview.js
export default () => ({
    reviewModalOpen: false,
    bookId: null,
    bookTitle: '',
    recipients: [],
    selectedRecipients: [],
    eventType: 'milestone',
    description: '',
    isLoading: false,

    async openReviewModal(bookId, bookTitle) {
        this.bookId = bookId;
        this.bookTitle = bookTitle;
        this.isLoading = true;
        this.reviewModalOpen = true;
        this.selectedRecipients = [];
        this.description = '';

        try {
            const { ok, data } = await window.Fetch.get(`/admin/books/${bookId}/review`, {
                loading: false,
                showError: false
            });

            if (ok && data) {
                this.recipients = data.recipients || [];
                this.selectedRecipients = this.recipients.map(r => `${r.type}:${r.id}`);
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
            return { type, id: parseInt(id) };
        });

        // Use window.Swal helper for loading state
        window.Swal.loading('Issuing Service Records...', 'Please wait while records are bulk-issued...');

        try {
            const { ok, data } = await window.Fetch.post(`/admin/books/${this.bookId}/review`, {
                event_type: this.eventType,
                description: this.description,
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