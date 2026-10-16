export default () => ({
    async submit(e) {
        const form = e.target;

        const loadingTitle = form.dataset.loadingTitle;
        if (loadingTitle) {
            Swal.fire({
                title: loadingTitle,
                html: form.dataset.loadingMsg || 'Please wait...',
                allowOutsideClick: false,
                showConfirmButton: false,
                background: 'var(--theme-panel-bg, #111827)',
                color: 'var(--theme-text-main, #ffffff)',
                didOpen: () => Swal.showLoading()
            });
        }

        try {
            const response = await fetch(form.action, {
                method: form.method || 'POST',
                body: new FormData(form),
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            });

            if (response.ok) {
                const data = await response.json();

                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: data.message || '',
                    background: 'var(--theme-panel-bg, #111827)',
                    color: 'var(--theme-text-main, #ffffff)',
                    showConfirmButton: false,
                    timer: 1000
                }).then(() => {
                    this.$dispatch('ajax-success');

                    if (data.redirect) {
                        window.location.href = data.redirect;
                    }
                });
            } else {
                let errorMsg = 'Validation failed.';
                try {
                    const data = await response.json();
                    errorMsg = data.message || (data.errors ? Object.values(data.errors)[0][0] : errorMsg);
                } catch (err) { }
                Swal.fire('Error', errorMsg, 'error');
            }
        } catch (error) {
            Swal.fire('Error', 'A network error occurred.', 'error');
        }
    }
});
