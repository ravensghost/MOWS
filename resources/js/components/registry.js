// resources/js/components/registry.js
import Alpine from '../third-party/alpineHelper';

// 1. Components
import ajaxForm from './ajaxForm';
import autoSave from './autoSave';
import colourPicker from './colourPicker';
import deleteAction from './deleteAction';
import folioEdit from './folioEdit';
import thematicBindingCard from './thematicBindingCard';
import volumeReview from './volumeReview';

// 2. Managers
import accessClearanceRoleManager from '../managers/accessClearanceRoleManager';
import assetManager from '../managers/assetManager';
import avatarManager from '../managers/avatarManager';
import bookBuilderManager from '../managers/bookBuilderManager';
import bulletinManager from '../managers/bulletinManager';
import candidatePetitionManager from '../managers/candidatePetitionManager';
import chronologicalRegisterManager from '../managers/chronologicalRegisterManager';
import configurationManager from '../managers/configurationManager';
import distinctionManager from '../managers/distinctionManager';
import divisionManager from '../managers/divisionManager';
import encyclopediaCodexManager from '../managers/encyclopediaCodexManager';
import metadataManager from './metadataManager';
import insigniaManager from '../managers/insigniaManager';
import lexiconCodexManager from '../managers/lexiconCodexManager';
import loaHubManager from '../managers/loaHubManager';
import publicationManager from '../managers/publicationManager';
import tableOfContentParametersManager from '../managers/tableOfContentParametersManager';
import tableOfContentsManager from '../managers/tableOfContentsManager';
import thematicBindingManager from '../managers/thematicBindingManager';

// 3. Third-Party Component Helpers
import dropzoneHelper from '../third-party/dropzoneHelper';
import sortableHelper from '../third-party/sortableHelper';
import sweetalert2Helper from '../third-party/sweetalert2Helper';
import tiptapHelper from '../third-party/tiptapHelper';

// Register Components
Alpine.data('ajaxForm', ajaxForm);
Alpine.data('autoSave', autoSave);
Alpine.data('colourPicker', colourPicker);
Alpine.data('deleteAction', deleteAction);
Alpine.data('folioEdit', folioEdit);
Alpine.data('metadataManager', metadataManager);
Alpine.data('thematicBindingCard', thematicBindingCard);
Alpine.data('volumeReview', volumeReview);

// Register Managers
Alpine.data('accessClearanceRoleManager', accessClearanceRoleManager);
Alpine.data('assetManager', assetManager);
Alpine.data('avatarManager', avatarManager);
Alpine.data('bookBuilderManager', bookBuilderManager);
Alpine.data('bulletinManager', bulletinManager);
Alpine.data('candidatePetitionManager', candidatePetitionManager);
Alpine.data('chronologicalRegisterManager', chronologicalRegisterManager);
Alpine.data('configurationManager', configurationManager);
Alpine.data('distinctionManager', distinctionManager);
Alpine.data('divisionManager', divisionManager);
Alpine.data('encyclopediaCodexManager', encyclopediaCodexManager);
Alpine.data('insigniaManager', insigniaManager);
Alpine.data('lexiconCodexManager', lexiconCodexManager);
Alpine.data('loaHubManager', loaHubManager);
Alpine.data('publicationManager', publicationManager);
Alpine.data('tableOfContentParametersManager', tableOfContentParametersManager);
Alpine.data('tableOfContentsManager', tableOfContentsManager);
Alpine.data('thematicBindingManager', thematicBindingManager);

// Register Third-Party Component Helpers
Alpine.data('dropzoneComponent', dropzoneHelper);
Alpine.data('sortableHelper', sortableHelper);
Alpine.data('sweetalert2Helper', sweetalert2Helper);
Alpine.data('tiptapEditor', tiptapHelper);

// Initialise Alpine
Alpine.start();