// resources/js/third-party/sweetalert2Helper.js
import Swal from 'sweetalert2';

/**
 * Global SweetAlert2 Helper Wrapper & Theme Configuration
 */
window.Swal = {
    ...Swal,

    themeConfig() {
        return {
            background: '#1a1a1a',
            color: '#f3f4f6',
            confirmButtonColor: '#3b82f6',
            cancelButtonColor: '#4b5563',
            customClass: {
                popup: 'mows-swal-popup',
                title: 'mows-swal-title',
                htmlContainer: 'mows-swal-text',
                confirmButton: 'mows-swal-confirm-btn',
                cancelButton: 'mows-swal-cancel-btn'
            }
        };
    },

    toast(icon, title, timer = 3000) {
        return Swal.fire({
            icon: icon,
            title: title,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: timer,
            timerProgressBar: true,
            background: '#1a1a1a',
            color: '#f3f4f6',
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
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

export default Swal;