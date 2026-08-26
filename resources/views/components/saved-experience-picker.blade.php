<dialog class="saved-picker" data-saved-picker data-create-url="{{ route('saved-experience-collections.store') }}">
    <form method="POST" data-save-form>
        @csrf
        <div class="saved-picker-heading">
            <div><p class="eyebrow">Saved Experiences</p><h2>Save to a collection</h2></div>
            <button type="button" class="saved-picker-close" data-picker-close aria-label="Close">&times;</button>
        </div>
        <div class="saved-picker-options" data-picker-options>
            <label><input type="radio" name="collection_id" value="" checked> <span>Default</span></label>
            @foreach ($collections as $collection)
                <label><input type="radio" name="collection_id" value="{{ $collection->collection_id }}"> <span>{{ $collection->name }}</span></label>
            @endforeach
        </div>
        <button type="button" class="saved-picker-new-toggle" data-new-collection-toggle>+ Create New Collection</button>
        <div class="saved-picker-new" data-new-collection hidden>
            <label for="picker-collection-name">Collection name</label>
            <div><input id="picker-collection-name" type="text" maxlength="80" data-new-collection-name><button type="button" class="button button-primary" data-create-collection>Create</button></div>
            <p class="profile-field-error" data-picker-error hidden></p>
        </div>
        <div class="saved-picker-actions">
            <button type="button" class="button saved-picker-cancel" data-picker-close>Cancel</button>
            <button type="submit" class="button button-primary">Save</button>
        </div>
    </form>
</dialog>

<dialog class="saved-picker saved-message-dialog" data-already-saved-dialog aria-labelledby="already-saved-title">
    <div class="saved-picker-message">
        <div class="saved-picker-heading">
            <h2 id="already-saved-title">Already saved</h2>
            <button type="button" class="saved-picker-close" data-already-saved-close aria-label="Close">&times;</button>
        </div>
        <p data-already-saved-message>This experience is already saved.</p>
        <p class="saved-picker-supporting">You can manage or move it from Saved Experiences.</p>
        <div class="saved-picker-actions">
            <button type="button" class="button saved-picker-cancel" data-already-saved-close>Close</button>
            <a class="button button-primary" href="{{ route('profile.saved-experiences') }}">View Saved Experiences</a>
        </div>
    </div>
</dialog>

<dialog class="saved-picker saved-message-dialog" data-remove-saved-dialog aria-labelledby="remove-saved-title">
    <form method="POST" data-remove-saved-form>
        @csrf
        @method('DELETE')
        <div class="saved-picker-heading">
            <h2 id="remove-saved-title">Remove saved experience?</h2>
            <button type="button" class="saved-picker-close" data-remove-saved-close aria-label="Close">&times;</button>
        </div>
        <p data-remove-saved-message>Are you sure you want to remove this experience from your saved experiences?</p>
        <div class="saved-picker-actions">
            <button type="button" class="button saved-picker-cancel" data-remove-saved-close>Cancel</button>
            <button type="submit" class="button button-primary">Remove</button>
        </div>
    </form>
</dialog>
