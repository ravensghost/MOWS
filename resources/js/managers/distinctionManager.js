// resources/js/managers/distinctionManager.js
export default function distinctionManager() {
    return {
        modalOpen: false,
        categoryModalOpen: false, // State for Subgroup Manager
        isEdit: false,
        awardId: null,
        name: '',
        description: '',

        // PROPERTIES
        master_group: 'BOTH',
        award_category_id: '',

        base_color: '#1a1a1a',
        mid_type: 'css',
        mid_image: '',
        mid_css: 'original',
        top_type: '',
        top_image: '',
        top_css: '',
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
        },

        async loadImages() {
            try {
                // Leverage standardized window.Fetch helper
                const { ok, data } = await window.Fetch.get('/admin/awards/images', {
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
            }
        },

        openCreateModal() {
            this.isEdit = false;
            this.awardId = null;
            this.name = '';
            this.description = '';
            this.master_group = 'BOTH';
            this.award_category_id = '';
            this.base_color = '#1a1a1a';
            this.mid_type = 'css';
            this.mid_image = '';
            this.mid_css = 'original';
            this.top_type = '';
            this.top_image = '';
            this.top_css = '';
            this.modalOpen = true;
        },

        openEditModal(award) {
            this.isEdit = true;
            this.awardId = award.id;
            this.name = award.name;
            this.description = award.description || '';
            this.master_group = award.master_group || 'BOTH';
            this.award_category_id = award.award_category_id || '';
            this.base_color = award.base_color || '#1a1a1a';
            this.mid_type = award.mid_type || 'css';
            this.mid_image = award.mid_image || '';
            this.mid_css = award.mid_css || 'original';
            this.top_type = award.top_type || '';
            this.top_image = award.top_image || '';
            this.top_css = award.top_css || '';
            this.modalOpen = true;
        }
    };
}