export default () => ({
    isOpen: false,
    chapterId: null,

    init() {
        // Listen for open events dispatched from the story table or draft widget
        window.addEventListener('open-chapter-modal', (event) => {
            this.isOpen = true;
            this.loadChapter(event.detail?.chapterId || null);
        });

        window.addEventListener('close-chapter-modal', () => {
            this.isOpen = false;
            this.chapterId = null;
        });
    },

    async loadChapter(id) {
        this.chapterId = id;
        const form = this.$el.querySelector('form');
        if (!form) return;

        const charSelect = form.querySelector('[name="character_ids[]"]');

        if (!id) {
            // Reset form for a brand new chapter
            form.reset();
            const idInput = form.querySelector('input[name="chapter_id"]');
            if (idInput) idInput.remove();

            if (charSelect && charSelect.tomselect) {
                charSelect.tomselect.clear(true);
            }

            window.dispatchEvent(new CustomEvent('set-chapter-editor-content', { detail: { content: '' } }));
            return;
        }

        try {
            const response = await fetch(`/chapters/${id}/edit`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (response.ok) {
                const chapter = await response.json();

                // Populate standard form inputs
                const setVal = (name, val) => {
                    const el = form.querySelector(`[name="${name}"]`);
                    if (el) el.value = val ?? '';
                };

                setVal('book_id', chapter.book_id);
                setVal('active_character_id', chapter.active_character_id);
                setVal('chapter_type', chapter.chapter_type || 'mission');
                setVal('ic_day', chapter.ic_day);
                setVal('ic_time', chapter.ic_time);
                setVal('location', chapter.location);
                setVal('rating_l', chapter.rating_l);
                setVal('rating_s', chapter.rating_s);
                setVal('rating_v', chapter.rating_v);
                setVal('status', chapter.status || 'draft');

                // Populate Tom Select for Characters Present
                if (charSelect && charSelect.tomselect) {
                    charSelect.tomselect.clear(true);
                    if (Array.isArray(chapter.character_ids)) {
                        charSelect.tomselect.setValue(chapter.character_ids, true);
                    }
                }

                // Set hidden chapter_id input for updates/autosaves
                let idInput = form.querySelector('input[name="chapter_id"]');
                if (!idInput) {
                    idInput = document.createElement('input');
                    idInput.type = 'hidden';
                    idInput.name = 'chapter_id';
                    form.appendChild(idInput);
                }
                idInput.value = chapter.id;

                // Dispatch event to populate TipTap reliably
                window.dispatchEvent(new CustomEvent('set-chapter-editor-content', {
                    detail: { content: chapter.content || '' }
                }));
            }
        } catch (err) {
            console.error('Failed to load chapter data:', err);
        }
    }
});