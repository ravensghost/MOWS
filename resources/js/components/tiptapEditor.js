export default (initialContent = '') => ({
    content: initialContent,
    editor: null,
    updatedAt: Date.now(),

    init() {
        this.editor = new window.TipTapEditor({
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

        this.$watch('content', (val) => {
            if (this.editor && this.editor.getHTML() !== val) {
                this.editor.commands.setContent(val || '', false);
            }
        });
    },

    checkActive(type, options = {}) {
        this.updatedAt;
        return this.editor ? window.Alpine.raw(this.editor).isActive(type, options) : false;
    },

    refreshEditor() {
        if (this.editor) {
            setTimeout(() => this.editor.commands.setContent(this.content, false), 50);
        }
    },

    destroy() {
        if (this.editor) {
            this.editor.destroy();
            this.editor = null;
        }
    }
});