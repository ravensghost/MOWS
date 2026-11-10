// resources/js/third-party/tiptapHelper.js
import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import Placeholder from '@tiptap/extension-placeholder';
import Underline from '@tiptap/extension-underline';
import TextAlign from '@tiptap/extension-text-align';

/**
 * Global TipTap Helper Wrapper for Rich Text Editors
 */
window.TiptapHelper = {
    instances: {},

    create(elementOrSelector, options = {}) {
        const el = typeof elementOrSelector === 'string' ? document.querySelector(elementOrSelector) : elementOrSelector;
        if (!el) return null;

        const key = el.id || elementOrSelector;
        if (typeof key === 'string' && this.instances[key]) {
            this.instances[key].destroy();
        }

        const defaultOptions = {
            element: el,
            extensions: [
                StarterKit,
                Underline,
                TextAlign.configure({ types: ['heading', 'paragraph'] }),
                Placeholder.configure({
                    placeholder: options.placeholder || 'Write your content here...'
                }),
                ...(options.extensions || [])
            ],
            content: options.content || '',
            onUpdate: ({ editor }) => {
                if (typeof options.onUpdate === 'function') {
                    options.onUpdate(editor.getHTML());
                }
            },
            ...options
        };

        const editorInstance = new Editor(defaultOptions);

        if (typeof key === 'string') {
            this.instances[key] = editorInstance;
        }

        return editorInstance;
    },

    destroy(elementOrSelector) {
        const el = typeof elementOrSelector === 'string' ? document.querySelector(elementOrSelector) : elementOrSelector;
        const key = el && el.id ? el.id : elementOrSelector;

        if (typeof key === 'string' && this.instances[key]) {
            this.instances[key].destroy();
            delete this.instances[key];
        }
    }
};

window.Editor = Editor;

export default Editor;