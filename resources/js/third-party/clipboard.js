// resources/js/third-party/clipboard.js

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