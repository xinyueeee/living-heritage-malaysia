import '../bootstrap';

document.addEventListener('DOMContentLoaded', () => {
    const wrap = document.querySelector('[data-photo-upload]');
    const modal = document.getElementById('profilePhotoModal');

    if (!wrap || !modal) {
        return;
    }

    const viewTrigger = wrap.querySelector('[data-action="view"]');
    const modalImage = document.getElementById('profilePhotoModalImage');
    const modalDate = document.getElementById('profilePhotoModalDate');
    const modalThumbs = document.getElementById('profilePhotoModalThumbs');
    const modalClose = document.getElementById('profilePhotoModalClose');
    const modalPrev = document.getElementById('profilePhotoModalPrev');
    const modalNext = document.getElementById('profilePhotoModalNext');
    const modalSetCurrent = document.getElementById('profilePhotoModalSetCurrent');

    let photos = [];
    let currentIndex = 0;

    const renderThumbs = () => {
        modalThumbs.innerHTML = photos.map((photo, index) => `
            <button type="button" class="profile-photo-modal-thumb ${photo.is_current ? 'is-current-photo' : ''}" data-index="${index}">
                <img src="${photo.url}" alt="">
            </button>
        `).join('');
    };

    const renderMain = () => {
        const photo = photos[currentIndex];

        if (!photo) {
            return;
        }

        modalImage.src = photo.url;
        modalDate.textContent = photo.is_current
            ? 'Current photo'
            : (photo.uploaded_at ? `Uploaded ${photo.uploaded_at}` : '');

        modalSetCurrent.hidden = photo.is_current || !photo.id;

        const showNav = photos.length > 1;
        modalPrev.style.visibility = showNav ? 'visible' : 'hidden';
        modalNext.style.visibility = showNav ? 'visible' : 'hidden';

        modalThumbs.querySelectorAll('.profile-photo-modal-thumb').forEach((thumb, index) => {
            thumb.classList.toggle('is-active', index === currentIndex);
        });
    };

    const closeModal = () => {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    };

    viewTrigger?.addEventListener('click', () => {
        try {
            photos = JSON.parse(wrap.dataset.photoHistory || '[]');
        } catch (error) {
            photos = [];
        }

        if (photos.length === 0) {
            return;
        }

        currentIndex = photos.findIndex((photo) => photo.is_current);
        if (currentIndex < 0) {
            currentIndex = 0;
        }

        renderThumbs();
        renderMain();
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    });

    modalClose?.addEventListener('click', closeModal);

    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeModal();
        }
    });

    modalPrev?.addEventListener('click', () => {
        currentIndex = (currentIndex - 1 + photos.length) % photos.length;
        renderMain();
    });

    modalNext?.addEventListener('click', () => {
        currentIndex = (currentIndex + 1) % photos.length;
        renderMain();
    });

    modalThumbs?.addEventListener('click', (event) => {
        const thumb = event.target.closest('.profile-photo-modal-thumb');

        if (!thumb) {
            return;
        }

        currentIndex = parseInt(thumb.dataset.index, 10);
        renderMain();
    });

    modalSetCurrent?.addEventListener('click', async () => {
        const photo = photos[currentIndex];

        if (!photo || !photo.id) {
            return;
        }

        modalSetCurrent.disabled = true;

        try {
            const response = await window.axios.patch(`/profile/photo/${photo.id}/restore`);
            const newUrl = response.data.photo_url;

            photos = photos.map((p) => ({ ...p, is_current: p.url === newUrl }));
            wrap.dataset.photoHistory = JSON.stringify(photos);

            const avatarImg = wrap.querySelector('[data-avatar-image]');
            if (avatarImg) {
                avatarImg.src = `${newUrl}?t=${Date.now()}`;
            }

            renderThumbs();
            renderMain();
        } catch (error) {
            // Keep the modal open so the user can retry.
        } finally {
            modalSetCurrent.disabled = false;
        }
    });

    document.addEventListener('keydown', (event) => {
        if (!modal.classList.contains('active')) {
            return;
        }

        if (event.key === 'Escape') {
            closeModal();
        }

        if (event.key === 'ArrowLeft') {
            modalPrev?.click();
        }

        if (event.key === 'ArrowRight') {
            modalNext?.click();
        }
    });
});
