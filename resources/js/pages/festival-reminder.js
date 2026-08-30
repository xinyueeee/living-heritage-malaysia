document.addEventListener('DOMContentLoaded', () => {
    const button = document.querySelector('[data-festival-reminder]');
    const dialog = document.querySelector('[data-festival-reminder-dialog]');
    if (!button || !dialog) return;

    const title = dialog.querySelector('[data-festival-reminder-title]');
    const message = dialog.querySelector('[data-festival-reminder-message]');
    const closeButtons = dialog.querySelectorAll('[data-festival-reminder-close]');
    const showDialog = (heading, body) => {
        title.textContent = heading;
        message.textContent = body;
        dialog.showModal();
    };

    closeButtons.forEach((close) => close.addEventListener('click', () => dialog.close()));
    dialog.addEventListener('click', (event) => { if (event.target === dialog) dialog.close(); });

    button.addEventListener('click', async () => {
        if (button.dataset.reminderSet === 'true') {
            showDialog('Reminder already set', 'A reminder has already been created for this festival.');
            return;
        }

        button.disabled = true;
        try {
            const response = await fetch(button.dataset.reminderUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                },
                body: JSON.stringify({
                    experience_id: Number(button.dataset.experienceId),
                    selected_date: button.dataset.selectedDate,
                }),
            });
            const data = await response.json();
            if (response.ok && data.already_added) {
                button.dataset.reminderSet = 'true';
                button.classList.add('is-set');
                button.innerHTML = '<span aria-hidden="true">✓</span> Reminder Set';
                showDialog('Reminder already set', 'A reminder has already been created for this festival date.');
                return;
            }
            if (!response.ok || !data.success) throw new Error(data.message || 'The reminder could not be added.');

            button.dataset.reminderSet = 'true';
            button.classList.add('is-set');
            button.innerHTML = '<span aria-hidden="true">✓</span> Reminder Set';
            showDialog(
                'Reminder Added',
                "You'll be reminded before this festival begins.",
            );
        } catch {
            showDialog('Reminder unavailable', 'The reminder could not be added right now. Please try again later.');
        } finally {
            button.disabled = false;
        }
    });
});
