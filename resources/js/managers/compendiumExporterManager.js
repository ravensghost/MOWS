// resources/js/components/compendiumExporterManager.js
export default function compendiumExporterManager(initialPreset = 'blank', initialSkin = '') {
    return {
        // 1. State Properties Upfront
        preset: initialPreset || 'blank',
        skin_directory: initialSkin || '',
        isBuilding: false,

        // 2. Lifecycle & Data Initialisation
        initData() {
            this.isBuilding = false;
        },

        // 3. Computed / Derived Helpers
        getManifestUrl(domain) {
            return `/admin/compendium/manifest/${encodeURIComponent(domain)}`;
        },

        // 4. Async Actions & Server Mutations
        startBuild() {
            this.isBuilding = true;
            window.Swal?.toast?.('info', 'Compiling starter-pack archive...');
            setTimeout(() => {
                this.isBuilding = false;
            }, 3000);
        },

        // 5. Event Listeners & Cleanup Hooks
        refreshDOM() {
            this.isBuilding = false;
        },
    };
}