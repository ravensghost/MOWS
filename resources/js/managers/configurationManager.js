// resources/js/managers/configurationManager.js

export default function configurationManager(
    initialAiMode = 'cloud',
    initialMailMailer = 'smtp'
) {
    return {
        // 1. State Properties Upfront (Unified Modals & Search Contract)
        modals: {
            config: {
                isOpen: false,
                open: false,
                mode: 'edit',
                id: '',
                configuration_token: '',
                configuration_payload: '',
                label: '',
                hint: '',
                action: '/admin/configurations/update',
                method: 'POST'
            },
            engine: {
                isOpen: false,
                open: false,
                mode: 'create',
                id: '',
                engine_title: '',
                calculation_algorithm: 'stardate',
                date_prefix: '',
                date_suffix: '',
                base_earth_year: 2026,
                base_earth_modifier: 0,
                base_system_value: 0,
                temporal_multiplier: 1,
                is_active: false,
                action: '/admin/chronological-engines',
                method: 'POST'
            }
        },
        ai_mode: initialAiMode,
        mail_mailer: initialMailMailer,
        searchQuery: '',
        searchHelperInstance: null,
        filteredIds: null,
        isLoading: false,

        // 2. Lifecycle Hook
        init() {
            this.initSearch();
            this.$watch('searchQuery', (val) => {
                if (!val.trim() || !this.searchHelperInstance) {
                    this.filteredIds = null;
                    return;
                }
                const results = this.searchHelperInstance.search(val.trim());
                this.filteredIds = results ? results.map(res => String(res.id ?? res)) : [];
            });
        },

        // 3. Search & Filtering (Fuse.js Integration)
        initSearch() {
            if (window.Search && this.$el && this.$el.querySelector('[data-search-item], [data-config-item]')) {
                try {
                    this.searchHelperInstance = window.Search.create(
                        '[data-search-item], [data-config-item]',
                        ['key', 'token', 'configuration_token', 'label'],
                        { threshold: 0.35 }
                    );
                } catch (e) {
                    this.searchHelperInstance = null;
                }
            }
        },

        isItemVisible(idOrToken) {
            const q = this.searchQuery.trim().toLowerCase();
            if (!q) return true;
            if (this.filteredIds !== null) {
                return this.filteredIds.includes(String(idOrToken));
            }
            return String(idOrToken || '').toLowerCase().includes(q);
        },

        // 4. Modal Handlers (Using window.Modal utility)
        openModal(mode = 'edit', data = {}, target = 'config') {
            if (target === 'engine') {
                const baseUrl = '/admin/chronological-engines';
                if (mode === 'edit') {
                    window.Modal.edit(this.modals.engine, {
                        ...data,
                        is_active: Boolean(data.is_active)
                    }, (id) => `${baseUrl}/${id}`);
                } else {
                    window.Modal.create(this.modals.engine, {
                        engine_title: '',
                        calculation_algorithm: 'stardate',
                        date_prefix: '',
                        date_suffix: '',
                        base_earth_year: 2026,
                        base_earth_modifier: 0,
                        base_system_value: 0,
                        temporal_multiplier: 1,
                        is_active: false
                    }, baseUrl);
                }
                return;
            }

            window.Modal.edit(this.modals.config, {
                id: data.id ?? data.configuration_token ?? '',
                configuration_token: data.configuration_token ?? data.token ?? '',
                configuration_payload: data.configuration_payload ?? data.value ?? '',
                label: data.label ?? '',
                hint: data.hint ?? ''
            }, () => '/admin/configurations/update');
            this.modals.config.method = 'POST';
        },

        closeModal(target = null) {
            window.Modal.close(this.modals, target);
        },

        // 5. DOM Refresh Handler (sanitize = false)
        async refreshDOM() {
            if (typeof window.updateDOM === 'function') {
                await window.updateDOM(this.$refs, ['configurationsWrapper', 'enginesTableContent'], () => {
                    this.closeModal();
                    this.$nextTick(() => {
                        this.initSearch();
                    });
                }, false);
            } else {
                this.closeModal();
            }
        }
    };
}