@extends('layouts.app')

@section('title', 'Saved Posts - Living Heritage Malaysia')

@section('content')
    <section class="profile-hero">
        <div class="container profile-hero-content">
            <h1>Saved Posts</h1>
            <p>Community posts you've bookmarked to come back to later.</p>
        </div>
    </section>

    <div class="container profile-layout">
        @include('profile.partials.sidebar', ['active' => 'saved-posts'])

        <div>
            @if (session('status'))
                <p class="profile-saved-status" role="status">{{ session('status') }}</p>
            @endif

            <div class="profile-card profile-saved-empty" id="saved-posts-empty" @if (! $posts->isEmpty()) hidden @endif>
                <span aria-hidden="true">🔖</span>
                <h2>You haven't saved any posts yet.</h2>
                <p>Browse the Community feed and use the Save button to bookmark posts here.</p>
                <a class="button button-primary" href="{{ route('community.index') }}">Browse Community</a>
            </div>

            <div id="saved-posts-grid" @if ($posts->isEmpty()) hidden @endif>
                <div class="community-posts">
                    @foreach ($posts as $post)
                        @include('community.partials.post-card', [
                            'post' => $post,
                            'isSaved' => true,
                        ])
                    @endforeach
                </div>

                {{ $posts->onEachSide(1)->links('components.pagination') }}
            </div>
        </div>
    </div>

    @include('community.partials.photo-viewer')
@endsection

@push('scripts')
    @include('community.partials.photo-viewer-script')
    @vite(['resources/js/pages/community-save.js'])
@endpush
