// resources/js/managers/divisionManager.js
export default (config = {}) => ({
    modal: null,
    searchQuery: '',
    isGeneratingAi: false,
    sectionTomSelect: null,
    aiEndpoint: config.aiEndpoint || '/admin/sections/generate-description',
    emptyNameMsg: config.emptyNameMsg || 'Please enter a Section Name first.',
    importModalOpen: false,
    importMode: 'merge',

    section: {
        id: '',
        name: '',
        color: '#374151',
        description: '',
        order: 0,
        is_visible: true
    },

    position: {
        id: '',
        section_id: '',
        name: '',
        description: '',
        capacity: 1,
        is_open: true,
        is_visible: true,
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
        if (selectEl && window.Select && !this.sectionTomSelect) {
            this.sectionTomSelect = window.Select.init(selectEl, {
                create: false,
                onChange: (val) => {
                    this.position.section_id = val;
                }
            });
        }
    },

    handleImportSuccess(response) {
        this.importModalOpen = false;
        if (window.Swal && typeof window.Swal.fire === 'function') {
            window.Swal.fire({
                icon: 'success',
                title: 'Import Successful',
                text: response.message || 'Sections and positions have been imported.',
                ...window.Swal.themeConfig()
            }).then(() => {
                window.location.reload();
            });
        } else {
            window.location.reload();
        }
    },

    refreshAutosize() {
        this.$nextTick(() => {
            if (window.Autosize) {
                document.querySelectorAll('textarea[name="description"]').forEach(el => {
                    window.Autosize.update(el);
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
            order: 0,
            is_visible: true
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
            order: secData.order ?? 0,
            is_visible: secData.is_visible !== undefined ? Boolean(secData.is_visible) : true
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
            is_open: true,
            is_visible: true
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
            is_visible: posData.is_visible !== undefined ? Boolean(posData.is_visible) : true,
            section_name: '',
            section_color: ''
        };
        this.modal = 'position-edit';

        this.$nextTick(() => {
            const selectEl = document.getElementById('edit_position_section_id');
            if (selectEl && window.Select) {
                window.Select.setValue(selectEl, String(posData.section_id), true);
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

        // Leverage standardized window.Search helper if available, or fallback to Fuse
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
            if (window.Swal && typeof window.Swal.fire === 'function') {
                window.Swal.fire({
                    icon: 'warning',
                    title: this.emptyNameMsg,
                    ...window.Swal.themeConfig()
                });
            }
            return;
        }

        this.isGeneratingAi = true;
        try {
            // Leverage standardized window.Fetch helper
            const { ok, data } = await window.Fetch.post(this.aiEndpoint, { name: this.section.name }, {
                loading: false,
                showError: false
            });

            if (ok && data && data.description) {
                this.section.description = data.description;
                this.refreshAutosize();
            }
        } catch (error) {
            console.error(error);
        } finally {
            this.isGeneratingAi = false;
        }
    },

    async handleAjaxSuccess() {
        // Updated to use window.updateDOM with sanitize = false (replacing old _US helper)
        if (typeof window.updateDOM === 'function') {
            await window.updateDOM(this.$refs, ['sectionsContainer'], () => {
                this.closeModal();
            }, false);
        } else {
            this.closeModal();
        }
    }
});