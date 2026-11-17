// resources/js/managers/encyclopediaCodexManager.js

export default function encyclopediaCodexManager() {
    const articleState = {
        isOpen: false,
        open: false,
        mode: 'create',
        method: 'POST',
        action: '/admin/encyclopedia-codex',
        formAction: '/admin/encyclopedia-codex',
        id: null,
        article_title: '',
        article_category: 'General',
        article_text: '',
        title: '',
        category: 'General',
        content: '',
        is_visible: true
    };

    return {
        // 1. State Properties Upfront (Unified Modals & Search Contract)
        modals: {
            article: articleState,
            wiki: articleState
        },
        searchQuery: '',
        searchHelperInstance: null,
        filteredIds: null,
        isLoading: false,

        // 2. Lifecycle Hook
        init() {
            this.initSearch();
            this.$watch('searchQuery', (value) => {
                if (!value.trim() || !this.searchHelperInstance) {
                    this.filteredIds = null;
                    return;
                }
                const results = this.searchHelperInstance.search(value.trim());
                this.filteredIds = results
                    ? results.map(res => String(res.item?.id ?? res.item?.title ?? res.id ?? res))
                    : [];
            });
        },

        // 3. Search & Filtering (Fuse.js Integration)
        initSearch() {
            if (window.Search && this.$el && this.$el.querySelector('[data-search-item], [data-wiki-search-item]')) {
                try {
                    this.searchHelperInstance = window.Search.create(
                        '[data-search-item], [data-wiki-search-item]',
                        ['article_title', 'article_category', 'article_text', 'title', 'category', 'content'],
                        { threshold: 0.35 }
                    );
                } catch (e) {
                    this.searchHelperInstance = null;
                }
            }
        },

        initFuse() {
            this.initSearch();
        },

        isItemVisible(id) {
            if (!this.searchQuery.trim() || this.filteredIds === null) return true;
            const el = document.querySelector(`[data-search-item][data-id="${id}"], [data-wiki-search-item][data-id="${id}"]`);
            const title = el ? (el.getAttribute('data-article-title') || el.getAttribute('data-title')) : null;
            return this.filteredIds.includes(String(id)) || (title && this.filteredIds.includes(title));
        },

        isArticleVisible(id) {
            return this.isItemVisible(id);
        },

        // 4. Modal Handlers (Using window.Modal utility)
        openModal(mode = 'create', data = {}, baseUrl = '/admin/encyclopedia-codex') {
            if (mode === 'edit') {
                const resolvedTitle = data.article_title ?? data.title ?? '';
                const resolvedCategory = data.article_category ?? data.category ?? 'General';
                const resolvedText = data.article_text ?? data.content ?? '';

                window.Modal.edit(this.modals.article, {
                    ...data,
                    article_title: resolvedTitle,
                    article_category: resolvedCategory,
                    article_text: resolvedText,
                    title: resolvedTitle,
                    category: resolvedCategory,
                    content: resolvedText,
                    is_visible: data.is_visible !== undefined ? Boolean(data.is_visible) : true
                }, (id) => `${baseUrl}/${id}`);
            } else {
                window.Modal.create(this.modals.article, {
                    article_title: '',
                    article_category: 'General',
                    article_text: '',
                    title: '',
                    category: 'General',
                    content: '',
                    is_visible: true
                }, baseUrl);
            }

            this.$nextTick(() => {
                window.dispatchEvent(new CustomEvent('set-editor-content', {
                    detail: { content: this.modals.article.article_text || '' }
                }));
            });
        },

        openWikiModal(mode = 'create', data = {}) {
            this.openModal(mode, data);
        },

        closeModal() {
            window.Modal.close(this.modals.article);
        },

        closeWikiModal() {
            this.closeModal();
        },

        // 5. DOM Refresh Handler (sanitize = false)
        async refreshDOM() {
            if (typeof window.updateDOM === 'function') {
                await window.updateDOM(this.$refs, ['wikiTableContent', 'wikiModalContent', 'codexTableContent'], () => {
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