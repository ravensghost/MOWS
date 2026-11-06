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

    // Division Catalogues Management State
    catalogueModalOpen: false,
    catalogueMode: 'create',
    catalogueId: '',
    catalogueTitle: '',
    catalogueDesc: '',
    assignCatalogueModalOpen: false,
    assignCatalogueId: '',
    assignedSectionIds: [],

    section: {
        id: '',
        name: '',
        color: window.getThemeColour ? window.getThemeColour('--theme-border-colour') : '#374151',
        description: '',
        order: 0,
        is_visible: true,
        division_catalogue_id: ''
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

    // --- Division Catalogue Handlers ---
    openCatalogueModal(mode, data = {}) {
        this.catalogueMode = mode;
        this.catalogueId = data.id || '';
        this.catalogueTitle = data.title || '';
        this.catalogueDesc = data.description || '';
        this.catalogueModalOpen = true;
    },

    openAssignCatalogueModal(catalogueId, currentSectionIds = []) {
        this.assignCatalogueId = catalogueId;
        this.assignedSectionIds = currentSectionIds.map(String);
        this.assignCatalogueModalOpen = true;
    },

    async submitCatalogue() {
        const url = this.catalogueMode === 'edit'
            ? `/admin/division-catalogues/${this.catalogueId}`
            : '/admin/division-catalogues';
        const method = this.catalogueMode === 'edit' ? 'PUT' : 'POST';

        const { ok, data } = await window.Fetch.request(url, method, {
            catalogue_title: this.catalogueTitle,
            catalogue_description: this.catalogueDesc
        }, { loading: true, loadingTitle: 'Saving Catalogue...' });

        if (ok) {
            this.catalogueModalOpen = false;
            if (window.Swal && typeof window.Swal.toast === 'function') {
                window.Swal.toast('success', data.message || 'Catalogue saved successfully.');
            }
            await this.refreshDOM();
        }
    },

    async deleteCatalogue(catalogueId) {
        const result = await window.Swal.confirm('Delete Catalogue?', 'This will unassign its divisions.', 'Delete');
        if (!result.isConfirmed) return;

        const { ok, data } = await window.Fetch.delete(`/admin/division-catalogues/${catalogueId}`, {}, { loading: true });
        if (ok) {
            if (window.Swal && typeof window.Swal.toast === 'function') {
                window.Swal.toast('success', data.message || 'Catalogue deleted.');
            }
            await this.refreshDOM();
        }
    },

    async submitCatalogueAssignment() {
        const { ok, data } = await window.Fetch.post(`/admin/division-catalogues/${this.assignCatalogueId}/assign`, {
            sections: this.assignedSectionIds
        }, { loading: true, loadingTitle: 'Updating Assignments...' });

        if (ok) {
            this.assignCatalogueModalOpen = false;
            if (window.Swal && typeof window.Swal.toast === 'function') {
                window.Swal.toast('success', data.message || 'Divisions assigned successfully.');
            }
            await this.refreshDOM();
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
            color: window.getThemeColour ? window.getThemeColour('--theme-border-colour') : '#374151',
            description: '',
            order: 0,
            is_visible: true,
            division_catalogue_id: ''
        };
        this.modal = 'section-create';
        this.refreshAutosize();
    },

    openEditSection(secData) {
        this.section = {
            id: secData.id,
            name: secData.name || '',
                color: secData.color || window.getThemeColour ? window.getThemeColour('--theme-border-colour') : '#374151',
                description: secData.description || '',
            order: secData.order ?? 0,
            is_visible: secData.is_visible !== undefined ? Boolean(secData.is_visible) : true,
            division_catalogue_id: secData.division_catalogue_id || ''
        };
        this.modal = 'section-edit';
        this.refreshAutosize();
    },

    openCreatePosition(posData) {
        this.position = {
            id: '',
            section_id: posData.section_id,
            section_name: posData.section_name || '',
            section_color: posData.section_color || window.getThemeColour ? window.getThemeColour('--theme-border-colour') : '#374151',
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

    async refreshDOM() {
        if (typeof window.updateDOM === 'function') {
            await window.updateDOM(this.$refs, ['sectionsContainer'], () => {
                this.closeModal();
                this.catalogueModalOpen = false;
                this.assignCatalogueModalOpen = false;
            }, false);
        } else {
            this.closeModal();
            this.catalogueModalOpen = false;
            this.assignCatalogueModalOpen = false;
        }
    }
});