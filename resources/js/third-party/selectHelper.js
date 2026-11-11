// resources/js/third-party/searchHelper.js
import Fuse from 'fuse.js';

/**
 * Global Search Helper Wrapper using Fuse.js
 * Supports CSS selectors, NodeLists, HTMLCollections, HTMLElement containers, or raw object arrays.
 */
window.Search = {
    create(selectorOrItems, keys = ['name'], options = {}) {
        let items = [];
        let domElements = null;

        if (typeof selectorOrItems === 'string') {
            domElements = document.querySelectorAll(selectorOrItems);
        } else if (selectorOrItems instanceof NodeList || selectorOrItems instanceof HTMLCollection) {
            domElements = selectorOrItems;
        } else if (selectorOrItems instanceof HTMLElement) {
            domElements = selectorOrItems.querySelectorAll('[data-search-item]');
        } else if (Array.isArray(selectorOrItems)) {
            if (selectorOrItems.length > 0 && selectorOrItems[0] instanceof HTMLElement) {
                domElements = selectorOrItems;
            } else {
                items = selectorOrItems;
            }
        }

        if (domElements) {
            items = Array.from(domElements).map(el => {
                const item = { element: el };

                // Extract dataset attributes or attribute values based on specified keys
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

                // Store original ID if available on dataset (supports numeric or string IDs)
                if (el.dataset.id !== undefined) {
                    const numId = Number(el.dataset.id);
                    item.id = Number.isNaN(numId) ? el.dataset.id : numId;
                }
                return item;
            });
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