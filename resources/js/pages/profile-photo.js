import '../bootstrap';

document.addEventListener('DOMContentLoaded', () => {
    const wrap = document.querySelector('[data-photo-upload]');

    if (!wrap) {
        return;
    }

    const editBtn = wrap.querySelector('[data-action="edit"]');
    const fileInput = wrap.querySelector('[data-photo-input]');
    const errorEl = wrap.querySelector('[data-avatar-error]');
    const successEl = wrap.querySelector('[data-avatar-success]');
    let successTimeout;

    editBtn?.addEventListener('click', () => fileInput.click());

    fileInput?.addEventListener('change', async () => {
        const file = fileInput.files?.[0];

        if (!file) {
            return;
        }

        errorEl.hidden = true;
        if (successEl) {
            successEl.hidden = true;
        }
        editBtn.disabled = true;

        const formData = new FormData();
        formData.append('photo', file);

        try {
            const response = await window.axios.post('/profile/photo', formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });

            const photoUrl = response.data.photo_url;
            let img = wrap.querySelector('[data-avatar-image]');

            if (!img) {
                const fallback = wrap.querySelector('[data-avatar-fallback]');
                img = document.createElement('img');
                img.className = 'profile-avatar-lg';
                img.setAttribute('data-avatar-image', '');
                img.alt = '';
                fallback?.replaceWith(img);
            }

            img.src = `${photoUrl}?t=${Date.now()}`;

            if (successEl) {
                successEl.hidden = false;
                clearTimeout(successTimeout);
                successTimeout = setTimeout(() => {
                    successEl.hidden = true;
                }, 2000);
            }
        } catch (error) {
            const message = error.response?.data?.errors?.photo?.[0]
                ?? error.response?.data?.message
                ?? 'Could not upload photo. Please try again.';
            errorEl.textContent = message;
            errorEl.hidden = false;
        } finally {
            editBtn.disabled = false;
            fileInput.value = '';
        }
    });
});
