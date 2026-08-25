@extends('layouts.app')

@section('title', 'Saved Experiences - Living Heritage Malaysia')

@section('content')
    <section class="profile-hero">
        <div class="container profile-hero-content">
            <h1>Saved Experiences</h1>
            <p>Keep track of the cultural experiences and festivals you want to explore.</p>
        </div>
    </section>

    <div class="container profile-layout">
        @include('profile.partials.sidebar', ['active' => 'saved-experiences'])

        <div>
            @if (session('status'))
                <p class="profile-saved-status" role="status">{{ session('status') }}</p>
            @endif

            @if ($errors->any())
                <div class="form-error" role="alert">{{ $errors->first() }}</div>
            @endif

            <div class="saved-collections-toolbar">
                <nav class="saved-collection-tabs" aria-label="Saved experience collections">
                    <a @class(['active' => $selected === 'all']) href="{{ route('profile.saved-experiences', ['collection' => 'all']) }}">All ({{ $allCount }})</a>
                    <a @class(['active' => $selected === 'default']) href="{{ route('profile.saved-experiences', ['collection' => 'default']) }}">Default ({{ $defaultCount }})</a>
                    @foreach ($collections as $collection)
                        <a @class(['active' => $selected === (string) $collection->collection_id]) href="{{ route('profile.saved-experiences', ['collection' => $collection->collection_id]) }}">{{ $collection->name }} ({{ $collection->saved_experiences_count }})</a>
                    @endforeach
                </nav>
                <details class="saved-collection-manage">
                    <summary>Manage Collections</summary>
                    <div class="saved-collection-panel">
                        <form method="POST" action="{{ route('saved-experience-collections.store') }}" class="saved-collection-create">
                            @csrf
                            <label for="new-collection-name">New collection</label>
                            <div><input id="new-collection-name" name="name" maxlength="80" required placeholder="e.g. Weekend Plans"><button class="button button-primary">Create</button></div>
                        </form>
                        @foreach ($collections as $collection)
                            <div class="saved-collection-manage-row">
                                <form method="POST" action="{{ route('saved-experience-collections.update', $collection) }}">
                                    @csrf @method('PATCH')
                                    <input name="name" value="{{ $collection->name }}" maxlength="80" required aria-label="Rename {{ $collection->name }}">
                                    <button class="button">Rename</button>
                                </form>
                                <form method="POST" action="{{ route('saved-experience-collections.destroy', $collection) }}" onsubmit="return confirm('Delete &quot;{{ addslashes($collection->name) }}&quot;? {{ $collection->saved_experiences_count }} saved experience(s) will be moved to Default.');">
                                    @csrf @method('DELETE')
                                    <button class="saved-collection-delete">Delete</button>
                                </form>
                            </div>
                        @endforeach
                        <p class="saved-default-note">Default is permanent and cannot be renamed or deleted.</p>
                    </div>
                </details>
            </div>

            @if ($experiences->isEmpty())
                <div class="profile-card profile-saved-empty">
                    <span aria-hidden="true">♡</span>
                    <h2>You haven't saved any experiences yet.</h2>
                    <p>Explore Malaysia's living heritage and use the heart button to save experiences here.</p>
                    <a class="button button-primary" href="{{ route('experiences.index') }}">Explore Experiences</a>
                </div>
            @else
                @php($savedExperienceIds = $experiences->pluck('experiences_id')->map(fn ($id) => (int) $id)->all())
                <div class="profile-saved-grid">
                    @foreach ($experiences as $experience)
                        <div class="saved-experience-item">
                            @include('components.experience-card', ['experience' => $experience, 'savedExperienceIds' => $savedExperienceIds, 'hideFavourite' => true])
                            <div class="saved-experience-actions">
                                <span>Saved in <strong>{{ $experience->pivot->collection_id ? ($collections->firstWhere('collection_id', $experience->pivot->collection_id)?->name ?? 'Default') : 'Default' }}</strong></span>
                                <form method="POST" action="{{ route('experiences.saved.move', $experience) }}">
                                    @csrf @method('PATCH')
                                    <label class="sr-only" for="move-{{ $experience->experiences_id }}">Move to collection</label>
                                    <select id="move-{{ $experience->experiences_id }}" name="collection_id">
                                        <option value="">Default</option>
                                        @foreach ($collections as $collection)
                                            <option value="{{ $collection->collection_id }}" @selected((int) $experience->pivot->collection_id === (int) $collection->collection_id)>{{ $collection->name }}</option>
                                        @endforeach
                                    </select>
                                    <button class="button">Move</button>
                                </form>
                                <button type="button" class="saved-remove-button" data-open-remove-saved
                                    data-remove-url="{{ route('experiences.saved.destroy', $experience) }}"
                                    data-experience-name="{{ $experience->experiences_name }}">Remove</button>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{ $experiences->onEachSide(1)->links('components.pagination') }}
            @endif
        </div>
    </div>
@endsection
