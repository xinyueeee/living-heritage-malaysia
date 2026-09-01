@extends('layouts.app')

@section('title', 'My Albums - Living Heritage Malaysia')

@push('styles')
    @vite('resources/css/albums.css')
@endpush

@section('content')

<div class="album-page">
    <a href="{{ route('profile') }}" class="album-back-link">
        ← Back to Profile
    </a>

    <div class="album-page-header">
        <div>
            
            <p class="album-eyebrow">MY PROFILE</p>
            <h1>My Albums</h1>
            <p>Keep your favourite cultural memories organised in your own albums.</p>
        </div>

        <a href="{{ route('profile.albums.create') }}" class="album-primary-btn">
            + Create Album
        </a>
    </div>

    @if(session('success'))
        <div class="album-success-message">
            {{ session('success') }}
        </div>
    @endif

    @if($albums->isEmpty())

        <div class="album-empty">
            <div class="album-empty-icon">📷</div>
            <h2>No albums yet</h2>
            <p>Create your first album and start collecting your favourite memories.</p>

            <a href="{{ route('profile.albums.create') }}" class="album-primary-btn">
                Create Your First Album
            </a>
        </div>

    @else

        <div class="album-grid">

            @foreach($albums as $album)

                <a href="{{ route('profile.albums.show', $album->album_id) }}" class="album-card">

                    <div class="album-cover">

                        @if($album->cover_photo_url)
                            <img
                                src="{{ $album->cover_photo_url }}"
                                alt="{{ $album->album_name }}"
                            >
                        @else
                            <div class="album-cover-placeholder">
                                <span>📷</span>
                            </div>
                        @endif

                        <!-- Privacy Badge -->
                        @if(isset($album->privacy) && $album->privacy !== 'public')
                            <span style="position: absolute; top: 10px; right: 10px; background: rgba(0,0,0,0.7); color: white; padding: 4px 10px; border-radius: 12px; font-size: 0.7rem; font-weight: 600;">
                                @if($album->privacy === 'private') 🔒 Private @endif
                                @if($album->privacy === 'shared') 👥 Shared @endif
                            </span>
                        @endif

                    </div>

                    <div class="album-card-content">
                        <h2>{{ $album->album_name }}</h2>

                        @if($album->description)
                            <p>{{ $album->description }}</p>
                        @endif

                        <span class="album-photo-count">
                            {{ $album->photos_count ?? 0 }}
                            {{ ($album->photos_count ?? 0) == 1 ? 'photo' : 'photos' }}
                        </span>
                    </div>

                </a>

            @endforeach

        </div>

    @endif

</div>

@endsection