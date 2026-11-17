// resources/js/managers/loaHubManager.js

export default function loaHubManager() {
    return {
        // 1. State Properties Upfront (Unified Modals & Search Contract)
        modals: {
            loa: {
                isOpen: false,
                open: false,
                mode: 'edit',
                id: null,
                userId: null,
                userName: '',
                is_on_loa: '1',
                loa_return_date: '',
                reason: '',
                action: '',
                method: 'PUT'
            }
        },
        searchQuery: '',
        searchHelperInstance: null,
        filteredIds: null,
        isLoading: false,

        // 2. Lifecycle Hook
        init() {
            this.initSearch();
        },

        // 3. Search & Filtering (Fuse.js Integration)
        initSearch() {
            if (window.Search && this.$el && this.$el.querySelector('[data-search-item], [data-loa-user-item]')) {
                try {
                    this.searchHelperInstance = window.Search.create(
                        '[data-search-item], [data-loa-user-item]',
                        ['username', 'name', 'reason'],
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

        isItemVisible(id) {
            if (!this.searchQuery.trim() || this.filteredIds === null) return true;
            return this.filteredIds.includes(Number(id));
        },

        // 4. Modal & Action Handlers (Using window.Modal & window.Fetch)
        openLoaModal(userId, userName, isOnLoa = true, returnDate = '') {
            const baseUrl = `/admin/users/${userId}/loa`;
            window.Modal.edit(this.modals.loa, {
                id: userId,
                userId: userId,
                userName: userName,
                is_on_loa: isOnLoa ? '1' : '0',
                loa_return_date: returnDate || '',
                reason: ''
            }, () => baseUrl);
        },

        closeLoaModal() {
            window.Modal.close(this.modals.loa);
        },

        async quickClearLoa(userId, userName) {
            const result = await window.Swal.confirm(
                `Return ${userName} to Active Duty?`,
                'This will clear their Leave of Absence flag and restore their characters to Active Duty.',
                'Yes, Return to Duty'
            );

            if (!result.isConfirmed) return;

            try {
                const { ok, data } = await window.Fetch.put(`/admin/users/${userId}/loa`, {
                    is_on_loa: 0,
                    loa_return_date: null,
                    reason: 'Returned to Active Duty via Command Centre Hub'
                }, {
                    loading: false,
                    showError: true
                });

                if (ok) {
                    if (window.Swal && typeof window.Swal.toast === 'function') {
                        window.Swal.toast('success', data?.message || `${userName} returned to Active Duty.`);
                    }
                    await this.refreshDOM();
                } else {
                    window.Swal.error('Error', data?.message || 'Failed to update LOA status.');
                }
            } catch (error) {
                console.error('[LoaHubManager] Failed to clear LOA:', error);
            }
        },

        async sendPlayerPing(userId, userName, context = 'inactivity') {
            if (!window.Swal || !window.Swal.fire) return;

            const swalConfig = typeof window.Swal.themeConfig === 'function' ? window.Swal.themeConfig() : {};
            const { value: note, isConfirmed } = await window.Swal.fire({
                title: `Ping ${userName}`,
                input: 'text',
                inputLabel: 'Optional custom note to include in the check-in alert:',
                inputPlaceholder: 'e.g. Hope everything is well! Let us know when you are back.',
                showCancelButton: true,
                confirmButtonText: 'Send Check-In Ping',
                cancelButtonText: 'Cancel',
                ...swalConfig
            });

            if (!isConfirmed) return;

            try {
                const { ok, data } = await window.Fetch.post(`/admin/users/${userId}/ping`, {
                    context: context,
                    note: note || ''
                }, {
                    loading: false,
                    showError: true
                });

                if (ok) {
                    if (typeof window.Swal.toast === 'function') {
                        window.Swal.toast('success', data?.message || `Check-in alert sent to ${userName}.`);
                    }
                } else {
                    window.Swal.error('Error', data?.message || 'Failed to send player ping.');
                }
            } catch (error) {
                console.error('[LoaHubManager] Failed to send player ping:', error);
            }
        },

        // 5. DOM Refresh Handler (sanitize = false)
        async refreshDOM() {
            if (typeof window.updateDOM === 'function') {
                await window.updateDOM(this.$refs, ['loaHubTableContent', 'table-wrapper'], () => {
                    this.closeLoaModal();
                    this.$nextTick(() => {
                        this.initSearch();
                    });
                }, false);
            } else {
                this.closeLoaModal();
            }
        }
    };
}