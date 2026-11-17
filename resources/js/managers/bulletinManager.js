// resources/js/managers/bulletinManager.js

export default function bulletinManager() {
    return {
        // 1. State Properties Upfront (Unified Modals & Search Contract)
        modals: {
            bulletin: {
                isOpen: false,
                open: false,
                mode: 'create',
                id: '',
                bulletin_title: '',
                bulletin_text: '',
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
            this.initSearch();
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
        initSearch() {
            const el = document.getElementById('news-fresh-data');
            if (el) {
                try {
                    this.newsList = JSON.parse(el.getAttribute('data-news') || '[]');
                } catch (e) {
                    this.newsList = [];
                }
            }

            if (window.Search && this.$el && this.$el.querySelector('[data-search-item], [data-news-item], [data-news-search-item]')) {
                try {
                    this.searchHelperInstance = window.Search.create(
                        '[data-search-item], #news-fresh-data [data-news-item], [data-news-search-item]',
                        ['bulletin_title', 'title', 'date', 'published_at'],
                        { threshold: 0.35 }
                    );
                } catch (e) {
                    this.searchHelperInstance = null;
                }
            }
        },

        loadNewsData() {
            this.initSearch();
        },

        isItemVisible(id) {
            if (!this.searchQuery.trim() || this.filteredIds === null) return true;
            return this.filteredIds.includes(Number(id));
        },

        isVisible(id) {
            return this.isItemVisible(id);
        },

        // 4. Modal Handlers (Using window.Modal utility)
        openModal(mode = 'create', data = {}, baseUrl = '/admin/news') {
            if (mode === 'create') {
                window.Modal.create(this.modals.bulletin, {
                    id: '',
                    bulletin_title: '',
                    bulletin_text: '',
                    title: '',
                    content: ''
                }, baseUrl);
            } else {
                const resolvedTitle = data.bulletin_title ?? data.title ?? '';
                const resolvedText = data.bulletin_text ?? data.content ?? '';
                window.Modal.edit(this.modals.bulletin, {
                    ...data,
                    bulletin_title: resolvedTitle,
                    bulletin_text: resolvedText,
                    title: resolvedTitle,
                    content: resolvedText
                }, (id) => `${baseUrl}/${id}`);
            }

            this.$nextTick(() => {
                window.dispatchEvent(new CustomEvent('set-editor-content', {
                    detail: { content: this.modals.bulletin.bulletin_text || '' }
                }));
            });
        },

        closeModal() {
            window.Modal.close(this.modals.bulletin);
        },

        // 5. DOM Refresh Handler (sanitize = false)
        async refreshDOM() {
            if (typeof window.updateDOM === 'function') {
                await window.updateDOM(this.$refs, ['newsTableContent', 'bulletinsTableContent'], () => {
                    this.closeModal();
                    this.$nextTick(() => {
                        this.initSearch();
                    });
                }, false);
            } else {
                this.closeModal();
                this.$nextTick(() => {
                    this.initSearch();
                });
            }
        }
    };
}