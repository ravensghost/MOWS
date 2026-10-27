// resources/js/utility/fetchHelper.js

/**
 * Global Fetch / AJAX Utility
 * Standardises CSRF tokens, JSON/FormData payloads, method spoofing, and SweetAlert2 states.
 */
window.Fetch = {
    csrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    },
    async request(url, method = 'GET', payload = null, options = {}) {
        const {
            loading = false,
            loadingTitle = 'Please wait...',
            loadingHtml = 'Processing request...',
            showError = true
        } = options;

        if (loading && window.Swal) {
            window.Swal.loading(loadingTitle, loadingHtml);
        }

        const isFormData = payload instanceof FormData;
        const headers = {
            'X-CSRF-TOKEN': this.csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            ...(options.headers || {})
        };

        let body = null;
        let actualMethod = method.toUpperCase();

        if (payload && actualMethod !== 'GET') {
            if (isFormData) {
                if (actualMethod !== 'POST') {
                    payload.append('_method', actualMethod);
                    actualMethod = 'POST';
                }
                body = payload;
            } else {
                headers['Content-Type'] = 'application/json';
                const dataObj = { ...payload };
                if (actualMethod !== 'POST') {
                    dataObj._method = actualMethod;
                    actualMethod = 'POST';
                }
                body = JSON.stringify(dataObj);
            }
        }

        try {
            const response = await fetch(url, {
                method: actualMethod,
                headers,
                body
            });

            const data = await response.json().catch(() => ({}));

            if (window.Swal && loading) {
                window.Swal.close();
            }

            return { ok: response.ok, status: response.status, data };
        } catch (error) {
            if (window.Swal && loading) {
                window.Swal.close();
            }
            if (showError && window.Swal) {
                window.Swal.error('Error', 'An unexpected network error occurred.');
            }
            throw error;
        }
    },
    get(url, options = {}) {
        return this.request(url, 'GET', null, options);
    },
    post(url, payload = {}, options = {}) {
        return this.request(url, 'POST', payload, options);
    },
    put(url, payload = {}, options = {}) {
        return this.request(url, 'PUT', payload, options);
    },
    patch(url, payload = {}, options = {}) {
        return this.request(url, 'PATCH', payload, options);
    },
    delete(url, payload = {}, options = {}) {
        return this.request(url, 'DELETE', payload, options);
    }
};