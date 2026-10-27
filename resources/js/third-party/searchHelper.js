// resources/js/third-party/searchHelper.js
import Fuse from 'fuse.js';

/**
 * Global Search Utility
 * Encapsulates Fuse.js initialisation, dataset mapping, and filtering.
 */
window.Search = {
    create(selector, keys, options = {}) {
        const rows = Array.from(document.querySelectorAll(selector));
        const items = rows.map(row => {
            const data = { id: row.dataset.id || row.dataset.key || '' };
            keys.forEach(key => {
                data[key] = row.dataset[key] || row.getAttribute(`data-${key}`) || '';
            });
            return data;
        });

        const fuse = new Fuse(items, {
            keys: keys,
            threshold: options.threshold || 0.35,
            ignoreLocation: true,
            ...options
        });

        return {
            fuse,
            search(query) {
                if (!query || !query.trim()) return null;
                return fuse.search(query.trim()).map(res => res.item.id);
            }
        };
    }
};