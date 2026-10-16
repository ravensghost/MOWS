export default (config = {}) => ({
    modal: null,
    searchQuery: '',
    isGeneratingAi: false,
    sectionTomSelect: null,
    aiEndpoint: config.aiEndpoint || '/admin/sections/generate-description',
    emptyNameMsg: config.emptyNameMsg || 'Please enter a Section Name first.',

    section: {
        id: '',
        name: '',
        color: '#374151',
        description: '',
        order: 0
    },

    position: {
        id: '',
        section_id: '',
        name: '',
        description: '',
        capacity: 1,
        is_open: true,
        section_name: '',
        section_color: ''
    },

    init() {
        this.$nextTick(() => {
            this.initTomSelect();
        });
    },

    initTomSelect() {
        const selectEl = document.getElementById('edit_position_section_id');
        if (selectEl && window.TomSelect && !this.sectionTomSelect) {
            this.sectionTomSelect = new window.TomSelect(selectEl, {
                create: false,
                onChange: (val) => {
                    this.position.section_id = val;
                }
            });
        }
    },

    refreshAutosize() {
        this.$nextTick(() => {
            if (window.autosize) {
                document.querySelectorAll('textarea[name="description"]').forEach(el => {
                    window.autosize.update(el);
                });
            }
        });
    },

    openCreateSection() {
        this.section = {
            id: '',
            name: '',
            color: '#374151',
            description: '',
            order: 0
        };
        this.modal = 'section-create';
        this.refreshAutosize();
    },

    openEditSection(secData) {
        this.section = {
            id: secData.id,
            name: secData.name || '',
            color: secData.color || '#374151',
            description: secData.description || '',
            order: secData.order ?? 0
        };
        this.modal = 'section-edit';
        this.refreshAutosize();
    },

    openCreatePosition(posData) {
        this.position = {
            id: '',
            section_id: posData.section_id,
            section_name: posData.section_name || '',
            section_color: posData.section_color || '#374151',
            name: '',
            description: '',
            capacity: 1,
            is_open: true
        };
        this.modal = 'position-create';
        this.refreshAutosize();
    },

    openEditPosition(posData) {
        this.position = {
            id: posData.id,
            section_id: String(posData.section_id),
            name: posData.name || '',
            description: posData.description || '',
            capacity: posData.capacity ?? 1,
            is_open: Boolean(posData.is_open),
            section_name: '',
            section_color: ''
        };
        this.modal = 'position-edit';

        this.$nextTick(() => {
            if (this.sectionTomSelect) {
                this.sectionTomSelect.setValue(String(posData.section_id), true);
            }
            this.refreshAutosize();
        });
    },

    closeModal() {
        this.modal = null;
    },

    matchesSearch(secName, secDesc, posNames) {
        if (!this.searchQuery.trim()) return true;
        const q = this.searchQuery.trim();
        const haystack = [secName || '', secDesc || '', ...(posNames || [])];
        if (window.Fuse) {
            const fuse = new window.Fuse(
                haystack.map(item => ({ text: String(item) })),
                { keys: ['text'], threshold: 0.35 }
            );
            return fuse.search(q).length > 0;
        }
        return haystack.some(text => String(text).toLowerCase().includes(q.toLowerCase()));
    },

    matchesPosition(posName, posDesc, secName) {
        if (!this.searchQuery.trim()) return true;
        const q = this.searchQuery.trim();
        if (String(secName || '').toLowerCase().includes(q.toLowerCase())) return true;
        const haystack = [posName || '', posDesc || ''];
        if (window.Fuse) {
            const fuse = new window.Fuse(
                haystack.map(item => ({ text: String(item) })),
                { keys: ['text'], threshold: 0.35 }
            );
            return fuse.search(q).length > 0;
        }
        return haystack.some(text => String(text).toLowerCase().includes(q.toLowerCase()));
    },

    async generateAiDescription() {
        if (!this.section.name || !this.section.name.trim()) {
            if (window.Swal) {
                window.Swal.fire({
                    icon: 'warning',
                    title: this.emptyNameMsg
                });
            }
            return;
        }

        this.isGeneratingAi = true;
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const response = await fetch(this.aiEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ name: this.section.name })
            });
            const data = await response.json();
            if (data.description) {
                this.section.description = data.description;
                this.refreshAutosize();
            }
        } catch (error) {
            console.error(error);
        } finally {
            this.isGeneratingAi = false;
        }
    },

    handleAjaxSuccess() {
        window.updateDOM_US(this.$refs, ['sectionsContainer'], () => {
            this.closeModal();
        });
    }
});