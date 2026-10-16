/**
 * Global Clipboard Utility
 * Programmatically copies text to the clipboard and shows a non-blocking toast notification.
 */
window.copyToClipboard = async function (text, successMessage = 'Copied to clipboard!') {
    try {
        await navigator.clipboard.writeText(text);

        if (window.Swal) {
            window.Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: successMessage,
                showConfirmButton: false,
                timer: 1500,
                background: 'var(--theme-panel-bg, #111827)',
                color: 'var(--theme-text-main, #ffffff)'
            });
        }
    } catch (err) {
        console.error('Failed to copy text: ', err);
        if (window.Swal) {
            window.Swal.fire('Error', 'Failed to copy text.', 'error');
        }
    }
};