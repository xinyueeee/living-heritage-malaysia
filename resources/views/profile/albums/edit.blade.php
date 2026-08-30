@extends('layouts.app')

@section('title', 'Edit ' . $album->album_name . ' - Living Heritage Malaysia')

@push('styles')
    @vite('resources/css/albums.css')
@endpush

@section('content')

<div class="album-page album-create-page">

    <div class="album-form-card">

        <a href="{{ route('profile.albums.show', $album->album_id) }}" class="album-back-link">
            ← Back to {{ $album->album_name }}
        </a>

        <div class="album-form-header">
            <p class="album-eyebrow">EDIT ALBUM</p>

            <h1>Edit Album</h1>

            <p>
                Update your album name or description.
            </p>
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
            action="{{ route('profile.albums.update', $album->album_id) }}"
            method="POST"
            class="album-form"
        >
            @csrf
            @method('PUT')

            <!-- Album Name -->
            <div class="album-form-group">

                <label for="album_name">
                    Album Name <span class="required-asterisk">*</span>
                </label>

                <input
                    type="text"
                    id="album_name"
                    name="album_name"
                    value="{{ old('album_name', $album->album_name) }}"
                    placeholder="e.g. Melaka Heritage Trip"
                    required
                    maxlength="100"
                    autofocus
                    class="@error('album_name') is-invalid @enderror"
                    aria-describedby="album_name_help album_name_error"
                >

                @error('album_name')
                    <span
                        id="album_name_error"
                        class="album-error-text"
                        role="alert"
                    >
                        {{ $message }}
                    </span>
                @enderror

                <small id="album_name_help" class="album-help-text">
                    Maximum 100 characters
                </small>

            </div>

            <!-- Description -->
            <div class="album-form-group">

                <label for="description">
                    Description
                </label>

                <textarea
                    id="description"
                    name="description"
                    rows="5"
                    maxlength="1000"
                    placeholder="Tell us something about this album..."
                    class="@error('description') is-invalid @enderror"
                    aria-describedby="description_help description_error"
                >{{ old('description', $album->description) }}</textarea>

                @error('description')
                    <span
                        id="description_error"
                        class="album-error-text"
                        role="alert"
                    >
                        {{ $message }}
                    </span>
                @enderror

                <small id="description_help" class="album-help-text">
                    Maximum 1000 characters
                </small>

            </div>

            <!-- Actions -->
            <div class="album-form-actions">

                <a
                    href="{{ route('profile.albums.show', $album->album_id) }}"
                    class="album-secondary-btn"
                >
                    Cancel
                </a>

                <button
                    type="submit"
                    class="album-primary-btn"
                >
                    Save Changes
                </button>

            </div>

        </form>

    </div>

</div>

@endsection