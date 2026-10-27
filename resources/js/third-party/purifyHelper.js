// resources/js/third-party/purifyHelper.js
import DOMPurify from 'dompurify';

/**
 * Global Purify Helper Utility
 * Sanitises HTML strings to prevent XSS attacks.
 */
window.Purify = {
    clean(html, config = {}) {
        return DOMPurify.sanitize(html, config);
    }
};