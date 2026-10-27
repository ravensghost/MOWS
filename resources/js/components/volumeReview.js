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
            const response = await fetch(`/admin/books/${bookId}/review`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            });
            if (response.ok) {
                const data = await response.json();
                this.recipients = data.recipients || [];
                this.selectedRecipients = this.recipients.map(r => `${r.type}:${r.id}`);
            } else {
                window.Swal.fire('Error', 'Failed to load participating characters and co-writers.', 'error');
                this.reviewModalOpen = false;
            }
        } catch (error) {
            window.Swal.fire('Error', 'A network error occurred.', 'error');
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
            window.Swal.fire('Warning', 'Please select at least one recipient.', 'warning');
            return;
        }

        const formattedRecipients = this.selectedRecipients.map(item => {
            const [type, id] = item.split(':');
            return { type, id: parseInt(id) };
        });

        window.Swal.fire({
            title: 'Issuing Service Records...',
            html: 'Please wait while records are bulk-issued...',
            allowOutsideClick: false,
            showConfirmButton: false,
            background: 'var(--theme-panel-bg, #111827)',
            color: 'var(--theme-text-main, #ffffff)',
            didOpen: () => window.Swal.showLoading()
        });

        try {
            const response = await fetch(`/admin/books/${this.bookId}/review`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    event_type: this.eventType,
                    description: this.description,
                    recipients: formattedRecipients
                })
            });

            if (response.ok) {
                const data = await response.json();
                window.Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: data.message || 'Service records issued successfully.',
                    background: 'var(--theme-panel-bg, #111827)',
                    color: 'var(--theme-text-main, #ffffff)',
                    showConfirmButton: false,
                    timer: 1200
                }).then(() => {
                    this.reviewModalOpen = false;
                    this.$dispatch('ajax-success');
                });
            } else {
                let errorMsg = 'Failed to issue service records.';
                try {
                    const data = await response.json();
                    errorMsg = data.message || errorMsg;
                } catch (e) { }
                window.Swal.fire('Error', errorMsg, 'error');
            }
        } catch (error) {
            window.Swal.fire('Error', 'A network error occurred.', 'error');
        }
    }
});