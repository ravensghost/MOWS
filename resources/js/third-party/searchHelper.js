// resources/js/third-party/searchHelper.js
import Fuse from 'fuse.js';

/**
 * Global Search Helper Wrapper using Fuse.js
 */
window.Search = {
    create(selectorOrItems, keys = ['name'], options = {}) {
        let items = [];

        if (typeof selectorOrItems === 'string') {
            const elements = document.querySelectorAll(selectorOrItems);
            items = Array.from(elements).map(el => {
                const item = { element: el };
                // Extract dataset attributes or inner text based on specified keys
                keys.forEach(key => {
                    // Handle nested or dotted keys if needed (e.g., books.title)
                    if (key.includes('.')) {
                        const parts = key.split('.');
                        let val = el;
                        parts.forEach(p => {
                            val = val ? (val.dataset ? val.dataset[p] : val[p]) : '';
                        });
                        item[key] = val || '';
                    } else {
                        item[key] = el.dataset[key] || el.getAttribute(`data-${key}`) || '';
                    }
                });

                // Store original ID if available on dataset
                if (el.dataset.id) {
                    item.id = Number(el.dataset.id);
                }
                return item;
            });
        } else if (Array.isArray(selectorOrItems)) {
            items = selectorOrItems;
        }

        const defaultOptions = {
            keys: keys,
            threshold: 0.3,
            includeScore: false,
            ...options
        };

        const fuseInstance = new Fuse(items, defaultOptions);

        return {
            fuse: fuseInstance,
            search(query) {
                if (!query || !query.trim()) {
                    return items.map(item => item.id).filter(id => id !== undefined);
                }
                const results = fuseInstance.search(query.trim());
                return results.map(result => result.item.id).filter(id => id !== undefined);
            },
            searchObjects(query) {
                if (!query || !query.trim()) {
                    return items;
                }
                return fuseInstance.search(query.trim()).map(result => result.item);
            }
        };
    }
};

window.Fuse = Fuse;

export default Fuse;