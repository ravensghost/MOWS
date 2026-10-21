export default (initialContent = '') => {
    let editor = null;

    return {
        content: initialContent || '',
        updatedAt: Date.now(),

        // Expose the raw editor instance to the Alpine template scope (for x-show="editor" and toolbar @click handlers)
        // Tracking updatedAt ensures x-show="editor" re-evaluates as soon as the instance is initialised
        get editor() {
            this.updatedAt;
            return editor;
        },

        init() {
            editor = new window.TipTapEditor({
                element: this.$refs.editorContainer,
                extensions: [window.TipTapStarterKit],
                content: this.content,
                editorProps: {
                    attributes: {
                        class: 'bg-input text-input border-input rounded-input p-3 outline-none w-full box-border focus-border-accent focus-shadow-accent ProseMirror'
                    }
                },
                onUpdate: ({ editor: ed }) => {
                    this.content = ed.getHTML();
                    this.$dispatch('tiptap-update', this.content);
                },
                onBlur: ({ editor: ed }) => {
                    this.content = window.DOMPurify.sanitize(ed.getHTML());
                    this.$dispatch('tiptap-update', this.content);
                },
                onTransaction: () => {
                    this.updatedAt = Date.now();
                }
            });

            // Trigger reactivity so x-show="editor" reveals the toolbar immediately
            this.updatedAt = Date.now();

            // Listen for asynchronous chapter modal data loads
            window.addEventListener('set-chapter-editor-content', (e) => {
                this.setEditorContent(e.detail?.content ?? '');
            });

            this.$watch('content', (val) => {
                if (editor && editor.getHTML() !== val) {
                    editor.commands.setContent(val || '', false);
                }
            });
        },

        setEditorContent(newContent) {
            this.content = newContent || '';
            this.$nextTick(() => {
                if (editor) {
                    editor.commands.setContent(this.content, false);
                    this.updatedAt = Date.now();
                }
            });
        },

        checkActive(type, options = {}) {
            this.updatedAt;
            return editor ? editor.isActive(type, options) : false;
        },

        refreshEditor() {
            this.setEditorContent(this.content);
        },

        destroy() {
            if (editor) {
                editor.destroy();
                editor = null;
                this.updatedAt = Date.now();
            }
        }
    };
};