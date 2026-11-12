// resources/js/components/folioEdit.js
export default () => ({
    isOpen: false,
    folioId: null,

    init() {
        // Listen for open events dispatched from the story table or draft widget
        window.addEventListener('open-folio-modal', (event) => {
            this.isOpen = true;
            this.loadFolio(event.detail?.folioId || null);
        });

        window.addEventListener('close-folio-modal', () => {
            this.isOpen = false;
            this.folioId = null;
        });
    },

    async loadFolio(id) {
        this.folioId = id;
        const form = this.$el.querySelector('form');
        if (!form) return;

        const charSelect = form.querySelector('[name="character_ids[]"]');

        if (!id) {
            // Reset form for a brand new folio
            form.reset();
            const idInput = form.querySelector('input[name="folio_id"]');
            if (idInput) idInput.remove();

            if (charSelect) {
                window.Select.clear(charSelect, true);
            }

            window.dispatchEvent(new CustomEvent('set-folio-editor-content', { detail: { content: '' } }));
            return;
        }

        try {
            // Leverage global window.Fetch helper
            const { ok, data: folio } = await window.Fetch.get(`/folios/${id}/edit`, {
                loading: false,
                showError: true
            });

            if (ok && folio) {
                // Populate standard form inputs
                const setVal = (name, val) => {
                    const el = form.querySelector(`[name="${name}"]`);
                    if (el) el.value = val ?? '';
                };

                setVal('volume_id', folio.volume_id);
                setVal('active_persona_id', folio.active_persona_id);
                setVal('folio_mode', folio.folio_mode || 'mission');
                setVal('ic_day', folio.ic_day);
                setVal('ic_time', folio.ic_time);
                setVal('scene_location', folio.scene_location);
                setVal('content_tier_l', folio.content_tier_l);
                setVal('content_tier_s', folio.content_tier_s);
                setVal('content_tier_v', folio.content_tier_v);
                setVal('folio_status', folio.folio_status || 'draft');

                // Populate Tom Select for Personae Present using window.Select helper
                if (charSelect) {
                    window.Select.clear(charSelect, true);
                    if (Array.isArray(folio.character_ids)) {
                        window.Select.setValue(charSelect, folio.character_ids, true);
                    }
                }

                // Set hidden folio_id input for updates/autosaves
                let idInput = form.querySelector('input[name="folio_id"]');
                if (!idInput) {
                    idInput = document.createElement('input');
                    idInput.type = 'hidden';
                    idInput.name = 'folio_id';
                    form.appendChild(idInput);
                }
                idInput.value = folio.id;

                // Dispatch event to populate TipTap reliably
                window.dispatchEvent(new CustomEvent('set-folio-editor-content', {
                    detail: { content: folio.folio_text || '' }
                }));
            }
        } catch (err) {
            console.error('Failed to load folio data:', err);
        }
    }
});