export default function awardManager() {
    return {
        modalOpen: false,
        categoryModalOpen: false, // New state for Subgroup Manager
        isEdit: false,
        awardId: null,
        name: '',
        description: '',

        // NEW PROPERTIES
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
                let res = await fetch('/admin/awards/images');
                let data = await res.json();
                this.available_images = data.images || [];
                this.active_skin = data.active_skin || 'default';
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