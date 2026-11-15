// resources/js/managers/encyclopediaCodexManager.js
export default () => ({
    // 1. State Properties Upfront (Unified Modals & State Contract)
    modals: {
        wiki: {
            open: false,
            mode: 'create',
            method: 'POST',
            action: '/admin/wiki',
            formAction: '/admin/wiki',
            id: null,
            title: '',
            category: 'General',
            content: '',
            is_visible: true
        }
    },
    searchQuery: '',
    searchHelperInstance: null,
    filteredIds: [],

    // 2. Lifecycle Hook
    init() {
        this.initFuse();
        this.$watch('searchQuery', (value) => {
            if (!value.trim() || !this.searchHelperInstance) {
                this.filteredIds = [];
                return;
            }
            const results = this.searchHelperInstance.search(value.trim());
            this.filteredIds = results ? results.map(res => res.item.title || res.item.id) : [];
        });
    },

    // 3. Search & Filtering (Fuse.js Integration)
    initFuse() {
        if (window.Search) {
            try {
                this.searchHelperInstance = window.Search.create(
                    '[data-wiki-search-item]',
                    ['title', 'category', 'content'],
                    { threshold: 0.3 }
                );
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

    // 4. Modal Handlers (Using window.Modal utility)
    openWikiModal(mode = 'create', data = {}) {
        const baseUrl = '/admin/wiki';
        if (mode === 'edit') {
            window.Modal.edit(this.modals.wiki, data, (id) => `${baseUrl}/${id}`);
            this.modals.wiki.mode = 'edit';
            this.modals.wiki.method = 'PUT';
            this.modals.wiki.formAction = `${baseUrl}/${data.id}`;
            this.modals.wiki.action = `${baseUrl}/${data.id}`;
            this.modals.wiki.is_visible = data.is_visible !== undefined ? Boolean(data.is_visible) : true;
        } else {
            window.Modal.create(this.modals.wiki, {
                category: 'General',
                is_visible: true,
                title: '',
                content: ''
            }, baseUrl);
            this.modals.wiki.mode = 'create';
            this.modals.wiki.method = 'POST';
            this.modals.wiki.formAction = baseUrl;
            this.modals.wiki.action = baseUrl;
        }
        this.modals.wiki.open = true;

        this.$nextTick(() => {
            window.dispatchEvent(new CustomEvent('set-chapter-editor-content', {
                detail: { content: this.modals.wiki.content || '' }
            }));
        });
    },

    closeWikiModal() {
        window.Modal.close(this.modals.wiki);
        this.modals.wiki.open = false;
    },

    // 5. DOM Refresh Handler (sanitize = false)
    async refreshDOM() {
        if (typeof window.updateDOM === 'function') {
            await window.updateDOM(this.$refs, ['wikiTableContent', 'wikiModalContent'], () => {
                this.closeWikiModal();
                this.$nextTick(() => {
                    this.initFuse();
                });
            }, false);
        } else {
            this.closeWikiModal();
            this.$nextTick(() => {
                this.initFuse();
            });
        }
    }
});