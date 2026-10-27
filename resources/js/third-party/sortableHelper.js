// resources/js/third-party/sortableHelper.js
import Sortable from 'sortablejs';

/**
 * Alpine component for SortableJS drag-and-drop reordering
 */
export default function sortableHelper(options = {}) {
    return {
        sortableInstance: null,
        init() {
            this.sortableInstance = new Sortable(this.$el, {
                animation: 150,
                ghostClass: 'opacity-50',
                onEnd: (evt) => {
                    if (typeof options.onEnd === 'function') {
                        options.onEnd(evt);
                    } else {
                        this.$el.dispatchEvent(new CustomEvent('sort-end', {
                            detail: { oldIndex: evt.oldIndex, newIndex: evt.newIndex, item: evt.item }
                        }));
                    }
                },
                ...options
            });
        },
        destroy() {
            if (this.sortableInstance) {
                this.sortableInstance.destroy();
            }
        }
    };
}