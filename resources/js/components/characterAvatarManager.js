export default (primaryUrlInit, galleryInit, characterNameInit, galleryGroupInit, primaryUrlEndpoint, deleteUrlEndpoint) => ({
    editModalOpen: false,
    isBusy: false,
    primaryUrl: primaryUrlInit,
    gallery: galleryInit,
    characterName: characterNameInit,
    galleryGroup: galleryGroupInit,
    currentIndex: 0,
    lightboxInstance: null,

    get allImages() {
        let imgs = [];
        if (this.primaryUrl) imgs.push(this.primaryUrl);
        this.gallery.forEach(item => imgs.push(item.url));
        return imgs;
    },

    get currentImage() {
        let imgs = this.allImages;
        if (imgs.length === 0) return null;
        if (this.currentIndex >= imgs.length) this.currentIndex = 0;
        return imgs[this.currentIndex];
    },

    nextImage() {
        let imgs = this.allImages;
        if (imgs.length <= 1) return;
        this.currentIndex = (this.currentIndex + 1) % imgs.length;
        this.$nextTick(() => this.initLightbox());
    },

    prevImage() {
        let imgs = this.allImages;
        if (imgs.length <= 1) return;
        this.currentIndex = (this.currentIndex - 1 + imgs.length) % imgs.length;
        this.$nextTick(() => this.initLightbox());
    },

    initLightbox() {
        if (window.GLightbox) {
            if (this.lightboxInstance) {
                this.lightboxInstance.destroy();
            }
            this.lightboxInstance = window.GLightbox({
                selector: '.glightbox',
                touchNavigation: true,
                loop: true
            });
        }
    },

    syncPortraits(payload) {
        if (!payload) return;
        this.primaryUrl = payload.primary_url || null;
        this.gallery = payload.gallery || [];
        this.currentIndex = 0;
        this.$nextTick(() => {
            this.initLightbox();
            if (window.refreshDOM_US) window.refreshDOM_US();
        });
    },

    async setAsPrimary(attributeId) {
        if (this.isBusy) return;
        this.isBusy = true;
        try {
            const response = await fetch(primaryUrlEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                },
                body: JSON.stringify({ attribute_id: attributeId })
            });
            const data = await response.json();
            if (response.ok) {
                this.syncPortraits(data.portraits);
                window.Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: data.message, showConfirmButton: false, timer: 2500, background: 'var(--theme-bg-panel, #111827)', color: 'var(--theme-text-p, #ffffff)' });
            } else {
                window.Swal.fire('Error', data.message || 'Failed to update primary portrait.', 'error');
            }
        } catch (err) {
            window.Swal.fire('Error', 'Network error occurred.', 'error');
        } finally {
            this.isBusy = false;
        }
    },

    async removePortrait(type, attributeId = null) {
        if (this.isBusy) return;
        const confirmResult = await window.Swal.fire({
            title: 'Remove Portrait?',
            text: 'This image file will be permanently removed from the character gallery.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Delete',
            cancelButtonText: 'Cancel',
            background: 'var(--theme-bg-panel, #111827)',
            color: 'var(--theme-text-p, #ffffff)'
        });

        if (!confirmResult.isConfirmed) return;

        this.isBusy = true;
        try {
            const response = await fetch(deleteUrlEndpoint, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                },
                body: JSON.stringify({ type: type, attribute_id: attributeId })
            });
            const data = await response.json();
            if (response.ok) {
                this.syncPortraits(data.portraits);
                window.Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: data.message, showConfirmButton: false, timer: 2500, background: 'var(--theme-bg-panel, #111827)', color: 'var(--theme-text-p, #ffffff)' });
            } else {
                window.Swal.fire('Error', data.message || 'Failed to remove portrait.', 'error');
            }
        } catch (err) {
            window.Swal.fire('Error', 'Network error occurred.', 'error');
        } finally {
            this.isBusy = false;
        }
    },

    init() {
        this.initLightbox();
    }
});