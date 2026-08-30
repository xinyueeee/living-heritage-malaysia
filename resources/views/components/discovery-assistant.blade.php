<section
    class="discovery-assistant"
    data-discovery-assistant
    data-endpoint="{{ route('discover-assistant.message') }}"
    data-reset-endpoint="{{ route('discover-assistant.reset') }}"
    aria-label="AI Cultural Discovery Assistant"
>
    <button
        class="discovery-assistant__toggle"
        type="button"
        aria-label="Open cultural discovery assistant"
        aria-expanded="false"
        aria-controls="discovery-assistant-panel"
    >
        <span class="discovery-assistant__toggle-icon" aria-hidden="true">✦</span>
        <span class="discovery-assistant__toggle-label">Ask Cultural Guide</span>
    </button>

    <div class="discovery-assistant__panel" id="discovery-assistant-panel" hidden>
        <header class="discovery-assistant__header">
            <div>
                <p class="discovery-assistant__eyebrow">Living Heritage Malaysia</p>
                <h2>Cultural Discovery Assistant</h2>
            </div>
            <div class="discovery-assistant__header-actions">
                <button class="discovery-assistant__reset" type="button" aria-label="Clear assistant conversation">Reset</button>
                <button class="discovery-assistant__close" type="button" aria-label="Close cultural assistant">×</button>
            </div>
        </header>

        <div class="discovery-assistant__messages" role="log" aria-live="polite" aria-relevant="additions">
            <div class="discovery-assistant__message discovery-assistant__message--assistant">
                <p>Hello! I can find Cultural Experiences, suggest ideas based on your interests, or explain a recommendation.</p>
            </div>
        </div>

        <div class="discovery-assistant__prompts" aria-label="Suggested questions">
            <button type="button" data-assistant-prompt="Heritage in Melaka">Heritage in Melaka</button>
            <button type="button" data-assistant-prompt="Recommend something for me">Recommend for me</button>
            <button type="button" data-assistant-prompt="Arts &amp; Crafts in Penang">Crafts in Penang</button>
        </div>

        <form class="discovery-assistant__form">
            <label class="sr-only" for="discovery-assistant-message">Ask about Malaysian cultural experiences</label>
            <input
                id="discovery-assistant-message"
                name="message"
                type="text"
                maxlength="500"
                autocomplete="off"
                placeholder="Try “Culinary in Melaka”"
                required
            >
            <button type="submit">Send</button>
        </form>
        <p class="discovery-assistant__status" data-assistant-status role="status" aria-live="polite"></p>
    </div>
</section>
