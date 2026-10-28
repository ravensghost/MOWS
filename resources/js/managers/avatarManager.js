// resources/js/managers/avatarManager.js
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
        this.$nextTick(() => this.initLightbox());
    },

    async setPrimary(avatarId) {
        if (!primaryUrlEndpoint) return;
        this.isBusy = true;

        try {
            const { ok, data } = await window.Fetch.post(primaryUrlEndpoint, { avatar_id: avatarId }, {
                loading: true,
                loadingTitle: 'Please wait...',
                loadingHtml: 'Updating primary portrait...',
                showError: true
            });

            if (ok && data) {
                this.syncPortraits(data.payload);
                if (window.Swal && typeof window.Swal.toast === 'function') {
                    window.Swal.toast('success', data.message || 'Primary portrait updated.');
                }
            }
        } catch (err) {
            console.error('Failed to set primary portrait:', err);
        } finally {
            this.isBusy = false;
        }
    },

    async deleteAvatar(avatarId) {
        const result = await window.Swal.confirm(
            'Delete this portrait?',
            'This action cannot be reverted.',
            'Yes, delete'
        );

        if (result.isConfirmed && deleteUrlEndpoint) {
            this.isBusy = true;
            try {
                const { ok, data } = await window.Fetch.delete(`${deleteUrlEndpoint}/${avatarId}`, {}, {
                    loading: true,
                    loadingTitle: 'Deleting...',
                    loadingHtml: 'Removing portrait asset...',
                    showError: true
                });

                if (ok && data) {
                    this.syncPortraits(data.payload);
                    if (window.Swal && typeof window.Swal.toast === 'function') {
                        window.Swal.toast('success', data.message || 'Portrait deleted.');
                    }
                }
            } catch (err) {
                console.error('Failed to delete portrait:', err);
            } finally {
                this.isBusy = false;
            }
        }
    },

    init() {
        this.initLightbox();
    }
});