// resources/js/app.js

// 1. Third-Party Helpers & Global Wrappers
import './third-party/alpineHelper';
import './third-party/purifyHelper';
import './third-party/sweetalert2Helper';
import './third-party/selectHelper';
import './third-party/searchHelper';
import './third-party/dropzoneHelper';
import './third-party/tiptapHelper';
import './third-party/sortableHelper';
import './third-party/flatpickrHelper';
import './third-party/autosizeHelper';
import './third-party/lightboxHelper';
import './third-party/chartHelper';
import './third-party/tooltip';
import './third-party/clipboard';

// 2. Custom Vanilla Utilities
import './utility/DOM';
import './utility/debounce';
import './utility/storage';
import './utility/fetchHelper';
import './utility/modalHelper';

// 3. Alpine Component & Manager Registry (Initialises Alpine)
import './components/registry';