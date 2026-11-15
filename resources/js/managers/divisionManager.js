// resources/js/managers/divisionManager.js
export default (config = {}) => ({
    // 1. State Properties Upfront (Unified Modals & State Contract)
    modals: {
        section: {
            open: false,
            mode: 'create',
            id: '',
            division_title: '',
            division_base_colour: '#374151ff',
            division_description: '',
            division_sort_order: 0,
            is_visible: true,
            division_catalogue_id: '',
            action: '/admin/divisions',
            method: 'POST'
        },
        position: {
            open: false,
            mode: 'create',
            id: '',
            division_id: '',
            assignment_title: '',
            assignment_description: '',
            max_assignees: 1,
            is_open: true,
            is_visible: true,
            division_title: '',
            division_base_colour: '',
            action: '/admin/assignments',
            method: 'POST'
        },
        import: {
            open: false,
            mode: 'create',
            action: '/admin/divisions/import',
            method: 'POST'
        }
    },
    modal: null,
    searchQuery: '',
    isGeneratingAi: false,
    sectionTomSelect: null,
    aiEndpoint: config.aiEndpoint || '/admin/sections/generate-description',
    emptyNameMsg: config.emptyNameMsg || 'Please enter a Section Name first.',
    importMode: 'merge',

    // 2. Lifecycle Hook
    init() {
        this.$nextTick(() => {
            this.initTomSelect();
        });
    },

    // 3. Search & Filtering (Fuse.js Integration)
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

    initTomSelect() {
        const selectEl = document.getElementById('edit_position_division_id');
        if (selectEl && window.Select && !this.sectionTomSelect) {
            this.sectionTomSelect = window.Select.init(selectEl, {
                create: false,
                onChange: (val) => {
                    this.modals.position.division_id = val;
                }
            });
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

    // 4. Modal Handlers (Using window.Modal utility)
    openModal(mode = 'create', data = {}, type = 'division') {
        const divisionBaseUrl = '/admin/divisions';
        const assignmentBaseUrl = '/admin/assignments';

        if (type === 'division') {
            if (mode === 'create') {
                window.Modal.create(this.modals.section, {
                    division_title: '',
                    division_base_colour: '#374151ff',
                    division_description: '',
                    division_sort_order: 0,
                    is_visible: true,
                    division_catalogue_id: ''
                }, divisionBaseUrl);
                this.modals.section.method = 'POST';
                this.modal = 'section-create';
            } else if (mode === 'edit') {
                window.Modal.edit(this.modals.section, {
                    id: data.id,
                    division_title: data.division_title || data.name || '',
                    division_base_colour: data.division_base_colour || data.color || '#374151ff',
                    division_description: data.division_description || data.description || '',
                    division_sort_order: data.division_sort_order ?? data.order ?? 0,
                    is_visible: data.is_visible !== undefined ? Boolean(data.is_visible) : true,
                    division_catalogue_id: data.division_catalogue_id ?? ''
                }, (id) => `${divisionBaseUrl}/${id}`);
                this.modals.section.method = 'PUT';
                this.modal = 'section-edit';
            }
            this.modals.section.open = true;
            this.refreshAutosize();
        } else if (type === 'assignment') {
            if (mode === 'create') {
                window.Modal.create(this.modals.position, {
                    division_id: data.division_id,
                    division_title: data.division_title || data.section_name || '',
                    division_base_colour: data.division_base_colour || data.section_color || '#374151ff',
                    assignment_title: '',
                    assignment_description: '',
                    max_assignees: 1,
                    is_open: true,
                    is_visible: true
                }, assignmentBaseUrl);
                this.modals.position.method = 'POST';
                this.modal = 'position-create';
                this.refreshAutosize();
            } else if (mode === 'edit') {
                window.Modal.edit(this.modals.position, {
                    id: data.id,
                    division_id: String(data.division_id),
                    assignment_title: data.assignment_title || data.name || '',
                    assignment_description: data.assignment_description || data.description || '',
                    max_assignees: data.max_assignees ?? data.capacity ?? 1,
                    is_open: Boolean(data.is_open),
                    is_visible: data.is_visible !== undefined ? Boolean(data.is_visible) : true
                }, (id) => `${assignmentBaseUrl}/${id}`);
                this.modals.position.method = 'PUT';
                this.modal = 'position-edit';

                this.$nextTick(() => {
                    const selectEl = document.getElementById('edit_position_division_id');
                    if (selectEl && window.Select) {
                        window.Select.setValue(selectEl, String(data.division_id), true);
                    }
                    this.refreshAutosize();
                });
            }
            this.modals.position.open = true;
        } else if (type === 'import') {
            window.Modal.create(this.modals.import, {}, `${divisionBaseUrl}/import`);
            this.modals.import.open = true;
            this.modal = 'import';
        }
    },

    closeModal() {
        window.Modal.close(this.modals.section);
        window.Modal.close(this.modals.position);
        window.Modal.close(this.modals.import);
        this.modals.section.open = false;
        this.modals.position.open = false;
        this.modals.import.open = false;
        this.modal = null;
    },

    async generateAiDescription() {
        const title = this.modals.section.division_title || '';
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

            if (ok && data && (data.description || data.division_description)) {
                this.modals.section.division_description = data.division_description || data.description;
                this.refreshAutosize();
            }
        } catch (error) {
            console.error(error);
        } finally {
            this.isGeneratingAi = false;
        }
    },

    handleImportSuccess(response) {
        this.closeModal();
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

    // 5. DOM Refresh Handler (sanitize = false)
    async refreshDOM() {
        if (typeof window.updateDOM === 'function') {
            await window.updateDOM(this.$refs, ['sectionsContainer'], () => {
                this.closeModal();
                this.$nextTick(() => {
                    this.initTomSelect();
                });
            }, false);
        } else {
            this.closeModal();
        }
    }
});