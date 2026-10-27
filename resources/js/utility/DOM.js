// resources/js/utility/DOM.js

/**
 * Internal Helper: Synchronises global layout partials (header, navigation,
 * drawer, footer, auth-modal, rendered forms) and document metadata from the
 * freshly fetched HTML document without stripping Alpine.js bindings.
 */
function syncGlobalNavigation(doc) {
    const newLang = doc.documentElement.getAttribute('lang');
    if (newLang) {
        document.documentElement.setAttribute('lang', newLang);
    }
    if (doc.title) {
        document.title = doc.title;
    }

    const cleanupTooltips = (container) => {
        container.querySelectorAll('[x-tooltip]').forEach(el => {
            if (el._tippy) {
                el._tippy.destroy();
            }
        });
    };

    doc.querySelectorAll('[data-global-sync]').forEach(newPartial => {
        const key = newPartial.getAttribute('data-global-sync');
        const currentPartial = document.querySelector(`[data-global-sync="${key}"]`);

        if (currentPartial) {
            cleanupTooltips(currentPartial);
            currentPartial.innerHTML = newPartial.innerHTML;

            if (window.Alpine) {
                window.Alpine.initTree(currentPartial);
            }
        }
    });

    if (!document.querySelector('[data-global-sync="navigation"]')) {
        ['primary', 'drawer'].forEach(zone => {
            const currentNav = document.querySelector(`[data-global-nav="${zone}"]`);
            const newNav = doc.querySelector(`[data-global-nav="${zone}"]`);

            if (currentNav && newNav) {
                cleanupTooltips(currentNav);
                currentNav.innerHTML = newNav.innerHTML;

                if (window.Alpine) {
                    window.Alpine.initTree(currentNav);
                }
            }
        });
    }
}

window.syncGlobalNavigation = syncGlobalNavigation;

/**
 * Unified DOM Update Utility
 * 
 * @param {Object} componentRefs - Component $refs object
 * @param {Array<string>} refNames - Array of ref names to update
 * @param {Function} [onSuccess] - Optional callback function (doc) => {}
 * @param {boolean} [sanitize=true] - Whether to apply DOMPurify sanitisation
 */
window.updateDOM = async function (componentRefs, refNames = [], onSuccess = null, sanitize = true) {
    try {
        const response = await fetch(window.location.href, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const html = await response.text();
        const doc = new DOMParser().parseFromString(html, 'text/html');

        refNames.forEach(refName => {
            const currentEl = componentRefs[refName];
            const newEl = doc.querySelector(`[x-ref="${refName}"]`);

            if (currentEl && newEl) {
                const content = newEl.innerHTML;
                currentEl.innerHTML = (sanitize && window.DOMPurify) ? window.DOMPurify.sanitize(content) : content;

                if (window.Alpine) {
                    window.Alpine.initTree(currentEl);
                }
            }
        });

        syncGlobalNavigation(doc);

        if (typeof onSuccess === 'function') {
            onSuccess(doc);
        }

    } catch (err) {
        console.error("DOM Update Error:", err);
        window.Swal.error('Error', 'Failed to update component view.');
    }
};

/**
 * Unified DOM Refresh Utility
 * 
 * @param {string} [targetSelector='.table-wrapper'] - CSS selector for target element
 * @param {Function} [onSuccess] - Optional callback function (doc) => {}
 * @param {boolean} [sanitize=true] - Whether to apply DOMPurify sanitisation
 */
window.refreshDOM = async function (targetSelector = '.table-wrapper', onSuccess = null, sanitize = true) {
    try {
        const response = await fetch(window.location.href, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const html = await response.text();
        const doc = new DOMParser().parseFromString(html, 'text/html');
        const newContent = doc.querySelector(targetSelector);
        const currentContent = document.querySelector(targetSelector);

        if (newContent && currentContent) {
            const content = newContent.innerHTML;
            currentContent.innerHTML = (sanitize && window.DOMPurify) ? window.DOMPurify.sanitize(content) : content;

            if (window.Alpine) {
                window.Alpine.initTree(currentContent);
            }

            syncGlobalNavigation(doc);

            if (typeof onSuccess === 'function') {
                onSuccess(doc);
            }
        }
    } catch (error) {
        console.error('DOM Refresh Failed:', error);
        window.Swal.error('Error', 'Failed to refresh view content.');
    }
};