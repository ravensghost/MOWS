// resources/js/third-party/tooltip.js
import tippy from 'tippy.js';
import 'tippy.js/dist/tippy.css';

/**
 * Custom Alpine Directive: x-tooltip
 * Powers reactive tooltips using Tippy.js.
 */
document.addEventListener('alpine:init', () => {
    window.Alpine.directive('tooltip', (el, { expression }, { evaluate }) => {
        const content = evaluate(expression) || el.getAttribute('title') || '';

        // Initialise Tippy instance on the element
        const instance = tippy(el, {
            content: content,
            placement: el.dataset.placement || 'top',
            animation: 'fade',
            theme: 'dark'
        });

        // Cleanup instance on element removal
        el._tippy = instance;
    });
});