/**
 * Internal Helper: Synchronises global layout partials (header, navigation,
 * drawer, footer, auth-modal, rendered forms) and document metadata from the
 * freshly fetched HTML document without stripping Alpine.js bindings.
 */
function syncGlobalNavigation(doc) {
    // 1. Sync HTML lang attribute and document title
    const newLang = doc.documentElement.getAttribute('lang');
    if (newLang) {
        document.documentElement.setAttribute('lang', newLang);
    }
    if (doc.title) {
        document.title = doc.title;
    }

    // Helper to clean up active Tippy.js instances inside a container before replacing HTML
    const cleanupTooltips = (container) => {
        container.querySelectorAll('[x-tooltip]').forEach(el => {
            if (el._tippy) {
                el._tippy.destroy();
            }
        });
    };

    // 2. Sync all global layout partials tagged with data-global-sync
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

    // 3. Fallback for standalone data-global-nav zones if not wrapped in data-global-sync
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

// Expose globally as per architectural helpers
window.syncGlobalNavigation = syncGlobalNavigation;

/**
 * SAFE: updateDOM
 * Uses DOMPurify. Strips Alpine.js attributes and malicious scripts.
 * Best used for user-generated content blocks.
 */
// resources/js/utils/updateDOM.js

window.updateDOM = async function (componentRefs, refNames = [], onSuccess = null) {
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
                currentEl.innerHTML = window.DOMPurify.sanitize(newEl.innerHTML);

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
        console.error("Safe DOM Update Error:", err);
        window.location.reload();
    }
};

/**
 * UNSAFE: updateDOM_US
 * Bypasses DOMPurify. Preserves all attributes including Alpine.js.
 * Strictly for internal trusted UI elements (like Navigation Manager).
 */
window.updateDOM_US = async function (componentRefs, refNames = [], onSuccess = null) {
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
                currentEl.innerHTML = newEl.innerHTML;

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
        console.error("Unsafe DOM Update Error:", err);
        window.location.reload();
    }
};

/**
 * SAFE: refreshDOM
 * Uses DOMPurify. Strips Alpine.js attributes and malicious scripts.
 */
window.refreshDOM = function (targetSelector = '.table-wrapper') {
    fetch(window.location.href, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
        .then(response => response.text())
        .then(html => {
            let doc = new DOMParser().parseFromString(html, 'text/html');
            let newContent = doc.querySelector(targetSelector);
            let currentContent = document.querySelector(targetSelector);

            if (newContent && currentContent) {
                currentContent.innerHTML = window.DOMPurify.sanitize(newContent.innerHTML);

                if (window.Alpine) {
                    window.Alpine.initTree(currentContent);
                }

                syncGlobalNavigation(doc);
                if (typeof onSuccess === 'function') {
                    onSuccess(doc);
                }
            }
        })
        .catch(error => {
            console.error('Safe DOM Refresh Failed:', error);
            window.location.reload();
        });
};

/**
 * UNSAFE: refreshDOM_US
 * Bypasses DOMPurify. Preserves all attributes including Alpine.js.
 */
window.refreshDOM_US = function (targetSelector = '.table-wrapper, [x-ref="navTableContent"]') {
    fetch(window.location.href, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
        .then(response => response.text())
        .then(html => {
            let doc = new DOMParser().parseFromString(html, 'text/html');
            let newContent = doc.querySelector(targetSelector);
            let currentContent = document.querySelector(targetSelector);
            
            if (newContent && currentContent) {
                currentContent.innerHTML = newContent.innerHTML;

                if (window.Alpine) {
                    window.Alpine.initTree(currentContent);
                }

                syncGlobalNavigation(doc);
                if (typeof onSuccess === 'function') {
                    onSuccess(doc);
                }
            }
        })
        .catch(error => {
            console.error('Unsafe DOM Refresh Failed:', error);
            window.location.reload();
        });
};