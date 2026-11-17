// resources/js/managers/avatarManager.js

export default function avatarManager(
    primaryUrlInit = null,
    galleryInit = [],
    personaNameInit = '',
    galleryGroupInit = '',
    primaryUrlEndpoint = '',
    deleteUrlEndpoint = ''
) {
    return {
        // 1. State Properties Upfront (Unified Modals & State Contract)
        modals: {
            avatar: {
                isOpen: false,
                open: false,
                mode: 'edit',
                id: '',
                action: primaryUrlEndpoint || '',
                method: 'POST'
            }
        },
        isBusy: false,
        primaryUrl: primaryUrlInit,
        gallery: Array.isArray(galleryInit) ? galleryInit : [],
        personaName: personaNameInit || '',
        galleryGroup: galleryGroupInit || '',
        currentIndex: 0,
        lightboxInstance: null,
        searchQuery: '',
        searchHelperInstance: null,
        filteredIds: null,

        // 2. Lifecycle Hook
        init() {
            this.initSearch();
            this.initLightbox();
        },

        // 3. Search, Filtering & Computed Lightbox Getters
        initSearch() {
            // Reserved for gallery filtering when needed
        },

        isItemVisible() {
            return true;
        },

        get allImages() {
            const imgs = [];
            if (this.primaryUrl) {
                imgs.push(this.primaryUrl);
            }
            this.gallery.forEach(item => {
                if (item && item.url) {
                    imgs.push(item.url);
                }
            });
            return imgs;
        },

        get currentImage() {
            const imgs = this.allImages;
            if (imgs.length === 0) return null;
            if (this.currentIndex >= imgs.length) {
                this.currentIndex = 0;
            }
            return imgs[this.currentIndex];
        },

        nextImage() {
            const imgs = this.allImages;
            if (imgs.length <= 1) return;
            this.currentIndex = (this.currentIndex + 1) % imgs.length;
            this.$nextTick(() => this.initLightbox());
        },

        prevImage() {
            const imgs = this.allImages;
            if (imgs.length <= 1) return;
            this.currentIndex = (this.currentIndex - 1 + imgs.length) % imgs.length;
            this.$nextTick(() => this.initLightbox());
        },

        initLightbox() {
            if (window.Lightbox && typeof window.Lightbox.init === 'function') {
                if (this.lightboxInstance && typeof this.lightboxInstance.destroy === 'function') {
                    this.lightboxInstance.destroy();
                }
                this.lightboxInstance = window.Lightbox.init('.glightbox', {
                    touchNavigation: true,
                    loop: true
                });
            }
        },

        syncPortraits(payload) {
            if (!payload) return;
            this.primaryUrl = payload.primary_url || payload.primary_portrait_url || null;
            this.gallery = payload.gallery || [];
            this.currentIndex = 0;
            this.$nextTick(() => this.initLightbox());
        },

        // 4. Modal & Action Handlers (Using window.Modal & window.Fetch)
        openModal(mode = 'edit', data = {}) {
            window.Modal.open(this.modals.avatar, mode, data, {
                baseUrl: primaryUrlEndpoint
            });
        },

        closeModal() {
            window.Modal.close(this.modals.avatar);
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
                    this.syncPortraits(data.payload || data.data);
                    if (window.Swal && typeof window.Swal.toast === 'function') {
                        window.Swal.toast('success', data.message || 'Primary portrait updated.');
                    }
                }
            } catch (err) {
                console.error('[AvatarManager] Failed to set primary portrait:', err);
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
                        this.syncPortraits(data.payload || data.data);
                        if (window.Swal && typeof window.Swal.toast === 'function') {
                            window.Swal.toast('success', data.message || 'Portrait deleted.');
                        }
                    }
                } catch (err) {
                    console.error('[AvatarManager] Failed to delete portrait:', err);
                } finally {
                    this.isBusy = false;
                }
            }
        },

        // 5. DOM Refresh Handler (sanitize = false)
        async refreshDOM() {
            if (typeof window.updateDOM === 'function') {
                await window.updateDOM(this.$refs, ['avatarGalleryContent'], () => {
                    this.closeModal();
                    this.$nextTick(() => {
                        this.initLightbox();
                    });
                }, false);
            } else {
                this.initLightbox();
            }
        }
    };
}