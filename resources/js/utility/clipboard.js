// resources/js/utility/clipboard.js

/**
 * Global Clipboard Utility
 * Programmatically copies text to the clipboard and shows a non-blocking toast notification.
 */
window.copyToClipboard = async function (text, successMessage = 'Copied to clipboard!') {
    try {
        await navigator.clipboard.writeText(text);
        window.Swal.toast('success', successMessage);
    } catch (err) {
        console.error('Failed to copy text: ', err);
        window.Swal.error('Error', 'Failed to copy text.');
    }
};