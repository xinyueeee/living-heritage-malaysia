import '../bootstrap';

document.addEventListener('DOMContentLoaded', () => {
    document.body.addEventListener('submit', async (event) => {
        const form = event.target.closest('.post-like-form');

        if (!form) {
            return;
        }

        event.preventDefault();

        const button = form.querySelector('.like-action');
        const icon = form.querySelector('.like-icon');
        const count = form.querySelector('.like-count');

        if (!button || button.disabled) {
            return;
        }

        const wasLiked = form.dataset.liked === '1';

        button.disabled = true;

        try {
            let response;

            if (wasLiked) {
                response = await window.axios.delete(form.action);
            } else {
                response = await window.axios.post(form.action);
            }

            const data = response.data;
            const nowLiked = data.liked;

            form.dataset.liked = nowLiked ? '1' : '0';

            button.classList.toggle('is-liked', nowLiked);

            if (icon) {
                icon.textContent = nowLiked ? '♥' : '♡';
            }

            if (count) {
                count.textContent = data.likes_count;
            }

        } catch (error) {
            console.error('Like error:', error);
        } finally {
            button.disabled = false;
        }
    });
});