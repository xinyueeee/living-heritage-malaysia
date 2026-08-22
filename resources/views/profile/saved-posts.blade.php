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
                <div class="saved-posts-grid-tiles">
                    @foreach ($posts as $post)
                        @php
                            $postImages = $post->post_images ? json_decode($post->post_images, true) : null;
                            $firstImage = is_array($postImages) && count($postImages) > 0 ? $postImages[0] : null;
                        @endphp
                        <button
                            type="button"
                            class="saved-posts-grid-item"
                            data-post-id="{{ $post->post_id }}"
                            data-detail-target="post-detail-{{ $post->post_id }}"
                            aria-label="View post"
                        >
                            @if ($firstImage)
                                <img src="{{ $firstImage }}" alt="">
                            @else
                                <span class="saved-posts-grid-textonly">{{ \Illuminate\Support\Str::limit($post->content, 90) }}</span>
                            @endif

                            <span class="saved-posts-grid-overlay">
                                <span>♥ {{ $post->like_count ?? 0 }}</span>
                            </span>
                        </button>
                    @endforeach
                </div>

                {{ $posts->onEachSide(1)->links('components.pagination') }}
            </div>
        </div>
    </div>

    @foreach ($posts as $post)
        <template id="post-detail-{{ $post->post_id }}">
            @include('community.partials.post-card', [
                'post' => $post,
                'isSaved' => true,
            ])
        </template>
    @endforeach

    <div id="postDetailModal" class="post-detail-modal">
        <div class="post-detail-modal-content">
            <button type="button" class="post-detail-modal-close" id="postDetailModalClose" aria-label="Close">&times;</button>
            <div id="postDetailModalBody"></div>
        </div>
    </div>

    @include('community.partials.photo-viewer')
@endsection

@push('scripts')
    @include('community.partials.photo-viewer-script')
    @vite(['resources/js/pages/community-save.js', 'resources/js/pages/community-like.js', 'resources/js/pages/post-detail-modal.js'])
@endpush
