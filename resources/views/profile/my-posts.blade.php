@extends('layouts.app')

@section('title', 'My Posts - Living Heritage Malaysia')

@section('content')

    <section
        class="profile-hero-photo"
        style="background-image: url('{{ asset('images/profile/profile-hero.png') }}');"
    >
        <div class="profile-hero-photo-overlay"></div>

        <div class="container profile-hero-photo-content">
            <h1>My Posts</h1>
            <p>View the posts you have shared with the community.</p>
        </div>
    </section>

    <div class="container profile-layout">

        @include('profile.partials.sidebar', ['active' => 'my-posts'])

        <div>

            @if (session('status'))
                <div class="alert alert-success">
                    {{ session('status') }}
                </div>
            @endif

            {{-- Community Group Filter --}}
            <div class="form-group" style="margin-bottom: 20px;">

                <div class="form-label-row">
                    <label for="group">
                        Filter by Community
                    </label>
                </div>

                <div class="experience-dropdown">

                    <form
                        method="GET"
                        action="{{ route('profile.my-posts') }}"
                    >

                        <select
                            id="group"
                            name="group"
                            onchange="this.form.submit()"
                        >

                            <option
                                value="all"
                                @selected(($groupFilter ?? 'all') === 'all')
                            >
                                All Communities
                            </option>

                            <option
                                value="community"
                                @selected(($groupFilter ?? 'all') === 'community')
                            >
                                Community
                            </option>

                            @foreach ($joinedGroups as $group)

                                <option
                                    value="{{ $group->group_id }}"
                                    @selected(
                                        (string) ($groupFilter ?? 'all')
                                        ===
                                        (string) $group->group_id
                                    )
                                >
                                    {{ $group->name }}
                                </option>

                            @endforeach

                        </select>

                    </form>

                </div>

            </div>

            {{-- Posts --}}
            @if ($posts->isEmpty())

                <div class="profile-card profile-saved-empty">

                    <span aria-hidden="true">✎</span>

                    <h2>No posts found.</h2>

                    <p>
                        There are no posts matching the selected community.
                    </p>

                    <a
                        class="button button-primary"
                        href="{{ route('community.create') }}"
                    >
                        Create Post
                    </a>

                </div>

            @else

                <div class="community-posts">

                    @foreach ($posts as $post)

                        @include('community.partials.post-card', [
                            'post' => $post,
                            'isSaved' => in_array(
                                $post->post_id,
                                $savedPostIds ?? [],
                                true
                            ),
                            'fromProfile' => true,
                            'showPrice' => true,
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

    @vite([
        'resources/js/pages/community-save.js',
        'resources/js/pages/community-like.js',
        'resources/js/pages/community-comment.js'
    ])

@endpush