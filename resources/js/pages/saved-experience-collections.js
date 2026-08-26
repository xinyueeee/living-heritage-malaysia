document.addEventListener('DOMContentLoaded', () => {
    const dialog = document.querySelector('[data-saved-picker]');
    if (!dialog) return;

    const saveForm = dialog.querySelector('[data-save-form]');
    const newPanel = dialog.querySelector('[data-new-collection]');
    const nameInput = dialog.querySelector('[data-new-collection-name]');
    const error = dialog.querySelector('[data-picker-error]');

    document.querySelectorAll('[data-open-save-picker]').forEach((button) => button.addEventListener('click', () => {
        saveForm.action = button.dataset.saveUrl;
        dialog.showModal();
    }));
    dialog.querySelectorAll('[data-picker-close]').forEach((button) => button.addEventListener('click', () => dialog.close()));
    dialog.addEventListener('click', (event) => { if (event.target === dialog) dialog.close(); });
    dialog.querySelector('[data-new-collection-toggle]').addEventListener('click', () => {
        newPanel.hidden = !newPanel.hidden;
        if (!newPanel.hidden) nameInput.focus();
    });
    dialog.querySelector('[data-create-collection]').addEventListener('click', async () => {
        error.hidden = true;
        try {
            const response = await fetch(dialog.dataset.createUrl, {
                method: 'POST',
                headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content},
                body: JSON.stringify({name: nameInput.value}),
            });
            const payload = await response.json();
            if (!response.ok) throw new Error(payload.message || Object.values(payload.errors || {}).flat()[0] || 'Unable to create collection.');
            const item = document.createElement('label');
            const input = document.createElement('input');
            const label = document.createElement('span');
            input.type = 'radio'; input.name = 'collection_id'; input.value = payload.collection.collection_id; input.checked = true;
            label.textContent = payload.collection.name;
            item.append(input, ' ', label);
            dialog.querySelector('[data-picker-options]').append(item);
            nameInput.value = ''; newPanel.hidden = true;
        } catch (exception) {
            error.textContent = exception.message; error.hidden = false;
        }
    });

    const alreadySavedDialog = document.querySelector('[data-already-saved-dialog]');
    const alreadySavedMessage = alreadySavedDialog?.querySelector('[data-already-saved-message]');
    document.querySelectorAll('[data-open-already-saved]').forEach((button) => button.addEventListener('click', () => {
        const collectionName = button.dataset.collectionName?.trim();
        alreadySavedMessage.textContent = collectionName === 'Default'
            ? 'This experience is already saved in Default.'
            : collectionName
                ? `This experience is already saved in your “${collectionName}” collection.`
                : 'This experience is already saved.';
        alreadySavedDialog.showModal();
    }));
    alreadySavedDialog?.querySelectorAll('[data-already-saved-close]').forEach((button) => button.addEventListener('click', () => alreadySavedDialog.close()));
    alreadySavedDialog?.addEventListener('click', (event) => { if (event.target === alreadySavedDialog) alreadySavedDialog.close(); });

    const removeDialog = document.querySelector('[data-remove-saved-dialog]');
    const removeForm = removeDialog?.querySelector('[data-remove-saved-form]');
    const removeMessage = removeDialog?.querySelector('[data-remove-saved-message]');
    document.querySelectorAll('[data-open-remove-saved]').forEach((button) => button.addEventListener('click', () => {
        removeForm.action = button.dataset.removeUrl;
        removeMessage.textContent = `Are you sure you want to remove “${button.dataset.experienceName}” from your saved experiences?`;
        removeDialog.showModal();
    }));
    removeDialog?.querySelectorAll('[data-remove-saved-close]').forEach((button) => button.addEventListener('click', () => removeDialog.close()));
    removeDialog?.addEventListener('click', (event) => { if (event.target === removeDialog) removeDialog.close(); });
});
