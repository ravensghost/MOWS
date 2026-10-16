/**
 * Internal Helper: Synchronises the primary navigation bar and slide-out drawer
 * from the freshly fetched HTML document without stripping Alpine.js bindings.
 */
function syncGlobalNavigation(doc) {
    ['primary', 'drawer'].forEach(zone => {
        const currentNav = document.querySelector(`[data-global-nav="${zone}"]`);
        const newNav = doc.querySelector(`[data-global-nav="${zone}"]`);

        if (currentNav && newNav) {
            currentNav.innerHTML = newNav.innerHTML;

            if (window.Alpine) {
                window.Alpine.initTree(currentNav);
            }
        }
    });
}

/**
 * SAFE: updateDOM
 * Uses DOMPurify. Strips Alpine.js attributes and malicious scripts.
 * Best used for user-generated content blocks.
 */
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

        if (window.Swal) {
            window.Swal.close();
            window.Swal.fire({
                icon: 'success',
                title: 'Success!',
                showConfirmButton: false,
                timer: 1500,
                background: 'var(--theme-panel-bg, #111827)',
                color: 'var(--theme-text-main, #ffffff)'
            });
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

        if (window.Swal) {
            window.Swal.close();
            window.Swal.fire({
                icon: 'success',
                title: 'Success!',
                showConfirmButton: false,
                timer: 1500,
                background: 'var(--theme-panel-bg, #111827)',
                color: 'var(--theme-text-main, #ffffff)'
            });
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

                if (window.Swal) {
                    window.Swal.close();
                    window.Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        showConfirmButton: false,
                        timer: 1500,
                        background: 'var(--theme-panel-bg, #111827)',
                        color: 'var(--theme-text-main, #ffffff)'
                    });
                }
            } else {
                if (window.Swal) {
                    window.Swal.close();
                }
                window.location.reload();
            }
        })
        .catch(error => {
            console.error('Safe DOM Refresh Failed:', error);
            if (window.Swal) {
                window.Swal.close();
            }
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

                if (window.Swal) {
                    window.Swal.close();
                    window.Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        showConfirmButton: false,
                        timer: 1500,
                        background: 'var(--theme-panel-bg, #111827)',
                        color: 'var(--theme-text-main, #ffffff)'
                    });
                }
            } else {
                if (window.Swal) {
                    window.Swal.close();
                }
                window.location.reload();
            }
        })
        .catch(error => {
            console.error('Unsafe DOM Refresh Failed:', error);
            if (window.Swal) {
                window.Swal.close();
            }
            window.location.reload();
        });
};