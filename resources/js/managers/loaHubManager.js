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
        const result = await window.Swal.fire({
            title: `Return ${userName} to Active Duty?`,
            text: 'This will clear their Leave of Absence flag and restore their characters to Active Duty.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, Return to Duty',
            cancelButtonText: 'Cancel',
        });

        if (!result.isConfirmed) return;

        try {
            const response = await fetch(`/admin/users/${userId}/loa`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                },
                body: JSON.stringify({
                    is_on_loa: 0,
                    loa_return_date: null,
                    reason: 'Returned to Active Duty via Command Centre Hub',
                }),
            });

            const data = await response.json();

            if (response.ok) {
                window.Swal.fire({
                    icon: 'success',
                    title: 'Updated',
                    text: data.message || `${userName} returned to Active Duty.`,
                    timer: 2000,
                    showConfirmButton: false,
                });
                window.refreshDOM_US();
            } else {
                window.Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message || 'Failed to update LOA status.',
                });
            }
        } catch (error) {
            console.error(error);
            window.Swal.fire({
                icon: 'error',
                title: 'Network Error',
                text: 'Could not reach the server.',
            });
        }
    },

    async sendPlayerPing(userId, userName, context = 'inactivity') {
        const { value: note, isConfirmed } = await window.Swal.fire({
            title: `Ping ${userName}`,
            input: 'text',
            inputLabel: 'Optional custom note to include in the check-in alert:',
            inputPlaceholder: 'e.g. Hope everything is well! Let us know when you are back.',
            showCancelButton: true,
            confirmButtonText: 'Send Check-In Ping',
            cancelButtonText: 'Cancel',
        });

        if (!isConfirmed) return;

        try {
            const response = await fetch(`/admin/users/${userId}/ping`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                },
                body: JSON.stringify({
                    context: context,
                    note: note || '',
                }),
            });

            const data = await response.json();

            if (response.ok) {
                window.Swal.fire({
                    icon: 'success',
                    title: 'Ping Dispatched',
                    text: data.message || `Check-in alert sent to ${userName}.`,
                    timer: 2500,
                    showConfirmButton: false,
                });
            } else {
                window.Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message || 'Failed to send player ping.',
                });
            }
        } catch (error) {
            console.error(error);
            window.Swal.fire({
                icon: 'error',
                title: 'Network Error',
                text: 'Could not reach the server.',
            });
        }
    }
});