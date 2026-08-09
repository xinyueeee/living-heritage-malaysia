import '../bootstrap';

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('[data-interests-form]');

    if (!form) {
        return;
    }

    const errorEl = form.querySelector('[data-interests-error]');
    const successEl = form.querySelector('[data-interests-success]');
    const submitBtn = form.querySelector('button[type="submit"]');

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        errorEl.hidden = true;
        submitBtn.disabled = true;

        const categoryIds = Array.from(form.querySelectorAll('input[name="category_ids[]"]:checked'))
            .map((input) => Number(input.value));

        try {
            await window.axios.put('/profile/interests', { category_ids: categoryIds });

            successEl.hidden = false;
            setTimeout(() => {
                successEl.hidden = true;
            }, 2000);
        } catch (error) {
            const message = error.response?.data?.errors?.category_ids?.[0]
                ?? error.response?.data?.message
                ?? 'Could not save your interests. Please try again.';
            errorEl.textContent = message;
            errorEl.hidden = false;
        } finally {
            submitBtn.disabled = false;
        }
    });
});
