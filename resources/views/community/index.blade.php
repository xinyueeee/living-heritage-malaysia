@extends('layouts.app')

@section('title', 'Community | Living Heritage Malaysia')

@section('content')

<div class="community-page">

    <!-- Hero -->
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

    <!-- Success Message -->
    @if(session('success'))
        <div class="container">
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        </div>
    @endif

    <!-- Feed -->
    <div class="container community-content">

        <div class="community-feed">

            @forelse($posts as $post)

                <div class="post-card">

                    <!--Header-->
                    <div class="post-header">
                        <img src="{{ asset('images/default-avatar.png') }}" class="avatar" alt="Avatar">

                        <div class="post-user">
                            <h4>{{ $post->user->user_name ?? 'Anonymous' }}</h4>

                            <small>
                                {{ \Carbon\Carbon::parse($post->created_at)->diffForHumans() }}
                            </small>
                            
                        </div>
                    </div>

                    <!--Caption-->
                    <div class="post-caption">
                        {{ $post->content }}
                    </div>

                    <!--Image-->
                    @if($post->post_images)
                    <img  src="{{ asset('images/community/'.$post->post_images) }}" class="post-image">

                    @endif

                    <!--Footer-->
                    <div class="post-footer">
                        <span>❤️ {{ $post->like_count ?? 0 }}</span>
                        <span>💬 0</span>
                        <span>🔖 Save</span>
                    </div>

                </div>

            @empty

                <div class="empty-feed">

                    <h2>No Posts Yet</h2>

                    <p>
                        Be the first to share your cultural experience with the community.
                    </p>

                </div>

            @endforelse

        </div>

    </div>

</div>

@endsection