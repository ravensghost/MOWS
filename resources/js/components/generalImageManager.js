export default function generalImageManager() {
    return {
        images: [],
        isLoading: false,
        uploadModalOpen: false,

        init() {
            this.loadImages();
        },

        async loadImages() {
            this.isLoading = true;
            try {
                let res = await fetch('/admin/general-images');
                let data = await res.json();
                this.images = data.images || [];
            } catch (e) {
                this.images = [];
            } finally {
                this.isLoading = false;
            }
        },

        copyUrl(url) {
            copyToClipboard(url);
            window.Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Asset URL copied to clipboard!',
                showConfirmButton: false,
                timer: 2000,
                background: 'var(--theme-bg-panel, #111827)',
                color: 'var(--theme-text-p, #ffffff)'
            });
        },

        async deleteImage(filename) {
            const confirmResult = await window.Swal.fire({
                title: 'Delete Asset?',
                text: `Are you sure you want to delete ${filename}?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Delete',
                cancelButtonText: 'Cancel',
                background: 'var(--theme-bg-panel, #111827)',
                color: 'var(--theme-text-p, #ffffff)'
            });

            if (!confirmResult.isConfirmed) return;

            try {
                const response = await fetch('/admin/general-images', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                    },
                    body: JSON.stringify({ filename: filename })
                });
                const data = await response.json();
                if (response.ok) {
                    this.loadImages();
                    window.Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: data.message, showConfirmButton: false, timer: 2000, background: 'var(--theme-bg-panel, #111827)', color: 'var(--theme-text-p, #ffffff)' });
                } else {
                    window.Swal.fire('Error', data.message || 'Failed to delete asset.', 'error');
                }
            } catch (err) {
                window.Swal.fire('Error', 'Network error occurred.', 'error');
            }
        }
    };
}