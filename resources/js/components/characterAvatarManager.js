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

    init() {
        this.initLightbox();
    }
});