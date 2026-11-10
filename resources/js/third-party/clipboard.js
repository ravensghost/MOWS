// resources/js/third-party/clipboard.js

/**
 * Global Clipboard Helper Wrapper
 */
window.copyToClipboard = async function (text, successMessage = 'Copied to clipboard!') {
    try {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            await navigator.clipboard.writeText(text);
        } else {
            // Legacy fallback for unsupported contexts
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.opacity = '0';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
        }

        if (window.Swal && typeof window.Swal.toast === 'function') {
            window.Swal.toast('success', successMessage);
        }
    } catch (err) {
        console.error('[Clipboard] Failed to copy text: ', err);
        if (window.Swal && typeof window.Swal.error === 'function') {
            window.Swal.error('Error', 'Failed to copy to clipboard.');
        }
    }
};

export default window.copyToClipboard;