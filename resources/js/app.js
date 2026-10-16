// Third-party packages
import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import Swal from 'sweetalert2';
import autosize from 'autosize';
import Sortable from 'sortablejs';
import Dropzone from 'dropzone';
import GLightbox from 'glightbox';
import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import TomSelect from 'tom-select';
import flatpickr from 'flatpickr';
import tippy from 'tippy.js';
import DOMPurify from 'dompurify';
import ClipboardJS from 'clipboard';
import Chart from 'chart.js/auto';
import Fuse from 'fuse.js';

// Global window assignments MUST be declared before utils
window.Alpine = Alpine;
window.TipTapEditor = Editor;
window.TipTapStarterKit = StarterKit;
window.TomSelect = TomSelect;
window.flatpickr = flatpickr;
window.tippy = tippy;
window.DOMPurify = DOMPurify;
window.autosize = autosize;
window.Sortable = Sortable;
window.Dropzone = Dropzone;
window.GLightbox = GLightbox;
window.Swal = Swal;
window.ClipboardJS = ClipboardJS;
window.Chart = Chart;
window.Fuse = Fuse;

// Global Utilities (needs window.DOMPurify & window.Swal ready)
import './utils/updateDOM';
import './utils/debounce';
import './utils/storage';
import './utils/clipboard';

// Component Registrations
import tiptapEditor from './components/tiptapEditor';
import adminModal from './components/adminModal';
import applicationManager from './components/applicationManager';
import pageBuilderManager from './components/pageBuilderManager';
import lexiconManager from './components/lexiconManager';
import storyManager from './components/storyManager';
import navigationManager from './components/navigationManager';
import navOptionsManager from './components/navOptionsManager';
import ajaxForm from './components/ajaxForm';
import sortableManager from './components/sortableManager';
import dropzoneManager from './components/dropzoneManager';
import deleteAction from './components/deleteAction';
import themeUploader from './components/themeUploader';
import themeManager from './components/themeManager';
import sessionAlert from './components/sessionAlert';
import awardManager from './components/awardManager';
import colourPicker from './components/colourPicker';
import rankManager from './components/rankManager';
import settingsManager from './components/settingsManager';
import roleManager from './components/roleManager';
import sectionManager from './components/sectionManager';
import newsManager from './components/newsManager';
import characterAvatarManager from './components/characterAvatarManager';

// Alpine plugins & directives
Alpine.plugin(collapse);

Alpine.directive('tooltip', (el, { expression }, { evaluateLater, effect, cleanup }) => {
    const getContent = expression ? evaluateLater(expression) : null;
    let instance = tippy(el, {
        content: el.getAttribute('data-tippy-content') || '',
        allowHTML: false,
        arrow: true,
    });

    if (getContent) {
        effect(() => {
            getContent(value => {
                if (value) {
                    instance.setContent(value);
                    instance.enable();
                } else {
                    instance.disable();
                }
            });
        });
    }

    cleanup(() => {
        if (instance) {
            instance.destroy();
            instance = null;
        }
    });
});

// Custom Alpine directive for Clipboard.js integration (e.g., x-clipboard="'Text to copy'")
Alpine.directive('clipboard', (el, { expression }, { evaluate }) => {
    let clipboard = new ClipboardJS(el, {
        text: () => evaluate(expression)
    });

    clipboard.on('success', (e) => {
        window.dispatchEvent(new CustomEvent('clipboard-success', { detail: { text: e.text } }));
        e.clearSelection();
    });
});

// Component definitions
Alpine.data('tiptapEditor', tiptapEditor);
Alpine.data('adminModal', adminModal);
Alpine.data('applicationManager', applicationManager);
Alpine.data('pageBuilderManager', pageBuilderManager);
Alpine.data('lexiconManager', lexiconManager);
Alpine.data('storyManager', storyManager);
Alpine.data('navigationManager', navigationManager);
Alpine.data('navOptionsManager', navOptionsManager);
Alpine.data('ajaxForm', ajaxForm);
Alpine.data('sortableManager', sortableManager);
Alpine.data('dropzoneComponent', dropzoneManager);
Alpine.data('deleteAction', deleteAction);
Alpine.data('themeUploader', themeUploader);
Alpine.data('themeManager', themeManager);
Alpine.data('sessionAlert', sessionAlert);
Alpine.data('awardManager', awardManager);
Alpine.data('colourPicker', colourPicker);
Alpine.data('rankManager', rankManager);
Alpine.data('settingsManager', settingsManager);
Alpine.data('roleManager', roleManager);
Alpine.data('sectionManager', sectionManager);
Alpine.data('newsManager', newsManager);
Alpine.data('characterAvatarManager', characterAvatarManager);

Alpine.start();