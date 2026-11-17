// resources/js/third-party/purifyHelper.js
import DOMPurify from 'dompurify';

/**
 * Global DOMPurify Helper Wrapper & MOWS Unified Sanitisation Pass
 * Preserves safe custom web font spans, in-universe communication headers,
 * server-signed dice/tarot badges, conlang dialect tags (.mows-lexicon-tag),
 * and x-tooltip attributes while stripping unsafe inline styles and XSS vectors.
 */
const SAFE_FONT_STYLE_REGEX = /^font-family:\s*[a-zA-Z0-9\s,"'-]+;?$/i;

DOMPurify.addHook('uponSanitizeAttribute', (node, data) => {
    if (data.attrName === 'style') {
        const tagName = node.tagName ? node.tagName.toLowerCase() : '';
        const styleVal = (data.attrValue || '').trim();
        if (tagName === 'span' && SAFE_FONT_STYLE_REGEX.test(styleVal)) {
            data.keepAttr = true;
        } else {
            data.keepAttr = false;
        }
    }
});

window.PurifyHelper = {
    config: {
        ADD_TAGS: ['hr', 'span'],
        ADD_ATTR: [
            'class',
            'style',
            'title',
            'aria-label',
            'x-tooltip',
            'data-lexicon-lang',
            'data-lexicon-register',
            'data-lexicon-translated',
            'data-lexicon-font',
            'data-roll-signature',
            'data-roll-type',
            'data-roll-result'
        ]
    },

    clean(dirtyHtml, customConfig = {}) {
        if (!dirtyHtml || typeof dirtyHtml !== 'string') return '';
        return DOMPurify.sanitize(dirtyHtml, {
            ...this.config,
            ...customConfig
        });
    }
};

window.DOMPurify = DOMPurify;

export default DOMPurify;