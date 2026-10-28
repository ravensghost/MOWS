// resources/js/managers/loaHubManager.js
export default () => ({
    loaModal: {
        open: false,
        userId: null,
        userName: '',
        is_on_loa: '1',
        loa_return_date: '',
        reason: '',
    },

    openLoaModal(userId, userName, isOnLoa = true, returnDate = '') {
        this.loaModal.userId = userId;
        this.loaModal.userName = userName;
        this.loaModal.is_on_loa = isOnLoa ? '1' : '0';
        this.loaModal.loa_return_date = returnDate || '';
        this.loaModal.reason = '';
        this.loaModal.open = true;
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
                reason: 'Returned to Active Duty via Command Centre Hub',
            }, {
                loading: false,
                showError: true
            });

            if (ok) {
                if (window.Swal && typeof window.Swal.fire === 'function') {
                    window.Swal.fire({
                        icon: 'success',
                        title: 'Updated',
                        text: data.message || `${userName} returned to Active Duty.`,
                        timer: 2000,
                        showConfirmButton: false,
                        ...window.Swal.themeConfig()
                    });
                }

                // Updated to use refreshDOM with sanitize = false (replacing old _US helper)
                if (typeof window.refreshDOM === 'function') {
                    await window.refreshDOM('.table-wrapper', null, false);
                } else {
                    window.location.reload();
                }
            } else {
                window.Swal.error('Error', data.message || 'Failed to update LOA status.');
            }
        } catch (error) {
            console.error(error);
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
                note: note || '',
            }, {
                loading: false,
                showError: true
            });

            if (ok) {
                window.Swal.fire({
                    icon: 'success',
                    title: 'Ping Dispatched',
                    text: data.message || `Check-in alert sent to ${userName}.`,
                    timer: 2500,
                    showConfirmButton: false,
                    ...window.Swal.themeConfig()
                });
            } else {
                window.Swal.error('Error', data.message || 'Failed to send player ping.');
            }
        } catch (error) {
            console.error(error);
        }
    }
});