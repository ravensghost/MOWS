// resources/js/components/deleteAction.js

export default function deleteAction(
    url,
    method = 'DELETE',
    name = 'this record',
    warningText = 'You cannot revert this action!',
    isClear = false
) {
    return {
        async confirmDelete() {
            if (!window.Swal) return;

            const result = await window.Swal.confirm(
                `Delete ${name}?`,
                warningText,
                'Yes, proceed!'
            );

            if (!result.isConfirmed) return;

            try {
                const { ok, data } = await window.Fetch.request(url, method, null, {
                    loading: true,
                    loadingTitle: isClear ? 'Clearing Lexicon...' : 'Deleting Record...',
                    loadingHtml: 'Please wait...'
                });

                if (ok) {
                    if (window.Swal && typeof window.Swal.toast === 'function') {
                        window.Swal.toast('success', data?.message || 'Record deleted successfully.');
                    }
                    this.$dispatch(isClear ? 'lexicon-clear' : 'ajax-success', data);
                } else {
                    window.Swal.error('Error', data?.message || 'Failed to execute action.');
                }
            } catch (error) {
                // Network errors are caught and displayed by window.Fetch
            }
        }
    };
}