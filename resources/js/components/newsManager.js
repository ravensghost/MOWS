export default () => ({
    modalOpen: false,
    mode: 'create',
    id: '',
    title: '',
    content: '',
    formAction: '/admin/news',
    method: 'POST',
    searchQuery: '',
    fuse: null,
    newsList: [],
    filteredIds: null,

    init() {
        this.loadNewsData();
        this.$watch('searchQuery', (val) => {
            if (!val.trim() || !this.fuse) {
                this.filteredIds = null;
                return;
            }
            this.filteredIds = this.fuse.search(val.trim()).map(r => r.item.id);
        });
    },

    loadNewsData() {
        const el = document.getElementById('news-fresh-data');
        if (el) {
            this.newsList = JSON.parse(el.getAttribute('data-news') || '[]');
            if (window.Fuse) {
                this.fuse = new window.Fuse(this.newsList, {
                    keys: ['title', 'date'],
                    threshold: 0.35
                });
            }
        }
    },

    isVisible(id) {
        return this.filteredIds === null || this.filteredIds.includes(id);
    },

    openModal(mode, data = {}) {
        this.mode = mode;
        this.id = data.id || '';
        this.title = data.title || '';
        this.content = data.content || '';
        this.formAction = mode === 'edit' ? `/admin/news/${this.id}` : '/admin/news';
        this.method = mode === 'edit' ? 'PUT' : 'POST';
        this.modalOpen = true;

        // Dispatch event to populate TipTap reliably just like the chapter modal
        this.$nextTick(() => {
            window.dispatchEvent(new CustomEvent('set-chapter-editor-content', {
                detail: { content: this.content || '' }
            }));
        });
    }
});