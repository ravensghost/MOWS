// resources/js/third-party/tiptapHelper.js
import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';

/**
 * Alpine component for TipTap Rich Text Editor integration
 */
export default function tiptapHelper(initialContent = '', onChange = null) {
    return {
        editor: null,
        content: initialContent,
        init() {
            this.editor = new Editor({
                element: this.$refs.editorContainer,
                extensions: [StarterKit],
                content: this.content,
                onUpdate: ({ editor }) => {
                    const html = editor.getHTML();
                    this.content = html;
                    if (typeof onChange === 'function') {
                        onChange(html);
                    } else if (this.$el.dataset.model) {
                        // Support wire/model binding if attribute exists
                        this.dispatchModel(html);
                    }
                }
            });
        },
        dispatchModel(html) {
            this.$dispatch('input', html);
        },
        destroy() {
            if (this.editor) {
                this.editor.destroy();
            }
        }
    };
}