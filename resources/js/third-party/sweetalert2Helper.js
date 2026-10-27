// resources/js/third-party/sweetalert2Helper.js
import Swal from 'sweetalert2';

/**
 * Global SweetAlert2 Helper Utility
 * Standardises theme colours, dark styling, toasts, and confirmation prompts.
 */
window.Swal = {
    themeConfig() {
        return {
            background: 'var(--theme-bg-panel-body, #111827)',
            color: 'var(--theme-text-p, #ffffff)'
        };
    },
    toast(icon, title, timer = 1500) {
        if (!Swal) return;
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: icon,
            title: title,
            showConfirmButton: false,
            timer: timer,
            ...this.themeConfig()
        });
    },
    loading(title = 'Please wait...', html = 'Processing request...') {
        if (!Swal) return;
        Swal.fire({
            title: title,
            html: html,
            allowOutsideClick: false,
            showConfirmButton: false,
            didOpen: () => Swal.showLoading(),
            ...this.themeConfig()
        });
    },
    confirm(title, text, confirmText = 'Yes, proceed!') {
        if (!Swal) return Promise.resolve({ isConfirmed: false });
        return Swal.fire({
            title: title,
            text: text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: 'var(--theme-bg-danger-colour, #ef4444)',
            cancelButtonColor: 'var(--theme-bg-primary-colour, #3b82f6)',
            confirmButtonText: confirmText,
            cancelButtonText: 'Cancel',
            ...this.themeConfig()
        });
    },
    error(title = 'Error', text = 'An unexpected error occurred.') {
        if (!Swal) return;
        Swal.fire({
            icon: 'error',
            title: title,
            text: text,
            ...this.themeConfig()
        });
    },
    close() {
        if (Swal) Swal.close();
    },
    fire(...args) {
        if (Swal) return Swal.fire(...args);
    }
};

/**
 * Alpine component for handling session flash alerts from backend
 */
export default function sweetalert2Helper() {
    return {
        init() {
            // Check for global session flash data injected from Laravel backend
            if (window.sessionFlash) {
                const { type, message } = window.sessionFlash;
                if (type && message) {
                    if (type === 'success' || type === 'info' || type === 'warning') {
                        window.Swal.toast(type, message);
                    } else if (type === 'error') {
                        window.Swal.error('Error', message);
                    }
                }
            }
        }
    };
}