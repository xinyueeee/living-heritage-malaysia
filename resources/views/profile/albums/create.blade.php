@extends('layouts.app')

@section('title', 'Create Album - Living Heritage Malaysia')

@push('styles')
    @vite('resources/css/albums.css')
@endpush

@section('content')

<div class="album-page album-create-page">

    <div class="album-form-card">

        <a href="{{ route('profile.albums.index') }}" class="album-back-link">
            ← Back to My Albums
        </a>

        <div class="album-form-header">
            <p class="album-eyebrow">MY ALBUMS</p>
            <h1>Create a New Album</h1>
            <p>Give your memories a name and start building your collection.</p>
        </div>

        @if($errors->any())
            <div class="album-error-message" role="alert">
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form
            action="{{ route('profile.albums.store') }}"
            method="POST"
            class="album-form"
            novalidate
        >
            @csrf

            <div class="album-form-group">
                <label for="album_name">
                    Album Name <span class="required-asterisk">*</span>
                </label>

                <input
                    type="text"
                    id="album_name"
                    name="album_name"
                    value="{{ old('album_name') }}"
                    placeholder="e.g. Melaka Heritage Trip"
                    required
                    maxlength="100"
                    autofocus
                    class="@error('album_name') is-invalid @enderror"
                    aria-describedby="album_name_help album_name_error"
                >

                @error('album_name')
                    <span id="album_name_error" class="album-error-text" role="alert">
                        {{ $message }}
                    </span>
                @enderror

                <small id="album_name_help" class="album-help-text">
                    Maximum 100 characters
                </small>
            </div>

            <div class="album-form-group">
                <label for="description">Description</label>

                <textarea
                    id="description"
                    name="description"
                    rows="5"
                    maxlength="500"
                    placeholder="Tell us something about this album..."
                    class="@error('description') is-invalid @enderror"
                    aria-describedby="description_help description_error"
                >{{ old('description') }}</textarea>

                @error('description')
                    <span id="description_error" class="album-error-text" role="alert">
                        {{ $message }}
                    </span>
                @enderror

                <small id="description_help" class="album-help-text">
                    Maximum 500 characters
                </small>
            </div>

            <!-- Privacy Option -->
            <div class="album-form-group">
                <label for="privacy">Privacy</label>
                <select
                    id="privacy"
                    name="privacy"
                    class="@error('privacy') is-invalid @enderror"
                >
                    <option value="private" {{ old('privacy') == 'private' ? 'selected' : '' }}>
                        🔒 Private (Only you)
                    </option>
                    <option value="shared" {{ old('privacy') == 'shared' ? 'selected' : '' }}>
                        👥 Shared (People with link)
                    </option>
                    <option value="public" {{ old('privacy') == 'public' ? 'selected' : '' }}>
                        🌍 Public (Everyone)
                    </option>
                </select>
                <small class="album-help-text">
                    Choose who can see your album.
                </small>
                @error('privacy')
                    <span class="album-error-text">{{ $message }}</span>
                @enderror
            </div>

            <div class="album-form-actions">
                <a
                    href="{{ route('profile.albums.index') }}"
                    class="album-secondary-btn"
                >
                    Cancel
                </a>

                <button type="submit" class="album-primary-btn">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                        <polyline points="17 21 17 13 7 13 7 21"/>
                        <polyline points="7 3 7 8 15 8"/>
                    </svg>
                    Create Album
                </button>
            </div>

        </form>

    </div>

</div>

@endsection