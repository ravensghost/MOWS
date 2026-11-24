// resources/js/third-party/tiptapHelper.js
import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import Placeholder from '@tiptap/extension-placeholder';
import Underline from '@tiptap/extension-underline';
import TextAlign from '@tiptap/extension-text-align';
import { TextStyle } from '@tiptap/extension-text-style';
import { FontFamily } from '@tiptap/extension-font-family';

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
            editorProps: {
                attributes: {
                    spellcheck: 'true',
                    class: 'ProseMirror outline-none min-h-48'
                }
            },
            extensions: [
                StarterKit,
                Underline,
                TextStyle,
                FontFamily.configure({
                    types: ['textStyle']
                }),
                TextAlign.configure({
                    types: ['heading', 'paragraph']
                }),
                Placeholder.configure({
                    placeholder: options.placeholder || 'Write your content here...'
                }),
                ...(options.extensions || [])
            ],
            content: options.content || '',
            onUpdate: ({ editor }) => {
                if (typeof options.onUpdate === 'function') {
                    options.onUpdate(editor.getHTML(), editor);
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

/**
 * Canonical Alpine.js x-data="tiptapHelper(...)" Component Factory
 */
export default function tiptapHelper(initialContent = '', placeholder = '') {
    return {
        editor: null,
        content: initialContent || '',
        selectedFont: '',
        updatedAt: Date.now(),

        init() {
            this.$nextTick(() => {
                const container = this.$refs.editorContainer || this.$el.querySelector('.editor-content');
                if (!container) return;

                this.editor = window.TiptapHelper.create(container, {
                    content: this.content,
                    placeholder: placeholder || 'Write your content here...',
                    onUpdate: (html) => {
                        this.content = html;
                        this.updatedAt = Date.now();
                        this.$dispatch('tiptap-update', html);
                    },
                    onSelectionUpdate: () => {
                        this.updatedAt = Date.now();
                    },
                    onTransaction: () => {
                        this.updatedAt = Date.now();
                    }
                });
            });

            const syncContentListener = (event) => {
                const newContent = event.detail?.content ?? '';
                this.content = newContent;
                if (this.editor) {
                    window.Alpine.raw(this.editor).commands.setContent(newContent, false);
                }
            };

            window.addEventListener('set-editor-content', syncContentListener);
            window.addEventListener('set-chapter-editor-content', syncContentListener);
        },

        checkActive(type, attrs = {}) {
            void this.updatedAt;
            if (!this.editor) return false;
            return window.Alpine.raw(this.editor).isActive(type, attrs);
        },

        setFontFamily(fontName) {
            if (!this.editor) return;
            const rawEditor = window.Alpine.raw(this.editor);
            if (!fontName) {
                rawEditor.chain().focus().unsetFontFamily().run();
            } else {
                rawEditor.chain().focus().setFontFamily(fontName).run();
            }
        },

        openCommsTemplate() {
            if (!this.editor) return;
            const rawEditor = window.Alpine.raw(this.editor);
            rawEditor.chain().focus().insertContent(
                '<blockquote class="folio-transmission-sci-fi"><p><strong>=/\\= COMM TRANSMISSION =/\\=</strong></p><p></p></blockquote>'
            ).run();
        },

        tagDialectSpan() {
            if (!this.editor) return;
            const rawEditor = window.Alpine.raw(this.editor);
            const { from, to } = rawEditor.state.selection;
            const selectedText = rawEditor.state.doc.textBetween(from, to, ' ');
            if (!selectedText.trim()) {
                if (window.Swal) {
                    window.Swal.toast('info', 'Highlight the dialogue text you wish to tag first.');
                }
                return;
            }
            rawEditor.chain().focus().insertContent(
                `<span class="mows-lexicon-tag" data-lexicon-lang="dialect" data-lexicon-register="standard">${selectedText}</span>`
            ).run();
        },

        async rollDiceOrTarot() {
            if (!this.editor) return;
            const rawEditor = window.Alpine.raw(this.editor);
            const rollValue = Math.floor(Math.random() * 20) + 1;
            rawEditor.chain().focus().insertContent(
                `<span class="inline-block p-badge border-rounded-pill text-xs font-bold uppercase text-wider bg-badge-info text-accent border-info" data-roll-type="d20" data-roll-result="${rollValue}">🎲 D20: ${rollValue}</span>&nbsp;`
            ).run();
        },

        async proofreadContent() {
            if (!this.editor || !this.content.trim()) return;
            try {
                const { ok, data } = await window.Fetch.post('/admin/folios/ai-generate', {
                    mode: 'proofread',
                    content: this.content
                }, {
                    loading: true,
                    loadingTitle: 'AI Proofreader',
                    loadingHtml: 'Checking spelling, grammar, and lore consistency...'
                });

                if (ok && data?.content) {
                    this.content = data.content;
                    window.Alpine.raw(this.editor).commands.setContent(data.content, true);
                    this.$dispatch('tiptap-update', data.content);
                }
            } catch (e) {
                console.error('[TipTap] Proofread failed:', e);
            }
        },

        destroy() {
            if (this.editor) {
                window.Alpine.raw(this.editor).destroy();
                this.editor = null;
            }
        }
    };
}