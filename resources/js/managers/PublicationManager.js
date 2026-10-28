// resources/js/managers/PublicationManager.js
export default () => ({
    searchQuery: '',
    searchHelperInstance: null,
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
        this.$watch('searchQuery', () => {
            this.filterLibrary();
        });
    },

    initFuse() {
        // Leverage the standardized global window.Search helper (powered by Fuse.js)
        if (this.$refs.storySearchData && window.Search) {
            try {
                this.searchHelperInstance = window.Search.create('[data-story-search-item], [data-bookcase-item]', ['name', 'books.title', 'books.description'], {
                    threshold: 0.35
                });
            } catch (e) {
                this.searchHelperInstance = null;
            }
        }
    },

    filterLibrary() {
        const q = this.searchQuery.trim();
        if (!q || !this.searchHelperInstance) {
            this.matchedBookcaseIds = null;
            return;
        }
        const results = this.searchHelperInstance.search(q);
        this.matchedBookcaseIds = results ? results.map(id => Number(id)) : [];
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
        if (detail.mode === 'edit') {
            window.Modal.edit(this, { id: detail.id, name: detail.name }, (id) => `/admin/statuses/${id}`);
            this.statusMode = 'edit';
            this.statusFormAction = `/admin/statuses/${this.statusId}`;
            this.statusMethod = 'PUT';
        } else {
            window.Modal.create(this, { name: '' }, '/admin/statuses');
            this.statusMode = 'create';
            this.statusFormAction = '/admin/statuses';
            this.statusMethod = 'POST';
        }
        this.statusModalOpen = true;
    },

    openBookcaseModal(detail = {}) {
        if (detail.mode === 'edit') {
            window.Modal.edit(this, { id: detail.id, name: detail.name }, (id) => `/admin/bookcases/${id}`);
            this.bookcaseMode = 'edit';
            this.bookcaseFormAction = `/admin/bookcases/${this.bookcaseId}`;
            this.bookcaseMethod = 'PUT';
        } else {
            window.Modal.create(this, { name: '' }, '/admin/bookcases');
            this.bookcaseMode = 'create';
            this.bookcaseFormAction = '/admin/bookcases';
            this.bookcaseMethod = 'POST';
        }
        this.bookcaseModalOpen = true;
    },

    openBookModal(detail = {}) {
        this.bookMode = detail.mode || 'create';
        this.bookId = detail.id || '';
        this.bookTitle = detail.title || '';
        this.bookDesc = detail.desc || '';
        this.bookStatusId = detail.bookStatusId !== undefined && detail.bookStatusId !== null ? String(detail.bookStatusId) : '';
        this.bookDay = detail.day !== undefined && detail.day !== null ? detail.day : '';
        this.bookTime = detail.time || '';
        this.bookBookcaseId = detail.bookcaseId !== undefined && detail.bookcaseId !== null ? String(detail.bookcaseId) : '';
        this.bookFormAction = this.bookMode === 'edit' ? `/admin/books/${this.bookId}` : '/admin/books';
        this.bookMethod = this.bookMode === 'edit' ? 'PUT' : 'POST';
        this.bookModalOpen = true;

        this.$nextTick(() => {
            if (this.$refs.bookDescTextarea && window.Autosize) {
                window.Autosize.update(this.$refs.bookDescTextarea);
            }
        });
    },

    openMoveChapterModal(detail = {}) {
        this.moveChapterId = detail.chapterId || '';
        this.moveBookId = detail.bookId !== undefined && detail.bookId !== null ? String(detail.bookId) : '';
        this.moveFormAction = `/admin/chapters/${this.moveChapterId}/move`;
        this.moveModalOpen = true;
    },

    async refreshDOM(responseData = {}) {
        // Updated to use window.updateDOM with sanitize = false (replacing old _US helper)
        if (typeof window.updateDOM === 'function') {
            await window.updateDOM(this.$refs, ['bookcasesWrapper', 'looseBooksWrapper', 'statusesWrapper'], () => {
                this.bookcaseModalOpen = false;
                this.bookModalOpen = false;
                this.moveModalOpen = false;
                this.statusModalOpen = false;
                this.initFuse();
                this.filterLibrary();

                if (responseData && responseData.trigger_review && responseData.data) {
                    window.dispatchEvent(new CustomEvent('open-book-review', {
                        detail: {
                            bookId: responseData.data.id,
                            bookTitle: responseData.data.title
                        }
                    }));
                }
            }, false);
        } else {
            this.bookcaseModalOpen = false;
            this.bookModalOpen = false;
            this.moveModalOpen = false;
            this.statusModalOpen = false;
            this.initFuse();
            this.filterLibrary();
        }
    }
});