// resources/js/managers/encyclopediaCodexManager.js
export default () => ({
    searchQuery: '',
    searchHelperInstance: null,
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
        this.initFuse();
        this.$watch('searchQuery', (value) => {
            if (!value.trim() || !this.searchHelperInstance) {
                this.filteredIds = [];
                return;
            }
            const results = this.searchHelperInstance.search(value.trim());
            // Extract matching titles or IDs depending on dataset mapping
            this.filteredIds = results ? results.map(res => res.item.title || res.item.id) : [];
        });
    },

    initFuse() {
        // Leverage the standardized global window.Search helper (powered by Fuse.js)
        if (window.Search) {
            try {
                this.searchHelperInstance = window.Search.create('[data-wiki-search-item]', ['title', 'category', 'content'], {
                    threshold: 0.3
                });
            } catch (e) {
                this.searchHelperInstance = null;
            }
        }
    },

    isArticleVisible(id) {
        if (!this.searchQuery.trim()) return true;
        const el = document.querySelector(`[data-wiki-search-item][data-id="${id}"]`) || document.querySelector(`[data-wiki-search-item]:nth-child(${id})`);
        if (!el) return true;
        const title = el.getAttribute('data-title');
        return this.filteredIds.includes(title) || this.filteredIds.includes(String(id));
    },

    async refreshDOM() {
        // Updated to use window.updateDOM with sanitize = false (replacing old _US helper)
        if (typeof window.updateDOM === 'function') {
            await window.updateDOM(this.$refs, ['wikiTableContent', 'wikiModalContent'], () => {
                this.wikiModal.open = false;
                this.initFuse();
            }, false);
        } else {
            this.wikiModal.open = false;
            this.initFuse();
        }
    },

    openWikiModal(mode, data = {}) {
        if (mode === 'edit') {
            window.Modal.edit(this.wikiModal, data, (id) => `/admin/wiki/${id}`);
            this.wikiModal.mode = 'edit';
            this.wikiModal.method = 'PUT';
            this.wikiModal.formAction = `/admin/wiki/${data.id}`;
            this.wikiModal.is_visible = data.is_visible !== undefined ? Boolean(data.is_visible) : true;
        } else {
            window.Modal.create(this.wikiModal, {
                category: 'General',
                is_visible: true,
                title: '',
                content: ''
            }, '/admin/wiki');
            this.wikiModal.mode = 'create';
            this.wikiModal.method = 'POST';
            this.wikiModal.formAction = '/admin/wiki';
        }
        this.wikiModal.open = true;

        // Standardised to 'set-chapter-editor-content' for consistent TipTap synchronization across managers
        this.$nextTick(() => {
            window.dispatchEvent(new CustomEvent('set-chapter-editor-content', {
                detail: { content: this.wikiModal.content || '' }
            }));
        });
    },

    closeWikiModal() {
        window.Modal.close(this.wikiModal);
    }
});