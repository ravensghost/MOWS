// resources/js/components/setupWizardManager.js
export default function setupWizardManager(availableSkins = [], initialSkin = 'default') {
    return {
        // 1. State Properties Upfront
        step: 1,
        isSubmitting: false,
        allRequirementsPassed: true,
        availableSkins: Array.isArray(availableSkins) && availableSkins.length > 0 ? availableSkins : ['default'],
        form: {
            app_name: 'MOWS Engine',
            app_url: window.location.origin || 'http://localhost',
            db_host: '127.0.0.1',
            db_port: '3306',
            db_database: '',
            db_username: '',
            db_password: '',
            skin_directory: initialSkin || 'default',
            admin_name: '',
            admin_email: '',
            password: '',
            password_confirmation: '',
        },

        // 2. Lifecycle & Data Initialisation
        initData() {
            const disabledButtons = this.$el?.querySelectorAll('[disabled]') || [];
            this.allRequirementsPassed = disabledButtons.length === 0;
        },

        // 3. Computed / Derived Helpers
        nextStep(targetStep) {
            this.step = targetStep;
        },

        // 4. Async Actions & Server Mutations
        async submitDatabase() {
            if (this.isSubmitting) {
                return;
            }

            this.isSubmitting = true;

            try {
                const payload = {
                    app_name: this.form.app_name,
                    app_url: this.form.app_url,
                    db_host: this.form.db_host,
                    db_port: this.form.db_port,
                    db_database: this.form.db_database,
                    db_username: this.form.db_username,
                    db_password: this.form.db_password,
                };

                const response = window.Fetch
                    ? await window.Fetch.post('/setup/database', payload)
                    : await fetch('/setup/database', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                        },
                        body: JSON.stringify(payload),
                    }).then((r) => r.json());

                if (response && response.status === 'success') {
                    window.Swal?.toast?.('success', response.message || 'Database ready.');
                    this.step = 3;
                } else {
                    window.Swal?.error?.(response?.message || 'Database setup failed.');
                }
            } catch (error) {
                window.Swal?.error?.(error?.message || 'Database setup failed.');
            } finally {
                this.isSubmitting = false;
            }
        },

        async completeSetup() {
            if (this.isSubmitting) {
                return;
            }

            this.isSubmitting = true;

            try {
                const payload = {
                    skin_directory: this.form.skin_directory,
                    admin_name: this.form.admin_name,
                    admin_email: this.form.admin_email,
                    password: this.form.password,
                    password_confirmation: this.form.password_confirmation,
                };

                const response = window.Fetch
                    ? await window.Fetch.post('/setup/complete', payload)
                    : await fetch('/setup/complete', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                        },
                        body: JSON.stringify(payload),
                    }).then((r) => r.json());

                if (response && response.status === 'success') {
                    window.Swal?.toast?.('success', response.message || 'Installation complete.');
                    window.location.href = response.redirect || '/dashboard';
                } else {
                    window.Swal?.error?.(response?.message || 'Failed to complete installation.');
                }
            } catch (error) {
                window.Swal?.error?.(error?.message || 'Failed to complete installation.');
            } finally {
                this.isSubmitting = false;
            }
        },

        // 5. Event Listeners & Cleanup Hooks
        refreshDOM() {
            this.initData();
        },
    };
}