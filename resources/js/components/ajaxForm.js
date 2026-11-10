// resources/js/components/ajaxForm.js
export default () => ({
    submitting: false,

    async submit(e) {
        const form = e.target;
        this.submitting = true;

        const loadingTitle = form.dataset.loadingTitle;
        const loadingMsg = form.dataset.loadingMsg || 'Please wait...';
        const method = form.getAttribute('method') || 'POST';
        const formData = new FormData(form);

        try {
            const { ok, data } = await window.Fetch.request(form.action, method, formData, {
                loading: Boolean(loadingTitle),
                loadingTitle: loadingTitle,
                loadingHtml: loadingMsg
            });

            if (ok) {
                const successTitle = document.body.dataset.swalSuccess || 'Success!';

                window.Swal.fire({
                    icon: 'success',
                    title: successTitle,
                    text: data.message || '',
                    showConfirmButton: false,
                    timer: 1000,
                    ...window.Swal.themeConfig()
                }).then(() => {
                    this.$dispatch('ajax-success', data);

                    if (data.redirect) {
                        window.location.href = data.redirect;
                    } else if (form.action.includes('/admin/settings')) {
                        if (typeof window.refreshDOM === 'function') {
                            window.refreshDOM('body', () => {
                                if (window.Alpine) {
                                    window.Alpine.initTree(document.body);
                                }
                            }, false);
                        } else {
                            window.location.reload();
                        }
                    }
                });
            } else {
                let errorMsg = 'Validation failed.';
                if (data.message) {
                    errorMsg = data.message;
                } else if (data.errors) {
                    errorMsg = Object.values(data.errors)[0][0];
                }
                window.Swal.error('Error', errorMsg);
            }
        } catch (error) {
            // Network errors handled by window.Fetch
        } finally {
            this.submitting = false;
        }
    }
});