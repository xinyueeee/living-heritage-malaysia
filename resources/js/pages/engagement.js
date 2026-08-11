document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('badgeModal');

    if (!modal) {
        return;
    }

    const image = document.getElementById('badgeModalImage');
    const title = document.getElementById('badgeModalTitle');
    const description = document.getElementById('badgeModalDescription');
    const requirement = document.getElementById('badgeModalRequirement');
    const status = document.getElementById('badgeModalStatus');
    const progressText = document.getElementById('badgeModalProgressText');
    const progressBar = document.getElementById('badgeModalProgressBar');
    const unlockedDate = document.getElementById('badgeModalUnlockedDate');

    document.querySelectorAll('[data-badge-card]').forEach((card) => {
        card.addEventListener('click', () => {
            const isUnlocked = card.dataset.unlocked === 'true';
            const progress = Number(card.dataset.progress || 0);
            const target = Number(card.dataset.target || 1);
            const percentage = Math.min(100, Math.max(0, Number(card.dataset.percentage || 0)));

            image.src = card.dataset.image;
            image.alt = card.dataset.name;
            title.textContent = card.dataset.name;
            description.textContent = card.dataset.description;
            requirement.textContent = card.dataset.requirement;
            progressText.textContent = `${progress} / ${target}`;
            progressBar.style.width = `${percentage}%`;
            status.textContent = isUnlocked ? 'Unlocked' : 'Locked';
            status.className = `badge-modal-status ${isUnlocked ? 'unlocked' : 'locked'}`;

            if (isUnlocked && card.dataset.unlockedDate) {
                unlockedDate.textContent = `Unlocked on ${card.dataset.unlockedDate}`;
                unlockedDate.hidden = false;
            } else {
                unlockedDate.hidden = true;
            }

            modal.hidden = false;
            document.body.classList.add('modal-open');
        });
    });

    modal.querySelectorAll('[data-close-badge-modal]').forEach((element) => {
        element.addEventListener('click', () => {
            modal.hidden = true;
            document.body.classList.remove('modal-open');
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.hidden) {
            modal.hidden = true;
            document.body.classList.remove('modal-open');
        }
    });
});
