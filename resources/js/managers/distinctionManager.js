// resources/js/managers/distinctionManager.js

export default function distinctionManager() {
    const defaultDistinctionState = {
        isOpen: false,
        open: false,
        mode: 'create',
        id: null,
        distinction_title: '',
        distinction_description: '',
        scope_target: 'BOTH',
        distinction_catalogue_id: '',
        distinction_base_colour: '#1a1a1aFF',
        mid_layer_mode: 'css',
        mid_layer_asset: '',
        mid_layer_modifier: 'original',
        mid_layer_hue: '#1a1a1aFF',
        top_layer_mode: 'image',
        top_layer_asset: '',
        top_layer_modifier: '',
        top_layer_hue: '#ffffffFF',
        available_images: [],
        loading_images: false,
        action: '/admin/distinctions',
        method: 'POST'
    };

    return {
        // 1. State Properties Upfront (Unified Modals & Search Contract)
        modals: {
            distinction: { ...defaultDistinctionState },
            catalogue: {
                isOpen: false,
                open: false,
                mode: 'create',
                id: null,
                catalogue_title: '',
                action: '/admin/distinctions/catalogues',
                method: 'POST'
            }
        },
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
            if (window.Search && this.$el && this.$el.querySelector('[data-search-item], [data-distinction-item]')) {
                try {
                    this.searchHelperInstance = window.Search.create(
                        '[data-search-item], [data-distinction-item]',
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

        updateColourFromPicker(model, value) {
            if (this.modals.distinction && typeof this.modals.distinction[model] !== 'undefined') {
                this.modals.distinction[model] = value;
            }
        },

        updateColorFromPicker(model, value) {
            this.updateColourFromPicker(model, value);
        },

        async loadImages() {
            this.loading_images = true;
            this.modals.distinction.loading_images = true;
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
                this.modals.distinction.available_images = this.available_images;
                this.modals.distinction.loading_images = false;
                this.loading_images = false;
            }
        },

        // 4. Modal Handlers (Using window.Modal utility)
        openModal(mode = 'create', data = {}, target = 'distinction') {
            if (target === 'catalogue') {
                const baseUrl = '/admin/distinctions/catalogues';
                if (mode === 'edit') {
                    window.Modal.edit(this.modals.catalogue, data, (id) => `${baseUrl}/${id}`);
                } else {
                    window.Modal.create(this.modals.catalogue, { catalogue_title: '' }, baseUrl);
                }
                return;
            }

            const baseUrl = '/admin/distinctions';
            if (mode === 'edit') {
                window.Modal.edit(this.modals.distinction, {
                    ...defaultDistinctionState,
                    ...data,
                    distinction_base_colour: data.distinction_base_colour || '#1a1a1aFF',
                    available_images: this.available_images,
                    loading_images: false
                }, (id) => `${baseUrl}/${id}`);
            } else {
                window.Modal.create(this.modals.distinction, {
                    ...defaultDistinctionState,
                    available_images: this.available_images,
                    loading_images: false,
                    ...data
                }, baseUrl);
            }
        },

        closeModal(target = null) {
            window.Modal.close(this.modals, target);
        },

        // 5. DOM Refresh Handler (sanitize = false)
        async refreshDOM() {
            if (typeof window.updateDOM === 'function') {
                await window.updateDOM(this.$refs, ['distinctionsTableWrapper'], () => {
                    this.closeModal();
                    this.$nextTick(() => {
                        this.initSearch();
                    });
                }, false);
            } else {
                this.closeModal();
            }
        }
    };
}