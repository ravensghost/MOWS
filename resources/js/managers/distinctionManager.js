// resources/js/managers/distinctionManager.js
export default function distinctionManager() {
    return {
        // 1. State Properties Upfront (Unified Modals & State Contract)
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
                top_layer_modifier: '',
                action: '/admin/distinctions',
                method: 'POST'
            },
            catalogue: {
                open: false,
                mode: 'create',
                id: null,
                catalogue_title: '',
                action: '/admin/distinctions/catalogues',
                method: 'POST'
            }
        },
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
        available_images: [],
        active_skin: 'default',
        loading_images: false,
        searchQuery: '',
        searchHelperInstance: null,
        filteredIds: null,

        // 2. Lifecycle Hook
        init() {
            this.loadImages();
            this.initSearch();
        },

        // 3. Search & Filtering (Fuse.js Integration)
        initSearch() {
            if (window.Search && this.$el && this.$el.querySelector('[data-distinction-item]')) {
                try {
                    this.searchHelperInstance = window.Search.create(
                        '[data-distinction-item]',
                        ['distinction_title', 'distinction_description'],
                        { threshold: 0.35 }
                    );
                } catch (e) {
                    this.searchHelperInstance = null;
                }
            }
            this.$watch('searchQuery', (val) => {
                if (!val.trim() || !this.searchHelperInstance) {
                    this.filteredIds = null;
                    return;
                }
                const results = this.searchHelperInstance.search(val.trim());
                this.filteredIds = results ? results.map(id => Number(id)) : [];
            });
        },

        isItemVisible(id) {
            if (!this.searchQuery.trim() || this.filteredIds === null) return true;
            return this.filteredIds.includes(Number(id));
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

        // 4. Modal Handlers (Using window.Modal utility)
        openModal(mode = 'create', data = {}, target = 'distinction') {
            this.isEdit = mode === 'edit';

            if (target === 'catalogue') {
                const baseUrl = '/admin/distinctions/catalogues';
                if (mode === 'edit') {
                    window.Modal.edit(this.modals.catalogue, data, (id) => `${baseUrl}/${id}`);
                    this.modals.catalogue.method = 'PUT';
                } else {
                    window.Modal.create(this.modals.catalogue, { catalogue_title: '' }, baseUrl);
                    this.modals.catalogue.method = 'POST';
                }
                this.modals.catalogue.open = true;
                this.catalogueModalOpen = true;
                return;
            }

            const baseUrl = '/admin/distinctions';
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

            if (mode === 'edit') {
                window.Modal.edit(this.modals.distinction, record, (id) => `${baseUrl}/${id}`);
                this.modals.distinction.method = 'PUT';
            } else {
                window.Modal.create(this.modals.distinction, {
                    scope_target: 'BOTH',
                    distinction_base_colour: '#1a1a1a',
                    mid_layer_mode: 'css',
                    mid_layer_modifier: 'original'
                }, baseUrl);
                this.modals.distinction.method = 'POST';
            }

            this.modals.distinction.open = true;
            this.modalOpen = true;
        },

        closeModal(target = 'distinction') {
            if (target === 'catalogue') {
                window.Modal.close(this.modals.catalogue);
                this.modals.catalogue.open = false;
                this.catalogueModalOpen = false;
                return;
            }
            window.Modal.close(this.modals.distinction);
            this.modals.distinction.open = false;
            this.modalOpen = false;
        },

        // 5. DOM Refresh Handler (sanitize = false)
        async refreshDOM() {
            if (typeof window.updateDOM === 'function') {
                await window.updateDOM(this.$refs, ['distinctionsTableWrapper'], () => {
                    this.closeModal('distinction');
                    this.closeModal('catalogue');
                    this.$nextTick(() => {
                        this.initSearch();
                    });
                }, false);
            } else {
                this.closeModal('distinction');
                this.closeModal('catalogue');
            }
        }
    };
}