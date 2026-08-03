import '../bootstrap';

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.profile-field').forEach((fieldEl) => {
        const field = fieldEl.dataset.field;
        const display = fieldEl.querySelector('.profile-field-display');
        const form = fieldEl.querySelector('.profile-field-form');
        const valueEl = fieldEl.querySelector('.profile-field-value');
        const input = fieldEl.querySelector('.profile-field-input');
        const errorEl = fieldEl.querySelector('.profile-field-error');
        const successEl = fieldEl.querySelector('.profile-field-success');
        const editBtn = fieldEl.querySelector('[data-action="edit"]');
        const saveBtn = fieldEl.querySelector('[data-action="save"]');
        const cancelBtn = fieldEl.querySelector('[data-action="cancel"]');

        if (!field || !display || !form || !valueEl || !input) {
            return;
        }

        const formatForDisplay = (raw) => {
            if (!raw) {
                return 'Not set yet.';
            }

            if (field === 'birthday') {
                return new Date(`${raw}T00:00:00`).toLocaleDateString('en-GB', {
                    day: '2-digit',
                    month: 'short',
                    year: 'numeric',
                });
            }

            return raw;
        };

        const showForm = () => {
            errorEl.hidden = true;
            input.value = valueEl.dataset.raw ?? '';
            display.hidden = true;
            form.hidden = false;
            input.focus();
        };

        const showDisplay = () => {
            form.hidden = true;
            display.hidden = false;
        };

        editBtn?.addEventListener('click', showForm);
        cancelBtn?.addEventListener('click', showDisplay);

        saveBtn?.addEventListener('click', async () => {
            errorEl.hidden = true;
            saveBtn.disabled = true;

            try {
                const response = await window.axios.patch(`/profile/personal-information/${field}`, {
                    value: input.value,
                });

                const newValue = response.data.value;
                valueEl.dataset.raw = newValue ?? '';
                valueEl.textContent = formatForDisplay(newValue);
                showDisplay();

                if (successEl) {
                    successEl.hidden = false;
                    setTimeout(() => {
                        successEl.hidden = true;
                    }, 2000);
                }
            } catch (error) {
                const message = error.response?.data?.errors?.value?.[0]
                    ?? error.response?.data?.message
                    ?? 'Something went wrong. Please try again.';
                errorEl.textContent = message;
                errorEl.hidden = false;
            } finally {
                saveBtn.disabled = false;
            }
        });
    });
});
