// resources/js/components/folioEdit.js

export default function folioEdit() {
    return {
        modals: {
            folio: {
                isOpen: false,
                open: false,
                mode: 'create',
                id: null,
                action: '/folios',
                method: 'POST'
            }
        },
        isOpen: false,
        folioId: null,

        init() {
            window.addEventListener('open-folio-modal', (event) => {
                const folioId = event.detail?.folioId || event.detail?.id || null;
                this.isOpen = true;
                if (window.Modal) {
                    window.Modal.open(
                        this.modals.folio,
                        folioId ? 'edit' : 'create',
                        { id: folioId },
                        { baseUrl: '/folios' }
                    );
                }
                this.loadFolio(folioId);
            });

            window.addEventListener('close-folio-modal', () => {
                this.closeModal();
            });
        },

        closeModal() {
            this.isOpen = false;
            this.folioId = null;
            if (window.Modal) {
                window.Modal.close(this.modals.folio);
            }
        },

        async loadFolio(id) {
            this.folioId = id;
            const form = this.$el.querySelector('form');
            if (!form) return;

            const charSelect = form.querySelector('[name="character_ids[]"]');

            if (!id) {
                form.reset();
                const idInput = form.querySelector('input[name="folio_id"]');
                if (idInput) idInput.remove();

                if (charSelect && window.Select) {
                    window.Select.clear(charSelect, true);
                }

                window.dispatchEvent(new CustomEvent('set-editor-content', { detail: { content: '' } }));
                return;
            }

            try {
                const { ok, data: responsePayload } = await window.Fetch.get(`/folios/${id}/edit`, {
                    loading: false,
                    showError: true
                });

                const folio = responsePayload?.data ?? responsePayload;

                if (ok && folio) {
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

                    if (charSelect && window.Select) {
                        window.Select.clear(charSelect, true);
                        if (Array.isArray(folio.character_ids)) {
                            window.Select.setValue(charSelect, folio.character_ids, true);
                        }
                    }

                    let idInput = form.querySelector('input[name="folio_id"]');
                    if (!idInput) {
                        idInput = document.createElement('input');
                        idInput.type = 'hidden';
                        idInput.name = 'folio_id';
                        form.appendChild(idInput);
                    }
                    idInput.value = folio.id;

                    window.dispatchEvent(new CustomEvent('set-editor-content', {
                        detail: { content: folio.folio_text || '' }
                    }));
                }
            } catch (err) {
                console.error('[FolioEdit] Failed to load folio data:', err);
            }
        }
    };
}