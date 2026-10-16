export default (initialLayouts = [], availableWidgets = [], widgetRegistry = {}) => ({
    availableLayouts: initialLayouts,
    // Use the new registry if available, fallback to old widget scan if not
    widgetData: Object.keys(widgetRegistry).length > 0 ? widgetRegistry : availableWidgets,
    isSyncing: false,

    async syncWidgets() {
        this.isSyncing = true;

        Swal.fire({
            title: 'Scanning Widgets...',
            html: 'Please wait while the registry is updated.',
            allowOutsideClick: false,
            showConfirmButton: false,
            background: 'var(--theme-bg-panel, #111827)',
            color: 'var(--theme-text-p, #ffffff)',
            didOpen: () => window.Swal.showLoading()
        });

        try {
            const response = await fetch('/admin/widgets/sync', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                    'Accept': 'application/json'
                }
            });
            const data = await response.json();

            if (response.ok && data.status === 'success') {
                this.widgetData = data.data;

                window.Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: data.message,
                    showConfirmButton: false,
                    timer: 3000,
                    background: 'var(--theme-bg-panel, #111827)',
                    color: 'var(--theme-text-p, #ffffff)'
                });
            } else {
                window.Swal.fire('Error', data.message || 'Synchronisation failed.', 'error');
            }
        } catch (e) {
            window.Swal.fire('Error', 'Network error occurred.', 'error');
        } finally {
            this.isSyncing = false;
        }
    },

    modals: {
        page: {
            open: false, mode: 'create', id: '', title: '', slug: '', layout_id: '', is_published: true,
            formAction: '', method: '', widgetBlocks: {}, selectedWidgets: {}, sortOrders: {},
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
        window.updateDOM(this.$refs, ['pagesTableContent', 'layoutsTableContent', 'widgetsTableContent'], (doc) => {
            const freshDataEl = doc.getElementById('pb-fresh-data');
            if (freshDataEl) {
                this.availableLayouts = JSON.parse(freshDataEl.getAttribute('data-layouts') || '[]');
                this.widgetData = JSON.parse(freshDataEl.getAttribute('data-widgets') || '{}');
            }
            this.modals.page.open = false;
            this.modals.layout.open = false;
            this.modals.widget.open = false;
        });
    },

    async refreshDOM_US() {
        window.updateDOM_US(this.$refs, ['pagesTableContent', 'layoutsTableContent', 'widgetsTableContent'], (doc) => {
            const freshDataEl = doc.getElementById('pb-fresh-data');
            if (freshDataEl) {
                this.availableLayouts = JSON.parse(freshDataEl.getAttribute('data-layouts') || '[]');
                this.widgetData = JSON.parse(freshDataEl.getAttribute('data-widgets') || '{}');
            }
            this.modals.page.open = false;
            this.modals.layout.open = false;
            this.modals.widget.open = false;
        });
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
        this.modals.page.sortOrders = {};
        this.modals.page.requiredPermissions = {};

        if (data.widgets) {
            data.widgets.forEach(w => {
                this.modals.page.selectedWidgets[w.id] = true;
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
        this.modals.widget.options = (data.options && typeof data.options === 'object') ? data.options : {};
        this.modals.widget.action = mode === 'edit' ? `/admin/widgets/${data.id}` : '/admin/widgets';
        this.modals.widget.method = mode === 'edit' ? 'PUT' : 'POST';

        if (mode === 'create') {
            this.initWidgetOptions();
        }

        this.modals.widget.open = true;
    }
});