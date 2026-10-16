export default function rankManager(defaultDeptColor, baseLayer = '#1a1a1aFF') {
    return {
        modal: null,
        defaultDeptColor: defaultDeptColor,
        baseLayer: baseLayer,
        assignSet: { id: '', name: '', assignedIds: [] },
        imageError: { mid: false, top: false },
        rankForm: {
            id: '', set_id: '', name: '', short_name: '', sort_order: 0, description: '',
            mid_type: 'css', mid_image: '', mid_css: '', mid_color: '#1a1a1aFF',
            top_type: 'image', top_image: '', top_css: '', top_color: '#ffffffFF',
            set_name: '', set_color: '', available_images: [], loading_images: false
        },

        initData() {
            this.resetRankForm();
        },

        resetRankForm() {
            this.imageError = { mid: false, top: false };
            this.rankForm = {
                id: '', set_id: '', name: '', short_name: '', sort_order: 0, description: '',
                mid_type: 'css', mid_image: '', mid_css: '', mid_color: this.baseLayer || '#1a1a1aFF',
                top_type: 'image', top_image: '', top_css: '', top_color: '#ffffffFF',
                set_name: '', set_color: this.defaultDeptColor, available_images: [], loading_images: false
            };
        },

        async loadImages(preserveMid = '', preserveTop = '') {
            if (!this.rankForm.set_name) {
                this.rankForm.available_images = [];
                return;
            }
            this.rankForm.loading_images = true;
            try {
                const res = await fetch(`/admin/ranks/images?set_name=${encodeURIComponent(this.rankForm.set_name)}`);
                const data = await res.json();
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
            if (typeof window.updateDOM_US === 'function') {
                await window.updateDOM_US(this.$refs, ['ranksList', 'rankSetSelect', 'assignSectionsList']);
            }
        }
    };
}