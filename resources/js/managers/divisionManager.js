// resources/js/managers/divisionManager.js

export default function divisionManager(config = {}) {
    const divisionState = {
        isOpen: false,
        open: false,
        mode: 'create',
        id: '',
        division_title: '',
        division_base_colour: '#374151FF',
        division_synopsis: '',
        division_sort_order: 0,
        is_visible: true,
        division_catalogue_id: '',
        insignia_catalogue_id: '',
        action: '/admin/divisions',
        method: 'POST'
    };

    const assignmentState = {
        isOpen: false,
        open: false,
        mode: 'create',
        id: '',
        division_id: '',
        assignment_title: '',
        assignment_synopsis: '',
        maximum_occupants: 1,
        max_assignees: 1,
        is_recruitment_open: true,
        is_open: true,
        is_visible: true,
        division_title: '',
        division_base_colour: '#374151FF',
        action: '/admin/assignments',
        method: 'POST'
    };

    return {
        // 1. State Properties Upfront (Unified Modals & Search Contract)
        modals: {
            division: divisionState,
            section: divisionState,
            assignment: assignmentState,
            position: assignmentState,
            catalogue: {
                isOpen: false,
                open: false,
                mode: 'create',
                id: '',
                catalogue_title: '',
                catalogue_synopsis: '',
                is_visible: true,
                action: '/admin/divisions/catalogues',
                method: 'POST'
            },
            import: {
                isOpen: false,
                open: false,
                mode: 'create',
                action: '/admin/divisions/import',
                method: 'POST'
            }
        },
        searchQuery: '',
        searchHelperInstance: null,
        filteredIds: null,
        isGeneratingAi: false,
        divisionTomSelect: null,
        aiEndpoint: config.aiEndpoint || '/admin/divisions/generate-description',
        emptyNameMsg: config.emptyNameMsg || 'Please enter a Division Title first.',
        importMode: 'merge',

        // 2. Lifecycle Hook
        init() {
            this.initSearch();
            this.$nextTick(() => {
                this.initTomSelect();
            });
        },

        // 3. Search & Filtering (Fuse.js Integration)
        initSearch() {
            if (window.Search && this.$el && this.$el.querySelector('[data-search-item], [data-division-item]')) {
                try {
                    this.searchHelperInstance = window.Search.create(
                        '[data-search-item], [data-division-item]',
                        ['division_title', 'division_synopsis', 'assignment_title'],
                        { threshold: 0.35 }
                    );
                } catch (e) {
                    this.searchHelperInstance = null;
                }
            }
            this.$watch('searchQuery', (val) => {
                if (!val.trim() || !this.searchHelperInstance) {
                    this.filteredIds = null;
                    return;
                }
                const results = this.searchHelperInstance.search(val.trim());
                this.filteredIds = results ? results.map(id => Number(id)) : [];
            });
        },

        matchesSearch(divTitle, divDesc, assignTitles = []) {
            const q = this.searchQuery.trim().toLowerCase();
            if (!q) return true;
            const haystack = [divTitle || '', divDesc || '', ...(Array.isArray(assignTitles) ? assignTitles : [])];
            return haystack.some(text => String(text).toLowerCase().includes(q));
        },

        isItemVisible(assignTitleOrId, assignDesc = '', divTitle = '') {
            const q = this.searchQuery.trim().toLowerCase();
            if (!q) return true;
            if (typeof assignTitleOrId === 'number' && this.filteredIds !== null) {
                return this.filteredIds.includes(Number(assignTitleOrId));
            }
            if (String(divTitle || '').toLowerCase().includes(q)) return true;
            return [assignTitleOrId || '', assignDesc || ''].some(text =>
                String(text).toLowerCase().includes(q)
            );
        },

        initTomSelect() {
            const selectEl = document.getElementById('edit_assignment_division_id') || document.getElementById('edit_position_division_id');
            if (selectEl && window.Select && !this.divisionTomSelect) {
                this.divisionTomSelect = window.Select.init(selectEl, {
                    create: false,
                    onChange: (val) => {
                        this.modals.assignment.division_id = val;
                    }
                });
            }
        },

        refreshAutosize() {
            this.$nextTick(() => {
                if (window.Autosize) {
                    document.querySelectorAll('textarea[name="division_synopsis"], textarea[name="assignment_synopsis"]').forEach(el => {
                        window.Autosize.update(el);
                    });
                }
            });
        },

        // 4. Modal & Action Handlers (Using window.Modal & window.Fetch)
        openModal(mode = 'create', data = {}, type = 'division') {
            const divisionBaseUrl = '/admin/divisions';
            const assignmentBaseUrl = '/admin/assignments';

            if (type === 'division' || type === 'section') {
                if (mode === 'create') {
                    window.Modal.create(this.modals.division, {
                        division_title: '',
                        division_base_colour: '#374151FF',
                        division_synopsis: '',
                        division_sort_order: 0,
                        is_visible: true,
                        division_catalogue_id: data.division_catalogue_id ?? '',
                        insignia_catalogue_id: data.insignia_catalogue_id ?? ''
                    }, divisionBaseUrl);
                } else {
                    window.Modal.edit(this.modals.division, {
                        id: data.id,
                        division_title: data.division_title ?? data.name ?? '',
                        division_base_colour: data.division_base_colour ?? data.color ?? '#374151FF',
                        division_synopsis: data.division_synopsis ?? data.synopsis ?? '',
                        division_sort_order: data.division_sort_order ?? data.order ?? 0,
                        is_visible: data.is_visible !== undefined ? Boolean(data.is_visible) : true,
                        division_catalogue_id: data.division_catalogue_id ?? '',
                        insignia_catalogue_id: data.insignia_catalogue_id ?? ''
                    }, (id) => `${divisionBaseUrl}/${id}`);
                }
                this.refreshAutosize();
                return;
            }

            if (type === 'assignment' || type === 'position') {
                if (mode === 'create') {
                    window.Modal.create(this.modals.assignment, {
                        division_id: data.division_id ?? '',
                        division_title: data.division_title ?? data.section_name ?? '',
                        division_base_colour: data.division_base_colour ?? data.section_color ?? '#374151FF',
                        assignment_title: '',
                        assignment_synopsis: '',
                        maximum_occupants: 1,
                        max_assignees: 1,
                        is_recruitment_open: true,
                        is_open: true,
                        is_visible: true
                    }, assignmentBaseUrl);
                    this.refreshAutosize();
                } else {
                    const maxOcc = data.maximum_occupants ?? data.max_assignees ?? data.capacity ?? 1;
                    const isOpen = data.is_recruitment_open !== undefined
                        ? Boolean(data.is_recruitment_open)
                        : Boolean(data.is_open ?? true);

                    window.Modal.edit(this.modals.assignment, {
                        id: data.id,
                        division_id: String(data.division_id ?? ''),
                        assignment_title: data.assignment_title ?? data.name ?? '',
                        assignment_synopsis: data.assignment_synopsis ?? data.description ?? '',
                        maximum_occupants: maxOcc,
                        max_assignees: maxOcc,
                        is_recruitment_open: isOpen,
                        is_open: isOpen,
                        is_visible: data.is_visible !== undefined ? Boolean(data.is_visible) : true
                    }, (id) => `${assignmentBaseUrl}/${id}`);

                    this.$nextTick(() => {
                        const selectEl = document.getElementById('edit_assignment_division_id') || document.getElementById('edit_position_division_id');
                        if (selectEl && window.Select) {
                            window.Select.setValue(selectEl, String(data.division_id ?? ''), true);
                        }
                        this.refreshAutosize();
                    });
                }
                return;
            }

            if (type === 'catalogue') {
                const catalogueBaseUrl = `${divisionBaseUrl}/catalogues`;
                if (mode === 'edit') {
                    window.Modal.edit(this.modals.catalogue, data, (id) => `${catalogueBaseUrl}/${id}`);
                } else {
                    window.Modal.create(this.modals.catalogue, {
                        catalogue_title: '',
                        catalogue_synopsis: '',
                        is_visible: true
                    }, catalogueBaseUrl);
                }
                return;
            }

            if (type === 'import') {
                window.Modal.create(this.modals.import, {}, `${divisionBaseUrl}/import`);
            }
        },

        closeModal(target = null) {
            window.Modal.close(this.modals, target);
        },

        async generateAiDescription() {
            const title = this.modals.division.division_title || '';
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
                const { ok, data } = await window.Fetch.post(this.aiEndpoint, {
                    name: title,
                    division_title: title
                }, {
                    loading: false,
                    showError: false
                });

                if (ok && data && (data.division_synopsis || data.description)) {
                    this.modals.division.division_synopsis = data.division_synopsis || data.description;
                    this.refreshAutosize();
                }
            } catch (error) {
                console.error('[DivisionManager] AI description generation failed:', error);
            } finally {
                this.isGeneratingAi = false;
            }
        },

        async handleImportSuccess(response) {
            this.closeModal('import');
            if (window.Swal && typeof window.Swal.toast === 'function') {
                window.Swal.toast('success', response?.message || 'Divisions and assignments imported successfully.');
            }
            await this.refreshDOM();
        },

        // 5. DOM Refresh Handler (sanitize = false)
        async refreshDOM() {
            if (typeof window.updateDOM === 'function') {
                await window.updateDOM(this.$refs, ['sectionsContainer', 'divisionsContainer'], () => {
                    this.closeModal();
                    this.$nextTick(() => {
                        this.initSearch();
                        this.initTomSelect();
                    });
                }, false);
            } else {
                this.closeModal();
            }
        }
    };
}