// resources/js/third-party/alpineHelper.js
import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';

/**
 * Global Alpine.js Configuration & Plugin Registration
 */
Alpine.plugin(collapse);

// Make Alpine globally available if needed for external scripts
window.Alpine = Alpine;

export default Alpine;