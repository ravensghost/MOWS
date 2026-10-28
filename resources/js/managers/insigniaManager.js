// resources/js/managers/insigniaManager.js
export default function insigniaManager(defaultDeptColor, baseLayer = '#1a1a1aFF') {
    return {
        modal: null,
        importModalOpen: false,
        importMode: 'merge',
        defaultDeptColor: defaultDeptColor,
        baseLayer: baseLayer,
        assignSet: { id: '', name: '', assignedIds: [], is_visible: true },
        imageError: { mid: false, top: false },
        rankForm: {
            id: '', set_id: '', name: '', short_name: '', sort_order: 0, description: '',
            mid_type: 'css', mid_image: '', mid_css: '', mid_color: '#1a1a1aFF',
            top_type: 'image', top_image: '', top_css: '', top_color: '#ffffffFF',
            set_name: '', set_color: '', is_visible: true, available_images: [], loading_images: false
        },

        initData() {
            this.resetRankForm();
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

        resetRankForm() {
            this.imageError = { mid: false, top: false };
            this.rankForm = {
                id: '', set_id: '', name: '', short_name: '', sort_order: 0, description: '',
                mid_type: 'css', mid_image: '', mid_css: '', mid_color: this.baseLayer || '#1a1a1aFF',
                top_type: 'image', top_image: '', top_css: '', top_color: '#ffffffFF',
                set_name: '', set_color: this.defaultDeptColor, is_visible: true, available_images: [], loading_images: false
            };
        },

        async loadImages(preserveMid = '', preserveTop = '') {
            if (!this.rankForm.set_name) {
                this.rankForm.available_images = [];
                return;
            }
            this.rankForm.loading_images = true;
            try {
                // Leverage standardized window.Fetch helper
                const { ok, data } = await window.Fetch.get(`/admin/ranks/images?set_name=${encodeURIComponent(this.rankForm.set_name)}`, {
                    loading: false,
                    showError: false
                });

                if (ok && data) {
                    this.rankForm.available_images = data.images || [];

                    this.$nextTick(() => {
                        if (preserveMid) {
                            this.rankForm.mid_image = preserveMid;
                            this.imageError.mid = false;
                        }
                        if (preserveTop) {
                            this.rankForm.top_image = preserveTop;
                            this.imageError.top = false;
                        }
                    });
                } else {
                    this.rankForm.available_images = [];
                }
            } catch (e) {
                this.rankForm.available_images = [];
            }
            this.rankForm.loading_images = false;
        },

        openCreateRank() {
            this.resetRankForm();
            this.modal = 'rank-create';
        },

        openEditRank(rank, set, badgeColor) {
            const savedMidImage = rank.mid_image || '';
            const savedTopImage = rank.top_image || '';
            this.imageError = { mid: false, top: false };

            this.rankForm = {
                ...rank,
                set_id: set.id,
                set_name: set.name,
                set_color: badgeColor || this.defaultDeptColor,
                mid_image: savedMidImage,
                top_image: savedTopImage,
                mid_color: rank.mid_color || this.baseLayer || '#1a1a1aFF',
                top_color: rank.top_color || '#ffffffFF',
                is_visible: rank.is_visible !== undefined ? Boolean(rank.is_visible) : true,
                available_images: [],
                loading_images: false
            };

            this.modal = 'rank-edit';
            this.loadImages(savedMidImage, savedTopImage);
        },

        onSetChange(event) {
            const opt = event.target.options[event.target.selectedIndex];
            this.rankForm.set_name = opt.dataset.name || '';
            this.rankForm.set_color = opt.dataset.color || this.defaultDeptColor;
            this.rankForm.mid_image = '';
            this.rankForm.top_image = '';
            this.imageError = { mid: false, top: false };
            this.loadImages();
        },

        get midImageUrl() {
            if (this.rankForm.mid_type !== 'image' || !this.rankForm.set_name || !this.rankForm.mid_image) {
                return '';
            }
            return `/images/ranks/${encodeURIComponent(this.rankForm.set_name)}/${encodeURIComponent(this.rankForm.mid_image)}`;
        },

        get topImageUrl() {
            if (this.rankForm.top_type !== 'image' || !this.rankForm.set_name || !this.rankForm.top_image) {
                return '';
            }
            return `/images/ranks/${encodeURIComponent(this.rankForm.set_name)}/${encodeURIComponent(this.rankForm.top_image)}`;
        },

        async refreshDOM_US() {
            this.modal = null;
            // Updated to use window.updateDOM with sanitize = false (replacing old _US helper)
            if (typeof window.updateDOM === 'function') {
                await window.updateDOM(this.$refs, ['ranksList', 'rankSetSelect', 'assignSectionsList'], null, false);
            }
        }
    };
}