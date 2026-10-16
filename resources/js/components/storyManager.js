export default () => ({
    searchQuery: '',
    fuseInstance: null,
    matchedBookcaseIds: null,

    bookcaseModalOpen: false,
    bookcaseMode: 'create',
    bookcaseId: '',
    bookcaseName: '',
    bookcaseFormAction: '/admin/bookcases',
    bookcaseMethod: 'POST',

    bookModalOpen: false,
    bookMode: 'create',
    bookId: '',
    bookTitle: '',
    bookDesc: '',
    bookStatusId: '',
    bookDay: '',
    bookTime: '',
    bookBookcaseId: '',
    bookFormAction: '/admin/books',
    bookMethod: 'POST',

    moveModalOpen: false,
    moveChapterId: '',
    moveBookId: '',
    moveFormAction: '',

    statusModalOpen: false,
    statusMode: 'create',
    statusId: '',
    statusName: '',
    statusFormAction: '/admin/statuses',
    statusMethod: 'POST',

    init() {
        this.initFuse();
    },

    initFuse() {
        if (this.$refs.storySearchData && window.Fuse) {
            try {
                const data = JSON.parse(this.$refs.storySearchData.textContent || '[]');
                this.fuseInstance = new window.Fuse(data, {
                    keys: ['name', 'books.title', 'books.description'],
                    threshold: 0.35,
                    ignoreLocation: true
                });
            } catch (e) {
                this.fuseInstance = null;
            }
        }
    },

    filterLibrary() {
        const q = this.searchQuery.trim();
        if (!q || !this.fuseInstance) {
            this.matchedBookcaseIds = null;
            return;
        }
        const results = this.fuseInstance.search(q);
        this.matchedBookcaseIds = results.map(r => Number(r.item.id));
    },

    matchesBookcase(id, name = '') {
        const q = this.searchQuery.trim().toLowerCase();
        if (!q) return true;
        if (this.matchedBookcaseIds !== null) {
            return this.matchedBookcaseIds.includes(Number(id));
        }
        return String(name).toLowerCase().includes(q);
    },

    matchesBook(title = '', desc = '') {
        const q = this.searchQuery.trim().toLowerCase();
        if (!q) return true;
        return String(title).toLowerCase().includes(q) || String(desc).toLowerCase().includes(q);
    },

    openStatusModal(detail = {}) {
        this.statusMode = detail.mode || 'create';
        this.statusId = detail.id || '';
        this.statusName = detail.name || '';
        this.statusFormAction = this.statusMode === 'edit' ? `/admin/statuses/${this.statusId}` : '/admin/statuses';
        this.statusMethod = this.statusMode === 'edit' ? 'PUT' : 'POST';
        this.statusModalOpen = true;
    },

    openBookcaseModal(detail = {}) {
        this.bookcaseMode = detail.mode || 'create';
        this.bookcaseId = detail.id || '';
        this.bookcaseName = detail.name || '';
        this.bookcaseFormAction = this.bookcaseMode === 'edit' ? `/admin/bookcases/${this.bookcaseId}` : '/admin/bookcases';
        this.bookcaseMethod = this.bookcaseMode === 'edit' ? 'PUT' : 'POST';
        this.bookcaseModalOpen = true;
    },

    openBookModal(detail = {}) {
        this.bookMode = detail.mode || 'create';
        this.bookId = detail.id || '';
        this.bookTitle = detail.title || '';
        this.bookDesc = detail.desc || '';
        this.bookStatusId = detail.bookStatusId || '';
        this.bookDay = detail.day || '';
        this.bookTime = detail.time || '';
        this.bookBookcaseId = detail.bookcaseId || '';
        this.bookFormAction = this.bookMode === 'edit' ? `/admin/books/${this.bookId}` : '/admin/books';
        this.bookMethod = this.bookMode === 'edit' ? 'PUT' : 'POST';
        this.bookModalOpen = true;

        this.$nextTick(() => {
            if (this.$refs.bookDescTextarea && window.autosize) {
                window.autosize.update(this.$refs.bookDescTextarea);
            }
        });
    },

    openMoveChapterModal(detail = {}) {
        this.moveChapterId = detail.chapterId || '';
        this.moveBookId = detail.bookId || '';
        this.moveFormAction = `/admin/chapters/${this.moveChapterId}/move`;
        this.moveModalOpen = true;
    },

    async refreshDOM() {
        window.updateDOM_US(this.$refs, ['bookcasesWrapper', 'looseBooksWrapper', 'statusesWrapper'], () => {
            this.bookcaseModalOpen = false;
            this.bookModalOpen = false;
            this.moveModalOpen = false;
            this.statusModalOpen = false;
            this.initFuse();
            this.filterLibrary();
        });
    }
});