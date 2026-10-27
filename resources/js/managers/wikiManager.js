import Fuse from 'fuse.js';

export default () => ({
    searchQuery: '',
    fuse: null,
    filteredIds: [],

    wikiModal: {
        open: false,
        mode: 'create',
        method: 'POST',
        formAction: '/admin/wiki',
        id: null,
        title: '',
        category: 'General',
        content: '',
        is_visible: true
    },

    init() {
        const items = Array.from(document.querySelectorAll('[data-wiki-search-item]')).map(el => ({
            id: el.getAttribute('data-id') || el.id,
            title: el.getAttribute('data-title') || '',
            category: el.getAttribute('data-category') || '',
            content: el.getAttribute('data-content') || '',
            element: el
        }));

        this.fuse = new Fuse(items, {
            keys: ['title', 'category', 'content'],
            threshold: 0.3
        });

        this.$watch('searchQuery', (value) => {
            if (!value.trim()) {
                this.filteredIds = [];
                return;
            }
            const results = this.fuse.search(value);
            this.filteredIds = results.map(r => r.item.title);
        });
    },

    isArticleVisible(id) {
        if (!this.searchQuery.trim()) return true;
        const el = document.querySelector(`[data-wiki-search-item][data-id="${id}"]`) || document.querySelector(`[data-wiki-search-item]:nth-child(${id})`);
        if (!el) return true;
        const title = el.getAttribute('data-title');
        return this.filteredIds.includes(title);
    },

    openWikiModal(mode, data = {}) {
        this.wikiModal.mode = mode;
        this.wikiModal.open = true;
        if (mode === 'edit') {
            this.wikiModal.method = 'PUT';
            this.wikiModal.formAction = `/admin/wiki/${data.id}`;
            this.wikiModal.id = data.id;
            this.wikiModal.title = data.title;
            this.wikiModal.category = data.category;
            this.wikiModal.content = data.content;
            this.wikiModal.is_visible = data.is_visible;
            window.dispatchEvent(new CustomEvent('set-tiptap-content', { detail: data.content }));
        } else {
            this.wikiModal.method = 'POST';
            this.wikiModal.formAction = '/admin/wiki';
            this.wikiModal.id = null;
            this.wikiModal.title = '';
            this.wikiModal.category = 'General';
            this.wikiModal.content = '';
            this.wikiModal.is_visible = true;
            window.dispatchEvent(new CustomEvent('set-tiptap-content', { detail: '' }));
        }
    },

    closeWikiModal() {
        this.wikiModal.open = false;
    }
});