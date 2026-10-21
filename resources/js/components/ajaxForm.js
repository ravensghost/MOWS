export default () => ({
    submitting: false,

    async submit(e) {
        const form = e.target;
        this.submitting = true;

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
            const formData = new FormData(form);
            const rawAttrMethod = (form.getAttribute('method') || 'POST').toUpperCase();
            if (rawAttrMethod !== 'POST' && rawAttrMethod !== 'GET' && !formData.has('_method')) {
                formData.append('_method', rawAttrMethod);
            }

            const response = await fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
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
                    this.$dispatch('ajax-success', data);

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
        } finally {
            this.submitting = false;
        }
    }
});