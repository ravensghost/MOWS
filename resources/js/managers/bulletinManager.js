// resources/js/managers/bulletinManager.js
export default () => ({
    modalOpen: false,
    mode: 'create',
    id: '',
    title: '',
    content: '',
    formAction: '/admin/news',
    method: 'POST',
    searchQuery: '',
    searchHelperInstance: null,
    newsList: [],
    filteredIds: null,

    init() {
        this.loadNewsData();
        this.$watch('searchQuery', (val) => {
            if (!val.trim() || !this.searchHelperInstance) {
                this.filteredIds = null;
                return;
            }
            const results = this.searchHelperInstance.search(val.trim());
            this.filteredIds = results ? results.map(id => Number(id)) : [];
        });
    },

    loadNewsData() {
        const el = document.getElementById('news-fresh-data');
        if (el) {
            this.newsList = JSON.parse(el.getAttribute('data-news') || '[]');
            // Leverage the standardized global window.Search helper (powered by Fuse.js)
            if (window.Search) {
                this.searchHelperInstance = window.Search.create('#news-fresh-data [data-news-item], [data-news-search-item]', ['title', 'date'], {
                    threshold: 0.35
                });
            }
        }
    },

    isVisible(id) {
        return this.filteredIds === null || this.filteredIds.includes(Number(id));
    },

    async refreshDOM() {
        if (typeof window.updateDOM === 'function') {
            await window.updateDOM(this.$refs, ['newsTableContent'], () => {
                this.modalOpen = false;
                this.$nextTick(() => {
                    this.loadNewsData();
                });
            }, false);
        } else {
            this.modalOpen = false;
            this.$nextTick(() => {
                this.loadNewsData();
            });
        }
    },

    openModal(mode, data = {}) {
        if (mode === 'create') {
            window.Modal.create(this, {
                id: '',
                title: '',
                content: ''
            }, '/admin/news');
            this.mode = 'create';
            this.formAction = '/admin/news';
            this.method = 'POST';
        } else {
            window.Modal.edit(this, data, (id) => `/admin/news/${id}`);
            this.mode = 'edit';
            this.formAction = `/admin/news/${this.id}`;
            this.method = 'PUT';
        }
        this.modalOpen = true;

        // Dispatch event to populate TipTap reliably just like the chapter modal
        this.$nextTick(() => {
            window.dispatchEvent(new CustomEvent('set-chapter-editor-content', {
                detail: { content: this.content || '' }
            }));
        });
    },

    closeModal() {
        window.Modal.close(this);
    }
});