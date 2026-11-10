// resources/js/third-party/sortableHelper.js
import Sortable from 'sortablejs';

window.SortableHelper = {
    instances: {},

    create(selectorOrEl, options = {}) {
        const el = typeof selectorOrEl === 'string' ? document.querySelector(selectorOrEl) : selectorOrEl;
        if (!el) return null;

        const key = el.id || selectorOrEl;
        if (typeof key === 'string' && this.instances[key]) {
            this.instances[key].destroy();
        }

        const instance = Sortable.create(el, {
            animation: 150,
            ghostClass: 'sortable-ghost',
            ...options
        });

        if (typeof key === 'string') {
            this.instances[key] = instance;
        }

        return instance;
    },

    destroy(selectorOrEl) {
        const el = typeof selectorOrEl === 'string' ? document.querySelector(selectorOrEl) : selectorOrEl;
        const key = el && el.id ? el.id : selectorOrEl;

        if (typeof key === 'string' && this.instances[key]) {
            this.instances[key].destroy();
            delete this.instances[key];
        }
    }
};

window.Sortable = Sortable;

export default (endpoint, sortColumn = 'shelf_sort_order') => ({
    instance: null,

    init() {
        this.instance = window.SortableHelper.create(this.$el, {
            handle: '.bi-grip-vertical, [data-sortable-handle]',
            onEnd: async () => {
                if (!endpoint || !window.Fetch) return;

                const items = Array.from(this.$el.children)
                    .filter(el => el.dataset?.id)
                    .map((el, index) => ({
                        id: Number(el.dataset.id),
                        [sortColumn]: index + 1
                    }));

                if (!items.length) return;

                const { ok, data } = await window.Fetch.post(endpoint, { items });
                if (ok && window.Swal) {
                    window.Swal.toast('success', data.message || 'Order updated.');
                }
            }
        });
    },

    destroy() {
        this.instance?.destroy();
    }
});