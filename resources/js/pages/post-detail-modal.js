import '../bootstrap';

document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('postDetailModal');
    const modalBody = document.getElementById('postDetailModalBody');
    const closeBtn = document.getElementById('postDetailModalClose');

    if (!modal || !modalBody) {
        return;
    }

    const openModal = (templateId) => {
        const template = document.getElementById(templateId);

        if (!template) {
            return;
        }

        modalBody.innerHTML = '';
        modalBody.appendChild(template.content.cloneNode(true));
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    };

    const closeModal = () => {
        modal.classList.remove('active');
        modalBody.innerHTML = '';
        document.body.style.overflow = '';
    };

    document.querySelectorAll('.saved-posts-grid-item').forEach((tile) => {
        tile.addEventListener('click', () => openModal(tile.dataset.detailTarget));
    });

    closeBtn?.addEventListener('click', closeModal);

    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeModal();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && modal.classList.contains('active')) {
            closeModal();
        }
    });
});
