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
    let contextExperienceId = null;

    const setOpen = (isOpen) => {
        panel.hidden = !isOpen;
        toggle.setAttribute('aria-expanded', String(isOpen));

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
            image.src = experience.image_url;
            image.alt = '';
            image.loading = 'lazy';
            image.addEventListener('error', () => image.remove());
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

    const sendMessage = async (message) => {
        messageBubble(message, 'user');
        input.value = '';
        submit.disabled = true;
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

            const payload = await response.json();

            if (!response.ok) {
                throw new Error(payload.message || 'The assistant could not answer that request.');
            }

            const responseBubble = messageBubble(payload.message, 'assistant');

            if (payload.experiences?.length) {
                const cards = document.createElement('div');
                cards.className = 'discovery-assistant__cards';
                payload.experiences.forEach((experience) => cards.append(experienceCard(experience)));
                responseBubble.append(cards);
                contextExperienceId = payload.experiences[0].id;
            }

            if (payload.comparison?.length === 2) {
                responseBubble.append(comparisonTable(payload.comparison));
            }

            updateSuggestions(payload.suggestions);
        } catch (error) {
            messageBubble(error.message || 'Something went wrong. Please try again.', 'assistant');
        } finally {
            submit.disabled = false;
            status.textContent = '';
            messages.scrollTop = messages.scrollHeight;
            input.focus();
        }
    };

    toggle.addEventListener('click', () => setOpen(panel.hidden));
    close.addEventListener('click', () => setOpen(false));
    reset.addEventListener('click', async () => {
        reset.disabled = true;

        try {
            await fetch(root.dataset.resetEndpoint, {
                method: 'DELETE',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken },
            });
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
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeDiscoveryAssistant, { once: true });
} else {
    initializeDiscoveryAssistant();
}
