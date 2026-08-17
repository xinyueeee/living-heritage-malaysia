@extends('layouts.app')

@section('title', 'My Posts - Living Heritage Malaysia')

@section('content')

    <section class="profile-hero">
        <div class="container profile-hero-content">
            <h1>My Posts</h1>
            <p>View the posts you have shared with the community.</p>
        </div>
    </section>

    <div class="container profile-layout">

        @include('profile.partials.sidebar', ['active' => 'my-posts'])

        <div>

            @if (session('status'))
                <div class="alert alert-success">{{ session('status') }}</div>
            @endif

            @if ($posts->isEmpty())

                <div class="profile-card profile-saved-empty">
                    <span aria-hidden="true">✎</span>

                    <h2>You haven't posted anything yet.</h2>

                    <p>
                        Share your cultural experiences with the community.
                    </p>

                    <a class="button button-primary" href="{{ route('community.create') }}">
                        Create Post
                    </a>
                </div>

            @else

                <div class="community-posts">

                    @foreach ($posts as $post)

                        @include('community.partials.post-card', [
                            'post' => $post,
                            'isSaved' => in_array($post->post_id, $savedPostIds ?? [], true),
                        ])

                    @endforeach

                </div>

            @endif

        </div>

    </div>

    @include('community.partials.photo-viewer')

@endsection

@push('scripts')
    @include('community.partials.photo-viewer-script')
    @vite(['resources/js/pages/community-save.js'])
@endpush
