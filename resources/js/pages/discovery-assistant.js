const initializeDiscoveryAssistant = () => {
    const root = document.querySelector('[data-discovery-assistant]');

    if (!root) {
        return;
    }

    const toggle = root.querySelector('.discovery-assistant__toggle');
    const panel = root.querySelector('.discovery-assistant__panel');
    const close = root.querySelector('.discovery-assistant__close');
    const reset = root.querySelector('.discovery-assistant__reset');
    const form = root.querySelector('.discovery-assistant__form');
    const input = form.querySelector('input[name="message"]');
    const submit = form.querySelector('button[type="submit"]');
    const messages = root.querySelector('.discovery-assistant__messages');
    const status = root.querySelector('[data-assistant-status]');
    const prompts = root.querySelector('.discovery-assistant__prompts');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    const historyKey = 'livingHeritage.discoveryAssistant.history.v1';
    const openKey = 'livingHeritage.discoveryAssistant.open.v1';
    const maxHistoryEntries = 40;
    const welcomeMessage = 'Hello! I can find Cultural Experiences, suggest ideas based on your interests, or explain a recommendation.';
    let contextExperienceId = null;
    let isSending = false;

    const storage = {
        read(key, fallback) {
            try {
                return JSON.parse(window.sessionStorage.getItem(key)) ?? fallback;
            } catch {
                return fallback;
            }
        },
        write(key, value) {
            try {
                window.sessionStorage.setItem(key, JSON.stringify(value));
            } catch {
                // The assistant remains usable when storage is unavailable.
            }
        },
        remove(key) {
            try {
                window.sessionStorage.removeItem(key);
            } catch {
                // Nothing else is required when storage is unavailable.
            }
        },
    };

    let history = storage.read(historyKey, []);
    if (!Array.isArray(history)) {
        history = [];
    }

    const persistHistory = () => storage.write(historyKey, history.slice(-maxHistoryEntries));

    const setOpen = (isOpen) => {
        panel.hidden = !isOpen;
        toggle.setAttribute('aria-expanded', String(isOpen));
        storage.write(openKey, isOpen);

        if (isOpen) {
            window.setTimeout(() => input.focus(), 0);
        }
    };

    const messageBubble = (text, sender) => {
        const bubble = document.createElement('div');
        const paragraph = document.createElement('p');
        bubble.className = `discovery-assistant__message discovery-assistant__message--${sender}`;
        paragraph.textContent = text;
        bubble.append(paragraph);
        messages.append(bubble);
        return bubble;
    };

    const experienceCard = (experience) => {
        const card = document.createElement('article');
        card.className = 'discovery-assistant__card';

        const media = document.createElement('div');
        media.className = 'discovery-assistant__card-media';
        media.setAttribute('aria-hidden', 'true');

        if (experience.image_url) {
            const image = document.createElement('img');
            image.alt = '';
            image.loading = 'lazy';
            image.referrerPolicy = 'no-referrer';
            image.addEventListener('error', () => image.remove());
            image.src = experience.image_url;
            media.append(image);
        }

        const content = document.createElement('div');
        content.className = 'discovery-assistant__card-content';

        if (experience.category) {
            const category = document.createElement('span');
            category.className = 'discovery-assistant__card-category';
            category.textContent = experience.category;
            content.append(category);
        }

        const heading = document.createElement('h3');
        heading.textContent = experience.name;
        content.append(heading);

        if (experience.location) {
            const location = document.createElement('p');
            location.className = 'discovery-assistant__card-location';
            location.textContent = `● ${experience.location}`;
            content.append(location);
        }

        const reason = document.createElement('p');
        reason.className = 'discovery-assistant__card-reason';
        reason.textContent = experience.reason;
        content.append(reason);

        if (experience.details) {
            const detailsList = document.createElement('dl');
            detailsList.className = 'discovery-assistant__details';
            Object.entries(experience.details).forEach(([label, value]) => {
                const item = document.createElement('div');
                const term = document.createElement('dt');
                const description = document.createElement('dd');
                term.textContent = label;
                description.textContent = value;
                item.append(term, description);
                detailsList.append(item);
            });
            content.append(detailsList);
        }

        const actions = document.createElement('div');
        actions.className = 'discovery-assistant__card-actions';
        const details = document.createElement('a');
        details.href = experience.details_url;
        details.textContent = 'View details';
        actions.append(details);

        if (experience.map_url) {
            const map = document.createElement('a');
            map.href = experience.map_url;
            map.textContent = 'View on map';
            actions.append(map);
        }

        content.append(actions);
        card.append(media, content);
        return card;
    };

    const updateSuggestions = (suggestions = []) => {
        prompts.replaceChildren();
        suggestions.slice(0, 4).forEach((suggestion) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.dataset.assistantPrompt = suggestion;
            button.textContent = suggestion;
            button.addEventListener('click', () => sendMessage(suggestion));
            prompts.append(button);
        });
    };

    const comparisonTable = (comparison) => {
        const wrapper = document.createElement('div');
        wrapper.className = 'discovery-assistant__comparison';
        comparison.forEach((experience) => {
            const column = document.createElement('section');
            const heading = document.createElement('h3');
            const list = document.createElement('dl');
            heading.textContent = experience.name;
            Object.entries(experience.attributes).forEach(([label, value]) => {
                const row = document.createElement('div');
                const term = document.createElement('dt');
                const description = document.createElement('dd');
                term.textContent = label;
                description.textContent = value;
                row.append(term, description);
                list.append(row);
            });
            column.append(heading, list);
            wrapper.append(column);
        });
        return wrapper;
    };

    const renderEntry = (entry) => {
        const bubble = messageBubble(String(entry.text || ''), entry.sender === 'user' ? 'user' : 'assistant');

        if (Array.isArray(entry.experiences) && entry.experiences.length) {
            const cards = document.createElement('div');
            cards.className = 'discovery-assistant__cards';
            entry.experiences.forEach((experience) => cards.append(experienceCard(experience)));
            bubble.append(cards);
            contextExperienceId = entry.experiences[0]?.id ?? contextExperienceId;
        }

        if (Array.isArray(entry.comparison) && entry.comparison.length === 2) {
            bubble.append(comparisonTable(entry.comparison));
        }

        return bubble;
    };

    const addEntry = (entry) => {
        history.push(entry);
        history = history.slice(-maxHistoryEntries);
        persistHistory();
        return renderEntry(entry);
    };

    const restoreHistory = () => {
        messages.replaceChildren();

        if (!history.length) {
            renderEntry({ sender: 'assistant', text: welcomeMessage });
            return;
        }

        history.forEach(renderEntry);
        messages.scrollTop = messages.scrollHeight;
    };

    const sendMessage = async (message) => {
        if (isSending) {
            return;
        }

        isSending = true;
        addEntry({ sender: 'user', text: message });
        input.value = '';
        submit.disabled = true;
        input.disabled = true;
        prompts.querySelectorAll('button').forEach((button) => { button.disabled = true; });
        messages.setAttribute('aria-busy', 'true');
        status.textContent = 'Thinking…';

        try {
            const response = await fetch(root.dataset.endpoint, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    message,
                    context_experience_id: contextExperienceId,
                }),
            });

            const payload = await response.json().catch(() => ({}));

            if (!response.ok) {
                const safeMessage = response.status === 422 && payload.message
                    ? payload.message
                    : 'The assistant could not answer that request.';

                throw new Error(safeMessage);
            }

            addEntry({
                sender: 'assistant',
                text: payload.message,
                experiences: payload.experiences || [],
                comparison: payload.comparison || [],
            });

            updateSuggestions(payload.suggestions);
        } catch (error) {
            addEntry({
                sender: 'assistant',
                text: error.message || 'The assistant is temporarily unavailable. Please try again.',
            });
        } finally {
            isSending = false;
            submit.disabled = false;
            input.disabled = false;
            prompts.querySelectorAll('button').forEach((button) => { button.disabled = false; });
            messages.removeAttribute('aria-busy');
            status.textContent = '';
            messages.scrollTop = messages.scrollHeight;
            input.focus();
        }
    };

    toggle.addEventListener('click', () => setOpen(panel.hidden));
    close.addEventListener('click', () => setOpen(false));
    reset.addEventListener('click', async () => {
        if (isSending) {
            return;
        }

        reset.disabled = true;

        try {
            await fetch(root.dataset.resetEndpoint, {
                method: 'DELETE',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken },
            });
            history = [];
            storage.remove(historyKey);
            messages.replaceChildren();
            messageBubble('Conversation cleared. What would you like to discover?', 'assistant');
            contextExperienceId = null;
            updateSuggestions(['Recommend for me', 'Heritage in Melaka', 'Arts & Crafts']);
        } finally {
            reset.disabled = false;
        }
    });
    root.querySelectorAll('[data-assistant-prompt]').forEach((button) => {
        button.addEventListener('click', () => sendMessage(button.dataset.assistantPrompt));
    });
    form.addEventListener('submit', (event) => {
        event.preventDefault();
        const message = input.value.trim();

        if (message) {
            sendMessage(message);
        }
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !panel.hidden) {
            setOpen(false);
            toggle.focus();
        }
    });

    restoreHistory();
    setOpen(storage.read(openKey, false) === true);
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeDiscoveryAssistant, { once: true });
} else {
    initializeDiscoveryAssistant();
}
