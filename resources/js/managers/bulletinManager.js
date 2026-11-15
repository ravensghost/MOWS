// resources/js/managers/bulletinManager.js
export default () => ({
    // 1. State Properties Upfront (Unified Modals & Search Contract)
    modals: {
        bulletin: {
            open: false,
            mode: 'create',
            id: '',
            title: '',
            content: '',
            action: '/admin/news',
            method: 'POST'
        }
    },
    searchQuery: '',
    searchHelperInstance: null,
    newsList: [],
    filteredIds: null,

    // 2. Lifecycle Hook
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

    // 3. Search & Filtering (Fuse.js Integration)
    loadNewsData() {
        const el = document.getElementById('news-fresh-data');
        if (el) {
            this.newsList = JSON.parse(el.getAttribute('data-news') || '[]');
            if (window.Search) {
                this.searchHelperInstance = window.Search.create(
                    '#news-fresh-data [data-news-item], [data-news-search-item]',
                    ['title', 'date'],
                    { threshold: 0.35 }
                );
            }
        }
    },

    isVisible(id) {
        return this.filteredIds === null || this.filteredIds.includes(Number(id));
    },

    // 4. Modal Handlers (Using window.Modal utility)
    openModal(mode = 'create', data = {}) {
        const baseUrl = '/admin/news';
        if (mode === 'create') {
            window.Modal.create(this.modals.bulletin, {
                id: '',
                title: '',
                content: ''
            }, baseUrl);
            this.modals.bulletin.method = 'POST';
        } else {
            window.Modal.edit(this.modals.bulletin, data, (id) => `${baseUrl}/${id}`);
            this.modals.bulletin.method = 'PUT';
        }
        this.modals.bulletin.open = true;

        this.$nextTick(() => {
            window.dispatchEvent(new CustomEvent('set-chapter-editor-content', {
                detail: { content: this.modals.bulletin.content || '' }
            }));
        });
    },

    closeModal() {
        window.Modal.close(this.modals.bulletin);
        this.modals.bulletin.open = false;
    },

    // 5. DOM Refresh Handler (sanitize = false)
    async refreshDOM() {
        if (typeof window.updateDOM === 'function') {
            await window.updateDOM(this.$refs, ['newsTableContent'], () => {
                this.closeModal();
                this.$nextTick(() => {
                    this.loadNewsData();
                });
            }, false);
        } else {
            this.closeModal();
            this.$nextTick(() => {
                this.loadNewsData();
            });
        }
    }
});