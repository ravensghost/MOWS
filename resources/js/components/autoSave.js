// resources/js/components/autoSave.js
export default (initialInterval = 30000) => ({
    saving: false,
    lastSaved: null,
    timer: null,
    intervalMs: Number(initialInterval) || 30000,
    logs: [],

    init() {
        this.log(`Auto-save initialised with interval: ${this.intervalMs}ms`, 'info');
        this.timer = setInterval(() => {
            this.triggerAutoSave();
        }, this.intervalMs);
    },

    destroy() {
        if (this.timer) {
            clearInterval(this.timer);
            this.timer = null;
        }
    },

    log(message, type = 'info', data = null) {
        const timestamp = new Date().toLocaleTimeString();
        this.logs.unshift({ timestamp, message, type, data });
        if (this.logs.length > 25) this.logs.pop();
    },

    async triggerAutoSave() {
        // Resolve form whether this element is the form itself, a child, or a wrapper parent
        const form = this.$el.tagName === 'FORM'
            ? this.$el
            : (this.$el.closest('form') || this.$el.querySelector('form'));

        if (!form) {
            this.log('Auto-save skipped: Form not found in scope.', 'warning');
            return;
        }

        // Try to grab content directly from TipTap editor instance if available
        let htmlContent = '';
        const editorEl = form.querySelector('[x-data*="tiptapEditor"]');
        if (editorEl && window.Alpine) {
            const editorComponent = window.Alpine.$data(editorEl);
            if (editorComponent && editorComponent.editor) {
                htmlContent = editorComponent.editor.getHTML();
            }
        }

        // Fallback to hidden input if needed
        if (!htmlContent) {
            const contentInput = form.querySelector('input[name="content"]');
            htmlContent = contentInput ? contentInput.value : '';
        }

        if (!htmlContent || htmlContent.trim() === '' || htmlContent.trim() === '<p></p>') {
            this.log('Auto-save skipped: Content is empty or default paragraph.', 'info');
            return;
        }

        this.saving = true;
        this.$dispatch('autosave-start');
        this.log('Auto-save triggered. Assembling form payload...', 'info');

        try {
            const formData = new FormData(form);
            formData.set('content', htmlContent);

            let url = form.action;
            const chapterIdInput = form.querySelector('input[name="chapter_id"]');
            if (chapterIdInput && chapterIdInput.value) {
                url = `/chapters/${chapterIdInput.value}/autosave`;
                this.log(`Target URL: ${url} (Draft ID: ${chapterIdInput.value})`, 'info');
            } else {
                formData.set('status', 'draft');
                this.log(`Target URL: ${url} (New Draft creation)`, 'info');
            }

            // Utilize the standardized global window.Fetch helper
            const { ok, status, data } = await window.Fetch.request(url, 'POST', formData, {
                loading: false, // Background save; keep loading indicator disabled
                showError: false // Handled gracefully via local component logs
            });

            if (ok) {
                this.lastSaved = new Date().toLocaleTimeString();
                this.log(`Auto-save successful at ${this.lastSaved}`, 'success', data);

                if (data.chapter_id && (!chapterIdInput || !chapterIdInput.value)) {
                    let hiddenIdInput = form.querySelector('input[name="chapter_id"]');
                    if (!hiddenIdInput) {
                        hiddenIdInput = document.createElement('input');
                        hiddenIdInput.type = 'hidden';
                        hiddenIdInput.name = 'chapter_id';
                        form.appendChild(hiddenIdInput);
                    }
                    hiddenIdInput.value = data.chapter_id;
                    this.log(`Assigned new chapter_id hidden input: ${data.chapter_id}`, 'success');
                }

                this.$dispatch('autosave-success', data);
            } else {
                this.log(`Auto-save failed with HTTP status: ${status}`, 'error');
            }
        } catch (err) {
            console.error('Auto-save network error:', err);
            this.log(`Auto-save network error: ${err.message}`, 'error');
        } finally {
            this.saving = false;
        }
    }
});