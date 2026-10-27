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
            // Leverage global window.Fetch helper which handles loading states, 
            // CSRF headers, method spoofing, and network error alerts automatically.
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
                        // Refresh settings page view content dynamically
                        fetch(window.location.href, {
                            headers: { 'X-Requested-With': 'XMLHttpRequest' }
                        })
                            .then(res => res.text())
                            .then(html => {
                                const doc = new DOMParser().parseFromString(html, 'text/html');
                                document.documentElement.setAttribute('lang', doc.documentElement.getAttribute('lang') || 'gb');
                                document.body.innerHTML = doc.body.innerHTML;

                                if (window.Alpine) {
                                    window.Alpine.initTree(document.body);
                                }
                            })
                            .catch(() => {
                                window.location.reload();
                            });
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
            // Network errors are already handled by window.Fetch, 
            // but we ensure submitting state resets safely here.
        } finally {
            this.submitting = false;
        }
    }
});