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

            // Saved Posts clones its post detail from a hidden <template> each
            // time it's opened, so also update the template's stored markup —
            // otherwise reopening the same post reverts to the old like state.
            const postId = form.action.match(/community\/posts\/(\d+)\/like/)?.[1];
            const template = postId ? document.getElementById(`post-detail-${postId}`) : null;

            if (template) {
                const templateForm = template.content.querySelector('.post-like-form');
                const templateButton = templateForm?.querySelector('.like-action');
                const templateIcon = templateForm?.querySelector('.like-icon');
                const templateCount = templateForm?.querySelector('.like-count');

                if (templateForm) {
                    templateForm.dataset.liked = nowLiked ? '1' : '0';
                    templateButton?.classList.toggle('is-liked', nowLiked);

                    if (templateIcon) {
                        templateIcon.textContent = nowLiked ? '♥' : '♡';
                    }

                    if (templateCount) {
                        templateCount.textContent = data.likes_count;
                    }
                }
            }
        } catch (error) {
            console.error('Like error:', error);
        } finally {
            button.disabled = false;
        }
    });
});