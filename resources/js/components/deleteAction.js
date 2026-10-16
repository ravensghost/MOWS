export default (url, method, name, warningText = 'You cannot revert this action!', isClear = false) => ({
    confirmDelete() {
        Swal.fire({
            title: `Delete ${name}?`,
            text: warningText,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: 'var(--theme-danger, #ef4444)',
            cancelButtonColor: 'var(--theme-panel-header-bg, #1f2937)',
            confirmButtonText: 'Yes, proceed!',
            background: 'var(--theme-panel-bg, #111827)',
            color: 'var(--theme-text-main, #ffffff)'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Wiping Database...',
                    html: 'Please wait...',
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    background: 'var(--theme-panel-bg, #111827)',
                    color: 'var(--theme-text-main, #ffffff)',
                    didOpen: () => { Swal.showLoading(); }
                });

                fetch(url, {
                    method: method,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                        'Accept': 'application/json'
                    }
                }).then(response => {
                    if (response.ok) {
                        this.$dispatch(isClear ? 'lexicon-clear' : 'ajax-success');
                    } else {
                        Swal.fire('Error', 'Failed to execute action.', 'error');
                    }
                }).catch(() => {
                    Swal.fire('Error', 'Network error occurred.', 'error');
                });
            }
        });
    }
});
