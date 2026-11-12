// resources/js/managers/distinctionManager.js
export default function distinctionManager() {
    return {
        modalOpen: false,
        catalogueModalOpen: false,
        isEdit: false,
        id: null,
        distinction_title: '',
        distinction_description: '',

        scope_target: 'BOTH',
        distinction_catalogue_id: '',

        distinction_base_colour: '#1a1a1a',
        mid_layer_mode: 'css',
        mid_layer_asset: '',
        mid_layer_modifier: 'original',
        top_layer_mode: '',
        top_layer_asset: '',
        top_layer_modifier: '',

        modals: {
            distinction: {
                open: false,
                mode: 'create',
                id: null,
                distinction_title: '',
                distinction_description: '',
                scope_target: 'BOTH',
                distinction_catalogue_id: '',
                distinction_base_colour: '#1a1a1a',
                mid_layer_mode: 'css',
                mid_layer_asset: '',
                mid_layer_modifier: 'original',
                top_layer_mode: '',
                top_layer_asset: '',
                top_layer_modifier: ''
            },
            catalogue: {
                open: false,
                mode: 'create',
                id: null,
                catalogue_title: ''
            }
        },

        available_images: [],
        active_skin: 'default',
        loading_images: false,

        init() {
            this.loadImages();
        },

        updateColorFromPicker(model, value) {
            if (typeof this[model] !== 'undefined') {
                this[model] = value;
            }
            if (this.modals.distinction && typeof this.modals.distinction[model] !== 'undefined') {
                this.modals.distinction[model] = value;
            }
        },

        async loadImages() {
            this.loading_images = true;
            try {
                const { ok, data } = await window.Fetch.get('/admin/distinctions/images', {
                    loading: false,
                    showError: false
                });

                if (ok && data) {
                    this.available_images = data.images || [];
                    this.active_skin = data.active_skin || 'default';
                } else {
                    this.available_images = [];
                    this.active_skin = 'default';
                }
            } catch (e) {
                this.available_images = [];
                this.active_skin = 'default';
            } finally {
                this.loading_images = false;
            }
        },

        openModal(mode = 'create', data = {}, target = 'distinction') {
            this.isEdit = mode === 'edit';

            if (target === 'catalogue') {
                this.modals.catalogue.mode = mode;
                this.modals.catalogue.id = mode === 'edit' ? (data.id || null) : null;
                this.modals.catalogue.catalogue_title = mode === 'edit' ? (data.catalogue_title || '') : '';
                this.modals.catalogue.open = true;
                this.catalogueModalOpen = true;
                return;
            }

            const record = mode === 'edit' ? data : {};

            this.id = record.id || null;
            this.distinction_title = record.distinction_title || '';
            this.distinction_description = record.distinction_description || '';
            this.scope_target = record.scope_target || 'BOTH';
            this.distinction_catalogue_id = record.distinction_catalogue_id || '';
            this.distinction_base_colour = record.distinction_base_colour || '#1a1a1a';
            this.mid_layer_mode = record.mid_layer_mode || 'css';
            this.mid_layer_asset = record.mid_layer_asset || '';
            this.mid_layer_modifier = record.mid_layer_modifier || 'original';
            this.top_layer_mode = record.top_layer_mode || '';
            this.top_layer_asset = record.top_layer_asset || '';
            this.top_layer_modifier = record.top_layer_modifier || '';

            this.modals.distinction = {
                open: true,
                mode: mode,
                id: this.id,
                distinction_title: this.distinction_title,
                distinction_description: this.distinction_description,
                scope_target: this.scope_target,
                distinction_catalogue_id: this.distinction_catalogue_id,
                distinction_base_colour: this.distinction_base_colour,
                mid_layer_mode: this.mid_layer_mode,
                mid_layer_asset: this.mid_layer_asset,
                mid_layer_modifier: this.mid_layer_modifier,
                top_layer_mode: this.top_layer_mode,
                top_layer_asset: this.top_layer_asset,
                top_layer_modifier: this.top_layer_modifier
            };

            this.modalOpen = true;
        },

        closeModal(target = 'distinction') {
            if (target === 'catalogue') {
                this.modals.catalogue.open = false;
                this.catalogueModalOpen = false;
                return;
            }
            this.modals.distinction.open = false;
            this.modalOpen = false;
        }
    };
}