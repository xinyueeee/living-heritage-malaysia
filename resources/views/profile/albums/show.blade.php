@extends('layouts.app')

@section('title', $album->album_name . ' - My Albums')

@push('styles')
    @vite('resources/css/albums.css')
@endpush

@section('content')

<div class="album-page">

    <div class="album-detail-header">

        <a href="{{ route('profile.albums.index') }}" class="album-back-link">
            ← Back to My Albums
        </a>

        <div class="album-detail-title">
            <p class="album-eyebrow">MY ALBUM</p>

            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
                <div>
                    <h1>{{ $album->album_name }}</h1>
                    
                    @if($album->description)
                        <p>{{ $album->description }}</p>
                    @endif

                    <span class="album-photo-count">
                        {{ $photos->count() }}
                        {{ $photos->count() == 1 ? 'photo' : 'photos' }}
                    </span>

                    <!-- Privacy Badge -->
                    @if(isset($album->privacy))
                        <span style="display: inline-block; margin-left: 12px; padding: 4px 12px; border-radius: 12px; font-size: 0.75rem; font-weight: 600; background: #f0f0f0;">
                            @if($album->privacy === 'private') 🔒 Private @endif
                            @if($album->privacy === 'shared') 👥 Shared @endif
                            @if($album->privacy === 'public') 🌍 Public @endif
                        </span>
                    @endif
                </div>

                <!-- Action Buttons -->
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <a href="{{ route('profile.albums.photos.create', $album->album_id) }}" class="album-primary-btn">
                        + Add Photos
                    </a>

                    <a href="{{ route('profile.albums.edit', $album->album_id) }}" class="album-secondary-btn">
                        Edit Album
                    </a>

                    <form action="{{ route('profile.albums.destroy', $album->album_id) }}" method="POST" style="display: inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="album-secondary-btn" style="border-color: #dc3545; color: #dc3545;" onclick="return confirm('Are you sure you want to delete this album?')">
                            Delete Album
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </div>

    @if(session('success'))
        <div class="album-success-message">
            {{ session('success') }}
        </div>
    @endif

    @if($photos->isEmpty())

        <div class="album-empty">
            <div class="album-empty-icon">📷</div>
            <h2>This album is empty</h2>
            <p>Photos added to this album will appear here.</p>

            <a href="{{ route('profile.albums.photos.create', $album->album_id) }}" class="album-primary-btn">
                + Add Your First Photo
            </a>
        </div>

    @else

        <div class="album-photo-grid">
            @foreach($photos as $photo)  
                <div class="album-photo-item" style="position: relative;">
                    <img
                        src="{{ $photo->photo_url }}"
                        alt="{{ $album->album_name }}"
                        style="width: 100%; height: 100%; object-fit: cover;"
                    >
                    
                    
                    @if($album->cover_photo_url === $photo->photo_url)
                        <span style="position: absolute; top: 8px; left: 8px; background: rgba(0,0,0,0.7); color: #FFD700; padding: 4px 10px; border-radius: 12px; font-size: 0.7rem; font-weight: 600; display: flex; align-items: center; gap: 4px;">
                            ⭐ Cover
                        </span>
                    @endif

                    <!-- Delete Photo Button -->
                    <form 
                        action="{{ route('profile.albums.photos.destroy', ['album' => $album->album_id, 'photo' => $photo->album_photo_id]) }}" 
                        method="POST" 
                        style="position: absolute; top: 8px; right: 8px;"
                    >
                        @csrf
                        @method('DELETE')
                        <button 
                            type="submit" 
                            style="background: rgba(220, 53, 69, 0.85); border: none; color: white; width: 30px; height: 30px; border-radius: 50%; cursor: pointer; font-size: 16px; display: flex; align-items: center; justify-content: center; transition: all 0.2s ease;"
                            onmouseover="this.style.transform='scale(1.1)'"
                            onmouseout="this.style.transform='scale(1)'"
                            onclick="return confirm('Delete this photo?')"
                        >
                            ×
                        </button>
                    </form>

                    <!-- Set as Cover Button - ✅ Only show if NOT the cover -->
                    @if($album->cover_photo_url !== $photo->photo_url)
                        <form 
                            action="{{ route('profile.albums.cover.update', ['album' => $album->album_id, 'photo' => $photo->album_photo_id]) }}" 
                            method="POST" 
                            style="position: absolute; bottom: 8px; left: 8px;"
                        >
                            @csrf
                            @method('PATCH')
                            <button 
                                type="submit" 
                                style="background: rgba(0,0,0,0.6); border: none; color: white; padding: 4px 10px; border-radius: 12px; cursor: pointer; font-size: 0.7rem; transition: all 0.2s ease;"
                                onmouseover="this.style.background='rgba(0,0,0,0.8)'"
                                onmouseout="this.style.background='rgba(0,0,0,0.6)'"
                            >
                                Set as Cover
                            </button>
                        </form>
                    @endif
                </div>
            @endforeach
        </div>

    @endif

</div>

@endsection