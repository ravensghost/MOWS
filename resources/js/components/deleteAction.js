// resources/js/components/deleteAction.js
export default (url, method, name, warningText = 'You cannot revert this action!', isClear = false) => ({
    async confirmDelete() {
        const result = await window.Swal.confirm(
            `Delete ${name}?`,
            warningText,
            'Yes, proceed!'
        );

        if (result.isConfirmed) {
            try {
                // Leverage window.Fetch which handles loading state, CSRF tokens, 
                // method spoofing (_method for DELETE/PUT), and error responses automatically.
                const { ok, data } = await window.Fetch.request(url, method, null, {
                    loading: true,
                    loadingTitle: 'Wiping Database...',
                    loadingHtml: 'Please wait...'
                });

                if (ok) {
                    this.$dispatch(isClear ? 'lexicon-clear' : 'ajax-success', data);
                } else {
                    window.Swal.error('Error', data.message || 'Failed to execute action.');
                }
            } catch (error) {
                // Network errors are automatically caught and alerted by window.Fetch,
                // but we ensure failure logging or fallback handling can occur here if needed.
            }
        }
    }
});