// resources/js/managers/bookBuilderManager.js
export default (initialLayouts = [], availableWidgets = [], widgetRegistry = {}) => ({
    availableLayouts: initialLayouts,
    widgetData: Object.keys(widgetRegistry).length > 0 ? widgetRegistry : availableWidgets,
    isSyncing: false,

    async syncWidgets() {
        this.isSyncing = true;

        if (window.Swal && typeof window.Swal.loading === 'function') {
            window.Swal.loading('Scanning Widgets...', 'Please wait while the registry is updated.');
        }

        try {
            const { ok, data } = await window.Fetch.post('/admin/widgets/sync', {}, {
                loading: false,
                showError: false
            });

            if (ok && data && data.status === 'success') {
                this.widgetData = data.data;

                if (window.Swal && typeof window.Swal.toast === 'function') {
                    window.Swal.toast('success', data.message || 'Widgets synchronized successfully.');
                }
            } else {
                window.Swal.error('Error', data?.message || 'Synchronisation failed.');
            }
        } catch (e) {
            window.Swal.error('Error', 'Network error occurred.');
        } finally {
            this.isSyncing = false;
        }
    },

    modals: {
        page: {
            open: false, mode: 'create', id: '', title: '', slug: '', layout_id: '', is_published: true,
            formAction: '', method: '', widgetBlocks: {}, selectedWidgets: {}, selectedVisible: {}, sortOrders: {},
            requiredPermissions: {},
            instanceOptions: {},
            configuringWidgetId: null
        },
        layout: {
            open: false, mode: 'create', id: '', name: '', description: '', blocks: [],
            formAction: '', method: ''
        },
        widget: {
            open: false, mode: 'create', id: '', name: '', widgetKey: '', options: '', dynamicValues: [],
            action: '/admin/widgets', method: 'POST'
        }
    },

    async refreshDOM() {
        this.modals.page.open = false;
        this.modals.layout.open = false;
        this.modals.widget.open = false;

        // Updated to use window.updateDOM with sanitize = false (replacing old _US helper)
        if (typeof window.updateDOM === 'function') {
            await window.updateDOM(this.$refs, ['pagesTableContent', 'layoutsTableContent', 'widgetsTableContent'], (doc) => {
                const freshDataEl = doc?.getElementById('pb-fresh-data');
                if (freshDataEl) {
                    this.availableLayouts = JSON.parse(freshDataEl.getAttribute('data-layouts') || '[]');
                    this.widgetData = JSON.parse(freshDataEl.getAttribute('data-widgets') || '{}');
                }
            }, false);
        }
    },

    async refreshDOM_US() {
        return this.refreshDOM();
    },

    // --- Page Logic ---
    openPageModal(mode, data = {}) {
        this.modals.page.mode = mode;
        this.modals.page.id = data.id || '';
        this.modals.page.title = data.title || '';
        this.modals.page.slug = data.slug || '';
        this.modals.page.layout_id = data.layout_id || '';
        this.modals.page.is_published = mode === 'edit' ? (data.is_published || false) : true;
        this.modals.page.formAction = mode === 'edit' ? `/admin/pages/${data.id}` : '/admin/pages';
        this.modals.page.method = mode === 'edit' ? 'PUT' : 'POST';

        this.modals.page.widgetBlocks = {};
        this.modals.page.selectedWidgets = {};
        this.modals.page.selectedVisible = {};
        this.modals.page.sortOrders = {};
        this.modals.page.requiredPermissions = {};
        this.modals.page.instanceOptions = {};

        if (data.widgets) {
            data.widgets.forEach(w => {
                this.modals.page.selectedWidgets[w.id] = true;
                this.modals.page.selectedVisible[w.id] = w.is_visible !== undefined ? Boolean(w.is_visible) : true;
                this.modals.page.sortOrders[w.id] = w.sort_order;
                this.modals.page.widgetBlocks[w.id] = w.layout_block_id;
                this.modals.page.requiredPermissions[w.id] = w.required_permission || '';

                let opts = w.instance_options || {};
                if (typeof opts === 'string') {
                    try { opts = JSON.parse(opts); } catch (e) { opts = {}; }
                }
                if (Array.isArray(opts) || typeof opts !== 'object') {
                    opts = {};
                }
                this.modals.page.instanceOptions[w.id] = opts;
            });
        }

        this.modals.page.open = true;
    },

    getActiveBlocks() {
        const layoutId = parseInt(this.modals.page.layout_id, 10);
        if (!layoutId) return [];
        const layout = this.availableLayouts.find(l => l.id === layoutId);
        return layout && layout.blocks ? layout.blocks : [];
    },

    // --- Layout Logic ---
    openLayoutModal(mode, data = {}) {
        this.modals.layout.mode = mode;
        this.modals.layout.id = data.id || '';
        this.modals.layout.name = data.name || '';
        this.modals.layout.description = data.description || '';
        this.modals.layout.blocks = data.blocks ? JSON.parse(JSON.stringify(data.blocks)) : [{ id: '', name: 'content', col_span: 12, row_span: 1 }];
        this.modals.layout.formAction = mode === 'edit' ? `/admin/layouts/${data.id}` : '/admin/layouts';
        this.modals.layout.method = mode === 'edit' ? 'PUT' : 'POST';
        this.modals.layout.open = true;
    },

    addLayoutBlock() {
        this.modals.layout.blocks.push({ id: '', name: '', col_span: 12, row_span: 1 });
    },

    removeLayoutBlock(index) {
        this.modals.layout.blocks.splice(index, 1);
    },

    openConfigModal(widgetId, widgetKey) {
        this.modals.page.configuringWidgetId = widgetId;
        this.modals.widget.widgetKey = widgetKey;

        if (!this.modals.page.instanceOptions[widgetId]) {
            this.modals.page.instanceOptions[widgetId] = {};
        }

        const fields = this.currentWidgetFields;
        for (let key in fields) {
            if (this.modals.page.instanceOptions[widgetId][key] === undefined) {
                this.modals.page.instanceOptions[widgetId][key] = fields[key].default || '';
            }
        }

        if (this.modals.widget.options && this.modals.widget.options[widgetId]) {
            this.modals.page.instanceOptions[widgetId] = {
                ...this.modals.page.instanceOptions[widgetId],
                ...this.modals.widget.options[widgetId]
            };
        }

        this.modals.widget.open = true;
    },

    // --- Widget Logic ---
    get currentWidgetFields() {
        if (!this.modals.widget.widgetKey || !this.widgetData) return {};
        let data = this.widgetData[this.modals.widget.widgetKey];
        if (!data) {
            const searchKey = this.modals.widget.widgetKey.toLowerCase().replace('widget_', '');
            const foundKey = Object.keys(this.widgetData).find(k =>
                k.toLowerCase() === this.modals.widget.widgetKey.toLowerCase() ||
                k.toLowerCase().replace('widget_', '') === searchKey
            );
            if (foundKey) data = this.widgetData[foundKey];
        }
        return data && data.fields ? data.fields : {};
    },

    initWidgetOptions() {
        const fields = this.currentWidgetFields;
        this.modals.widget.options = {};
        for (let key in fields) {
            this.modals.widget.options[key] = fields[key].default || '';
        }
    },

    openWidgetModal(mode, data = {}) {
        this.modals.widget.mode = mode;
        this.modals.widget.id = data.id || '';
        this.modals.widget.name = data.name || '';
        this.modals.widget.widgetKey = data.widgetKey || '';

        let rawOpts = data.options || '';
        if (typeof rawOpts === 'object') {
            try { rawOpts = JSON.stringify(rawOpts, null, 2); } catch (e) { rawOpts = ''; }
        }
        this.modals.widget.options = rawOpts;

        this.modals.widget.action = mode === 'edit' ? `/admin/widgets/${data.id}` : '/admin/widgets';
        this.modals.widget.method = mode === 'edit' ? 'PUT' : 'POST';

        if (mode === 'create') {
            this.initWidgetOptions();
        }

        this.modals.widget.open = true;
    }
});