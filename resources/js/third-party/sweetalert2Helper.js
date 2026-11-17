// resources/js/third-party/sweetalert2Helper.js
import Swal from 'sweetalert2';

/**
 * Global SweetAlert2 Helper Wrapper & Dynamic Theme Configuration
 */
const resolveColour = (varName, fallback) => {
    if (typeof window.getThemeColour === 'function') {
        const val = window.getThemeColour(varName);
        return val && val !== varName ? val : fallback;
    }
    return fallback;
};

window.Swal = {
    ...Swal,

    themeConfig() {
        return {
            background: resolveColour('--theme-bg-panel-body-colour', '#1a1a1a'),
            color: resolveColour('--theme-text-h-colour', '#f3f4f6'),
            confirmButtonColor: resolveColour('--theme-bg-primary-colour', '#3b82f6'),
            cancelButtonColor: resolveColour('--theme-bg-secondary-colour', '#4b5563'),
            customClass: {
                popup: 'mows-swal-popup border-panel border-rounded shadow-lg',
                title: 'mows-swal-title text-h font-bold',
                htmlContainer: 'mows-swal-text text-p',
                confirmButton: 'mows-swal-confirm-btn',
                cancelButton: 'mows-swal-cancel-btn'
            }
        };
    },

    toast(icon, title, timer = 3000) {
        const theme = this.themeConfig();
        return Swal.fire({
            icon: icon,
            title: title,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: timer,
            timerProgressBar: true,
            background: theme.background,
            color: theme.color,
            didOpen: (toastEl) => {
                toastEl.addEventListener('mouseenter', Swal.stopTimer);
                toastEl.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });
    },

    confirm(title, text = 'This action cannot be undone.', confirmButtonText = 'Yes, proceed') {
        return Swal.fire({
            title: title,
            text: text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: confirmButtonText,
            cancelButtonText: 'Cancel',
            ...this.themeConfig()
        });
    },

    error(title = 'Error', text = 'An unexpected error occurred.') {
        return Swal.fire({
            icon: 'error',
            title: title,
            text: text,
            ...this.themeConfig()
        });
    },

    loading(title = 'Processing...', html = 'Please wait while we complete your request.') {
        return Swal.fire({
            title: title,
            html: html,
            allowOutsideClick: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            },
            ...this.themeConfig()
        });
    }
};

window.swal = window.Swal;

export default function sweetalert2Helper() {
    return {
        toast(icon, title, timer = 3000) {
            return window.Swal.toast(icon, title, timer);
        },
        confirm(title, text, confirmButtonText) {
            return window.Swal.confirm(title, text, confirmButtonText);
        },
        error(title, text) {
            return window.Swal.error(title, text);
        },
        loading(title, html) {
            return window.Swal.loading(title, html);
        }
    };
}