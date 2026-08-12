@extends('layouts.app')

@section('title', 'Community | Living Heritage Malaysia')

@section('content')

<div class="community-page">

    <!-- =========================
         HERO
    ========================== -->
    <section class="community-hero">
        <div class="container community-hero-content">

            <div class="community-intro">

                <p class="community-eyebrow">
                    Share. Inspire. Preserve.
                </p>

                <h1>Community</h1>

                <p>
                    Share your cultural experiences, connect with other travellers,
                    and inspire more people to explore Malaysia's living heritage.
                </p>

            </div>

            <div>
                <a href="{{ route('community.create') }}"
                   class="create-post-btn">
                    + Create Post
                </a>
            </div>

        </div>
    </section>


    <!-- =========================
         SUCCESS MESSAGE
    ========================== -->
    @if(session('success'))

        <div class="container">

            <div class="alert alert-success">
                {{ session('success') }}
            </div>

        </div>

    @endif


    <!-- =========================
         COMMUNITY FEED
    ========================== -->
    <div class="container community-content">

        <div class="community-feed">

            @forelse($posts as $post)

                <article class="post-card">

                    <!-- =========================
                         POST HEADER
                    ========================== -->
                    <div class="post-header">

                        <img
                            src="{{ asset('images/default-avatar.png') }}"
                            class="avatar"
                            alt="Avatar"
                        >

                        <div class="post-user">

                            <h4>
                                {{ $post->user->user_name ?? 'Anonymous' }}
                            </h4>

                            <small>
                                {{ \Carbon\Carbon::parse($post->created_at)->diffForHumans() }}

                                <span class="post-separator">•</span>

                                <span class="post-location">
                                    📍 {{ $post->location ?? 'Malaysia' }}
                                </span>
                            </small>

                        </div>

                        <!-- More button -->
                        <button type="button" class="post-more-btn">
                            ⋯
                        </button>

                    </div>


                    <!-- =========================
                         POST CAPTION
                    ========================== -->
                    <div class="post-caption">

                        {{ $post->content }}

                    </div>


                    <!-- =========================
                         POST IMAGE
                    ========================== -->

                    @if($post->post_images)

                        @php 
                            $images = json_decode($post->post_images, true);
                        @endphp

                        @if(is_array($images)&&count($images)>0)

                            @php
                                $totalImages = count($images);
                                $displayImages = array_slice($images, 0, 3);
                            @endphp

                            <div class="post-gallery post-gallery-{{ count($displayImages) }}">

                                @foreach($displayImages as $index => $image)

                                <div class="gallery-item">
                                    <img src="{{ asset('images/community/' . $image) }}"
                                    alt="Community Post Image">

                                    {{-- Show +X on the 3rd image if there are more photos --}}
                                    @if($index === 2 && $totalImages > 3)
                                    <div class="more-images">
                                        +{{ $totalImages - 3 }}
                                    </div>
                                    @endif

                                </div>

                                @endforeach
                            </div>
                        @endif

                    @endif


                    <!-- =========================
                         POST ACTIONS
                    ========================== -->

                    <div class="post-footer">

                        <button type="button" class="post-action like-action">

                            <span class="action-icon">
                                ❤️
                            </span>

                            <span>
                                {{ $post->like_count ?? 0 }}
                            </span>

                        </button>


                        <button type="button" class="post-action">

                            <span class="action-icon">
                                💬
                            </span>

                            <span>
                                0
                            </span>

                        </button>


                        <button type="button" class="post-action">

                            <span class="action-icon">
                                🔖
                            </span>

                            <span>
                                Save
                            </span>

                        </button>

                    </div>

                </article>


            @empty

                <!-- =========================
                     EMPTY FEED
                ========================== -->

                <div class="empty-feed">

                    <div class="empty-icon">
                        💬
                    </div>

                    <h2>No Posts Yet</h2>

                    <p>
                        Be the first to share your cultural experience
                        with the community.
                    </p>

                </div>

            @endforelse

        </div>

    </div>

</div>

@endsection