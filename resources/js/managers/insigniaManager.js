// resources/js/managers/insigniaManager.js
export default function insigniaManager(defaultDeptColor, baseLayer = '#1a1a1aFF') {
    const defaultInsigniaState = {
        id: '',
        insignia_catalogue_id: '',
        insignia_title: '',
        abbreviated_title: '',
        insignia_sort_order: 0,
        insignia_description: '',
        mid_layer_mode: 'css',
        mid_layer_asset: '',
        mid_layer_modifier: '',
        mid_layer_hue: baseLayer || '#1a1a1aFF',
        top_layer_mode: 'image',
        top_layer_asset: '',
        top_layer_modifier: '',
        top_layer_hue: '#ffffffFF',
        catalogue_title: '',
        set_color: defaultDeptColor,
        is_visible: true,
        available_images: [],
        loading_images: false
    };

    return {
        modal: null,
        importModalOpen: false,
        importMode: 'merge',
        defaultDeptColor: defaultDeptColor,
        baseLayer: baseLayer,
        assignSet: { id: '', catalogue_title: '', assignedIds: [], is_visible: true },
        imageError: { mid: false, top: false },
        modals: {
            insignia: { ...defaultInsigniaState }
        },

        initData() {
            const baseDefaultState = (window.Modal && window.Modal.defaultState && window.Modal.defaultState.insignia)
                ? window.Modal.defaultState.insignia
                : defaultInsigniaState;
            this.modals.insignia = { ...baseDefaultState };
        },

        handleImportSuccess(response) {
            this.importModalOpen = false;
            if (window.Swal && typeof window.Swal.fire === 'function') {
                window.Swal.fire({
                    icon: 'success',
                    title: 'Import Successful',
                    text: response.message || 'Insignia have been imported successfully.',
                    ...window.Swal.themeConfig()
                }).then(() => {
                    window.location.reload();
                });
            } else {
                window.location.reload();
            }
        },

        async loadImages(preserveMid = '', preserveTop = '') {
            if (!this.modals.insignia.catalogue_title) {
                this.modals.insignia.available_images = [];
                return;
            }
            this.modals.insignia.loading_images = true;
            try {
                const { ok, data } = await window.Fetch.get(`/admin/ranks/images?set_name=${encodeURIComponent(this.modals.insignia.catalogue_title)}`, {
                    loading: false,
                    showError: false
                });

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
            }
            this.modals.insignia.loading_images = false;
        },

        openModal(mode, data = {}, domain = 'insignia') {
            this.imageError = { mid: false, top: false };
            const baseDefaultState = (window.Modal && window.Modal.defaultState && window.Modal.defaultState[domain])
                ? window.Modal.defaultState[domain]
                : defaultInsigniaState;

            if (mode === 'create') {
                this.modals[domain] = {
                    ...baseDefaultState,
                    mid_layer_hue: this.baseLayer || '#1a1a1aFF',
                    top_layer_hue: '#ffffffFF',
                    set_color: this.defaultDeptColor,
                    available_images: [],
                    loading_images: false,
                    ...data
                };
                this.modal = 'rank-create';
                return;
            }

            if (mode === 'edit') {
                const savedMidImage = data.mid_layer_asset || '';
                const savedTopImage = data.top_layer_asset || '';

                this.modals[domain] = {
                    ...baseDefaultState,
                    ...data,
                    set_color: data.set_color || this.defaultDeptColor,
                    mid_layer_asset: savedMidImage,
                    top_layer_asset: savedTopImage,
                    mid_layer_hue: data.mid_layer_hue || this.baseLayer || '#1a1a1aFF',
                    top_layer_hue: data.top_layer_hue || '#ffffffFF',
                    is_visible: data.is_visible !== undefined ? Boolean(data.is_visible) : true,
                    available_images: [],
                    loading_images: false
                };

                this.modal = 'rank-edit';
                this.loadImages(savedMidImage, savedTopImage);
            }
        },

        onSetChange(event) {
            const opt = event.target.options[event.target.selectedIndex];
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

        async refreshDOM() {
            this.modal = null;
            if (typeof window.updateDOM === 'function') {
                await window.updateDOM(this.$refs, ['ranksList', 'rankSetSelect', 'assignSectionsList'], null, false);
            }
        }
    };
}