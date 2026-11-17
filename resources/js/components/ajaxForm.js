// resources/js/components/ajaxForm.js

export default function ajaxForm() {
    return {
        submitting: false,

        async submit(e) {
            const form = e?.target || this.$el;
            if (!form || this.submitting) return;

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

                    if (window.Swal && typeof window.Swal.fire === 'function') {
                        await window.Swal.fire({
                            icon: 'success',
                            title: successTitle,
                            text: data?.message || '',
                            showConfirmButton: false,
                            timer: 1000,
                            ...window.Swal.themeConfig()
                        });
                    }

                    this.$dispatch('ajax-success', data);

                    if (data?.redirect) {
                        window.location.href = data.redirect;
                    } else if (form.action.includes('/admin/configurations')) {
                        if (typeof window.updateDOM === 'function' && this.$refs) {
                            await window.updateDOM(this.$refs, ['configurationsWrapper'], null, false);
                        }
                    }
                } else {
                    let errorMsg = 'Validation failed.';
                    if (data?.message) {
                        errorMsg = data.message;
                    } else if (data?.errors) {
                        const firstErrorGroup = Object.values(data.errors)[0];
                        if (Array.isArray(firstErrorGroup) && firstErrorGroup.length > 0) {
                            errorMsg = firstErrorGroup[0];
                        }
                    }
                    if (window.Swal && typeof window.Swal.error === 'function') {
                        window.Swal.error('Error', errorMsg);
                    }
                }
            } catch (error) {
                // Network errors are caught and displayed by window.Fetch
            } finally {
                this.submitting = false;
            }
        }
    };
}