import '../bootstrap';

document.addEventListener('DOMContentLoaded', () => {
    let snackbarTimeout;

    const showSnackbar = (message) => {
        let snackbar = document.querySelector('.save-snackbar');

        if (!snackbar) {
            snackbar = document.createElement('div');
            snackbar.className = 'save-snackbar';
            snackbar.setAttribute('role', 'status');
            snackbar.setAttribute('aria-live', 'polite');
            document.body.appendChild(snackbar);
        }

        snackbar.textContent = message;
        snackbar.classList.add('is-visible');

        clearTimeout(snackbarTimeout);
        snackbarTimeout = setTimeout(() => {
            snackbar.classList.remove('is-visible');
        }, 2500);
    };

    document.body.addEventListener('submit', async (event) => {
        const form = event.target.closest('.post-save-form');

        if (!form) {
            return;
        }

        event.preventDefault();

        const button = form.querySelector('.post-save-action');
        const label = form.querySelector('.post-save-label');

        if (!button || button.disabled) {
            return;
        }

        const wasSaved = form.dataset.saved === '1';
        button.disabled = true;

        try {
            if (wasSaved) {
                await window.axios.delete(form.action);
            } else {
                await window.axios.post(form.action);
            }

            const nowSaved = !wasSaved;
            form.dataset.saved = nowSaved ? '1' : '0';
            button.classList.toggle('is-saved', nowSaved);

            if (label) {
                label.textContent = nowSaved ? 'Saved' : 'Save';
            }

            showSnackbar(nowSaved ? 'Post saved.' : 'Post removed from saved posts.');

            const savedGrid = document.getElementById('saved-posts-grid');

            if (savedGrid && !nowSaved) {
                form.closest('.post-card')?.remove();

                if (!savedGrid.querySelector('.post-card')) {
                    savedGrid.hidden = true;
                    document.getElementById('saved-posts-empty')?.removeAttribute('hidden');
                }
            }
        } catch (error) {
            showSnackbar('Something went wrong. Please try again.');
        } finally {
            button.disabled = false;
        }
    });
});
