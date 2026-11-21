// resources/js/managers/insigniaManager.js

export default function insigniaManager(defaultDeptColour = '#991b1bFF', baseLayer = '#1a1a1aFF') {
    const defaultInsigniaState = {
        isOpen: false,
        open: false,
        mode: 'create',
        id: '',
        insignia_catalogue_id: '',
        insignia_title: '',
        abbreviated_title: '',
        insignia_sort_order: 0,
        insignia_synopsis: '',
        mid_layer_mode: 'css',
        mid_layer_asset: '',
        mid_layer_modifier: '',
        mid_layer_hue: baseLayer || '#1a1a1aFF',
        top_layer_mode: 'image',
        top_layer_asset: '',
        top_layer_modifier: '',
        top_layer_hue: '#ffffffFF',
        catalogue_title: '',
        set_color: defaultDeptColour,
        is_visible: true,
        available_images: [],
        loading_images: false,
        action: '/admin/insignia',
        method: 'POST'
    };

    return {
        // 1. State Properties Upfront (Unified Modals & Search Contract)
        modals: {
            insignia: { ...defaultInsigniaState },
            catalogue: {
                isOpen: false,
                open: false,
                mode: 'create',
                id: '',
                catalogue_title: '',
                assignedIds: [],
                is_visible: true,
                action: '/admin/insignia/catalogues',
                method: 'POST'
            },
            import: {
                isOpen: false,
                open: false,
                mode: 'create',
                importMode: 'merge',
                action: '/admin/insignia/import',
                method: 'POST'
            }
        },
        defaultDeptColor: defaultDeptColour,
        baseLayer: baseLayer,
        assignSet: { id: '', catalogue_title: '', assignedIds: [], is_visible: true },
        imageError: { mid: false, top: false },
        searchQuery: '',
        searchHelperInstance: null,
        filteredIds: null,

        // 2. Lifecycle Hook
        init() {
            this.initData();
            this.initSearch();
        },

        // 3. Search & Filtering (Fuse.js Integration)
        initSearch() {
            if (window.Search && this.$el && this.$el.querySelector('[data-search-item], [data-insignia-item]')) {
                try {
                    this.searchHelperInstance = window.Search.create(
                        '[data-search-item], [data-insignia-item]',
                        ['insignia_title', 'abbreviated_title', 'catalogue_title'],
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

        initData() {
            const baseDefaultState = (window.Modal && window.Modal.defaultState && window.Modal.defaultState.insignia)
                ? window.Modal.defaultState.insignia
                : defaultInsigniaState;
            this.modals.insignia = { ...baseDefaultState };
        },

        async loadImages(preserveMid = '', preserveTop = '') {
            if (!this.modals.insignia.catalogue_title) {
                this.modals.insignia.available_images = [];
                return;
            }
            this.modals.insignia.loading_images = true;
            try {
                const { ok, data } = await window.Fetch.get(
                    `/admin/insignia/images?set_name=${encodeURIComponent(this.modals.insignia.catalogue_title)}`,
                    { loading: false, showError: false }
                );

                if (ok && data) {
                    this.modals.insignia.available_images = data.images || [];

                    this.$nextTick(() => {
                        if (preserveMid) {
                            this.modals.insignia.mid_layer_asset = preserveMid;
                            this.imageError.mid = false;
                        }
                        if (preserveTop) {
                            this.modals.insignia.top_layer_asset = preserveTop;
                            this.imageError.top = false;
                        }
                    });
                } else {
                    this.modals.insignia.available_images = [];
                }
            } catch (e) {
                this.modals.insignia.available_images = [];
            } finally {
                this.modals.insignia.loading_images = false;
            }
        },

        // 4. Modal Handlers (Using window.Modal utility)
        openModal(mode = 'create', data = {}, target = 'insignia') {
            if (target === 'catalogue') {
                const catalogueBaseUrl = '/admin/insignia/catalogues';
                if (mode === 'edit') {
                    window.Modal.edit(this.modals.catalogue, {
                        ...data,
                        assignedIds: Array.isArray(data.assignedIds) ? data.assignedIds : [],
                        is_visible: data.is_visible !== undefined ? Boolean(data.is_visible) : true
                    }, (id) => `${catalogueBaseUrl}/${id}`);
                } else {
                    window.Modal.create(this.modals.catalogue, {
                        catalogue_title: '',
                        assignedIds: [],
                        is_visible: true
                    }, catalogueBaseUrl);
                }
                return;
            }

            if (target === 'import') {
                window.Modal.create(this.modals.import, { importMode: 'merge' }, '/admin/insignia/import');
                return;
            }

            this.imageError = { mid: false, top: false };
            const baseUrl = '/admin/insignia';

            if (mode === 'create') {
                window.Modal.create(this.modals.insignia, {
                    ...defaultInsigniaState,
                    mid_layer_hue: this.baseLayer || '#1a1a1aFF',
                    top_layer_hue: '#ffffffFF',
                    set_color: this.defaultDeptColor,
                    available_images: [],
                    loading_images: false,
                    ...data
                }, baseUrl);
                return;
            }

            if (mode === 'edit') {
                const savedMidImage = data.mid_layer_asset || '';
                const savedTopImage = data.top_layer_asset || '';

                window.Modal.edit(this.modals.insignia, {
                    ...defaultInsigniaState,
                    ...data,
                    set_color: data.set_color || this.defaultDeptColor,
                    mid_layer_asset: savedMidImage,
                    top_layer_asset: savedTopImage,
                    mid_layer_hue: data.mid_layer_hue || this.baseLayer || '#1a1a1aFF',
                    top_layer_hue: data.top_layer_hue || '#ffffffFF',
                    is_visible: data.is_visible !== undefined ? Boolean(data.is_visible) : true,
                    available_images: [],
                    loading_images: false
                }, (id) => `${baseUrl}/${id}`);

                this.loadImages(savedMidImage, savedTopImage);
            }
        },

        closeModal(target = null) {
            window.Modal.close(this.modals, target);
        },

        onSetChange(event) {
            const opt = event.target.options[event.target.selectedIndex];
            if (!opt) return;
            this.modals.insignia.catalogue_title = opt.dataset.catalogueTitle || opt.dataset.name || '';
            this.modals.insignia.set_color = opt.dataset.color || this.defaultDeptColor;
            this.modals.insignia.mid_layer_asset = '';
            this.modals.insignia.top_layer_asset = '';
            this.imageError = { mid: false, top: false };
            this.loadImages();
        },

        get midImageUrl() {
            if (this.modals.insignia.mid_layer_mode !== 'image' || !this.modals.insignia.catalogue_title || !this.modals.insignia.mid_layer_asset) {
                return '';
            }
            return `/images/ranks/${encodeURIComponent(this.modals.insignia.catalogue_title)}/${encodeURIComponent(this.modals.insignia.mid_layer_asset)}`;
        },

        get topImageUrl() {
            if (this.modals.insignia.top_layer_mode !== 'image' || !this.modals.insignia.catalogue_title || !this.modals.insignia.top_layer_asset) {
                return '';
            }
            return `/images/ranks/${encodeURIComponent(this.modals.insignia.catalogue_title)}/${encodeURIComponent(this.modals.insignia.top_layer_asset)}`;
        },

        async handleImportSuccess(response) {
            this.closeModal('import');
            if (window.Swal && typeof window.Swal.toast === 'function') {
                window.Swal.toast('success', response?.message || 'Insignia have been imported successfully.');
            }
            await this.refreshDOM();
        },

        // 5. DOM Refresh Handler (sanitize = false)
        async refreshDOM() {
            if (typeof window.updateDOM === 'function') {
                await window.updateDOM(this.$refs, ['ranksList', 'rankSetSelect', 'assignSectionsList', 'insigniaTableWrapper'], () => {
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