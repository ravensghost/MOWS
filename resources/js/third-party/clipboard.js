// resources/js/third-party/clipboard.js
import tippy from 'tippy.js';
import 'tippy.js/dist/tippy.css';

/**
 * Global Clipboard Utility
 * Programmatically copies text to the clipboard and shows a non-blocking toast notification.
 */
window.copyToClipboard = async function (text, successMessage = 'Copied to clipboard!') {
    try {
        await navigator.clipboard.writeText(text);
        if (window.Swal && typeof window.Swal.toast === 'function') {
            window.Swal.toast('success', successMessage);
        }
    } catch (err) {
        console.error('Failed to copy text: ', err);
        if (window.Swal && typeof window.Swal.error === 'function') {
            window.Swal.error('Error', 'Failed to copy text.');
        }
    }
};

/**
 * Custom Alpine Directive: x-clipboard
 * Integrates clipboard copying via an Alpine directive.
 */
document.addEventListener('alpine:init', () => {
    window.Alpine.directive('clipboard', (el, { expression }, { evaluate }) => {
        el.addEventListener('click', () => {
            const textToCopy = evaluate(expression) || el.dataset.clipboardText || '';
            if (textToCopy && window.copyToClipboard) {
                window.copyToClipboard(textToCopy);
            }
        });
    });
});