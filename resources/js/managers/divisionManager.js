// resources/js/managers/divisionManager.js
export default (config = {}) => ({
    modal: null,
    searchQuery: '',
    isGeneratingAi: false,
    sectionTomSelect: null,
    aiEndpoint: config.aiEndpoint || '/admin/divisions/generate-description',
    emptyNameMsg: config.emptyNameMsg || 'Please enter a Section Name first.',
    importModalOpen: false,
    importMode: 'merge',

    catalogueModalOpen: false,
    catalogueMode: 'create',
    catalogueId: null,
    catalogueTitle: '',
    catalogueDesc: '',
    assignCatalogueModalOpen: false,
    assignedSectionIds: [],

    section: {
        id: '',
        division_title: '',
        division_base_colour: '#374151ff',
        division_description: '',
        division_sort_order: 0,
        is_visible: true,
        division_catalogue_id: ''
    },

    position: {
        id: '',
        division_id: '',
        assignment_title: '',
        assignment_description: '',
        maximum_occupants: 1,
        is_recruitment_open: true,
        is_visible: true,
        division_title: '',
        division_base_colour: ''
    },

    init() {
        this.$nextTick(() => {
            this.initTomSelect();
        });
    },

    initTomSelect() {
        const selectEl = document.getElementById('edit_position_division_id');
        if (selectEl && window.Select && !this.sectionTomSelect) {
            this.sectionTomSelect = window.Select.init(selectEl, {
                create: false,
                onChange: (val) => {
                    this.position.division_id = val;
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
                document.querySelectorAll('textarea[name="division_description"], textarea[name="assignment_description"]').forEach(el => {
                    window.Autosize.update(el);
                });
            }
        });
    },

    openModal(mode, data = {}, type = 'division') {
        if (type === 'division') {
            if (mode === 'create') {
                this.section = {
                    id: '',
                    division_title: '',
                    division_base_colour: '#374151ff',
                    division_description: '',
                    division_sort_order: 0,
                    is_visible: true,
                    division_catalogue_id: ''
                };
                this.modal = 'section-create';
            } else if (mode === 'edit') {
                this.section = {
                    id: data.id,
                    division_title: data.division_title || data.name || '',
                    division_base_colour: data.division_base_colour || data.color || '#374151ff',
                    division_description: data.division_description || data.description || '',
                    division_sort_order: data.division_sort_order ?? data.order ?? 0,
                    is_visible: data.is_visible !== undefined ? Boolean(data.is_visible) : true,
                    division_catalogue_id: data.division_catalogue_id ?? ''
                };
                this.modal = 'section-edit';
            }
            this.refreshAutosize();
        } else if (type === 'assignment') {
            if (mode === 'create') {
                this.position = {
                    id: '',
                    division_id: data.division_id,
                    division_title: data.division_title || data.section_name || '',
                    division_base_colour: data.division_base_colour || data.section_color || '#374151ff',
                    assignment_title: '',
                    assignment_description: '',
                    maximum_occupants: 1,
                    is_recruitment_open: true,
                    is_visible: true
                };
                this.modal = 'position-create';
                this.refreshAutosize();
            } else if (mode === 'edit') {
                this.position = {
                    id: data.id,
                    division_id: String(data.division_id),
                    assignment_title: data.assignment_title || data.name || '',
                    assignment_description: data.assignment_description || data.description || '',
                    maximum_occupants: data.maximum_occupants ?? data.max_assignees ?? data.capacity ?? 1,
                    is_recruitment_open: Boolean(data.is_recruitment_open ?? data.is_open ?? true),
                    is_visible: data.is_visible !== undefined ? Boolean(data.is_visible) : true,
                    division_title: '',
                    division_base_colour: ''
                };
                this.modal = 'position-edit';

                this.$nextTick(() => {
                    const selectEl = document.getElementById('edit_position_division_id');
                    if (selectEl && window.Select) {
                        window.Select.setValue(selectEl, String(data.division_id), true);
                    }
                    this.refreshAutosize();
                });
            }
        }
    },

    closeModal() {
        this.modal = null;
    },

    openCatalogueModal(mode = 'create', data = {}) {
        this.catalogueMode = mode;
        this.catalogueId = data.id || null;
        this.catalogueTitle = data.catalogue_title || '';
        this.catalogueDesc = data.catalogue_description || '';
        this.catalogueModalOpen = true;
    },

    async submitCatalogue() {
        const url = this.catalogueMode === 'edit' && this.catalogueId
            ? `/admin/divisions/catalogues/${this.catalogueId}`
            : '/admin/divisions/catalogues';

        const payload = {
            catalogue_title: this.catalogueTitle,
            catalogue_description: this.catalogueDesc
        };

        const method = this.catalogueMode === 'edit' ? 'put' : 'post';
        const { ok } = await window.Fetch[method](url, payload);
        if (ok) {
            this.catalogueTitle = '';
            this.catalogueDesc = '';
            this.catalogueMode = 'create';
            this.catalogueId = null;
            window.location.reload();
        }
    },

    async deleteCatalogue(id) {
        const confirmResult = await window.Swal.confirm(
            'Delete Catalogue?',
            'Are you sure you want to delete this division catalogue?',
            'Delete'
        );
        if (!confirmResult.isConfirmed) return;

        const { ok } = await window.Fetch.delete(`/admin/divisions/catalogues/${id}`);
        if (ok) {
            window.location.reload();
        }
    },

    openAssignCatalogueModal(catId, divisionIds = []) {
        this.catalogueId = catId;
        this.assignedSectionIds = (divisionIds || []).map(String);
        this.assignCatalogueModalOpen = true;
    },

    async submitCatalogueAssignment() {
        if (!this.catalogueId) return;
        const { ok } = await window.Fetch.post(`/admin/divisions/catalogues/${this.catalogueId}/assign`, {
            division_ids: this.assignedSectionIds
        });
        if (ok) {
            this.assignCatalogueModalOpen = false;
            window.location.reload();
        }
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

    isItemVisible(posName, posDesc, secName) {
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
        const title = this.section.division_title || '';
        if (!title.trim()) {
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
            const { ok, data } = await window.Fetch.post(this.aiEndpoint, { name: title, division_title: title }, {
                loading: false,
                showError: false
            });

            if (ok && data) {
                const desc = data.division_description || data.description || data.data?.division_description || data.data?.description;
                if (desc) {
                    this.section.division_description = desc;
                    this.refreshAutosize();
                }
            }
        } catch (error) {
            console.error(error);
        } finally {
            this.isGeneratingAi = false;
        }
    },

    async handleAjaxSuccess() {
        if (typeof window.updateDOM === 'function') {
            await window.updateDOM(this.$refs, ['sectionsContainer'], () => {
                this.closeModal();
            }, false);
        } else {
            this.closeModal();
        }
    }
});