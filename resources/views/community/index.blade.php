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
                <a href="{{ route('community.create') }}" class="create-post-btn"> +Create Post</a>
            </div>
        </div>
    </section>

    <!-- Feed -->
    <div class="container community-content">
        <div class="community-feed">
            <div class="empty-feed">
                <h2>No Posts Yet</h2>
                <p>
                    Be the first to share your cultural experience with the community.
                </p>
            </div>
        </div>
    </div>
</div>

@endsection