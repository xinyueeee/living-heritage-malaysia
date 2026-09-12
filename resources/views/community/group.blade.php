@extends('layouts.app')

@section('title', $group->group_name . ' | Community')

@section('content')

<div class="community-page">

    {{-- Group Header --}}
    <section class="community-hero">
        <div class="container community-hero-content">

            <div class="community-intro">

                <p class="community-eyebrow">
                    Community Group
                </p>

                <h1>
                    {{ $group->group_name }}
                </h1>

                @if($group->description)
                    <p>
                        {{ $group->description }}
                    </p>
                @endif

                <p style="margin-top: 10px;">
                    <strong>{{ $memberCount }}</strong>
                    {{ $memberCount == 1 ? 'Member' : 'Members' }}
                </p>

            </div>

            {{-- Join / Leave Button --}}
            <div>

                @auth

                    @if($isJoined)

                        <form
                            action="{{ route('community.group.leave', $group->group_id) }}"
                            method="POST"
                            style="display: inline;"
                        >
                            @csrf
                            @method('DELETE')

                            <button
                                type="submit"
                                class="create-post-btn"
                                style="cursor: pointer;"
                            >
                                Leave Group
                            </button>
                        </form>

                    @else

                        <form
                            action="{{ route('community.group.join', $group->group_id) }}"
                            method="POST"
                            style="display: inline;"
                        >
                            @csrf

                            <button
                                type="submit"
                                class="create-post-btn"
                                style="cursor: pointer;"
                            >
                                Join Group
                            </button>
                        </form>

                    @endif

                @endauth

            </div>

        </div>
    </section>


    {{-- Success Message --}}
    @if(session('success'))
        <div class="container">
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        </div>
    @endif


    {{-- Group Content --}}
    <div class="container community-content">

        @if($isJoined)

            {{-- Joined Status --}}
            <div class="community-feed-header">

                <div>
                    <h2>
                        Group Posts
                    </h2>

                    <p style="margin-top: 5px;">
                        Posts shared with
                        <strong>{{ $group->group_name }}</strong>
                    </p>
                </div>

            </div>


            {{-- Group Posts --}}
            <div class="community-feed">

                @forelse($posts as $post)

                    @include('community.partials.post-card', [
                        'post' => $post,
                        'isSaved' => in_array(
                            $post->post_id,
                            $savedPostIds ?? [],
                            true
                        ),
                    ])

                @empty

                    <div class="empty-feed">

                        <div class="empty-icon">
                            💬
                        </div>

                        <h2>
                            No Group Posts Yet
                        </h2>

                        <p>
                            There are no posts in this group yet.
                            Share a post from the Community page and
                            select this group.
                        </p>

                    </div>

                @endforelse

            </div>

        @else

            {{-- Not Joined --}}
            <div class="empty-feed">

                <div class="empty-icon">
                    👥
                </div>

                <h2>
                    Join This Group
                </h2>

                <p>
                    Join <strong>{{ $group->group_name }}</strong>
                    to view posts shared by group members.
                </p>

                <p style="margin-top: 10px;">
                    There are currently
                    <strong>{{ $memberCount }}</strong>
                    {{ $memberCount == 1 ? 'member' : 'members' }}
                    in this group.
                </p>

            </div>

        @endif

    </div>

</div>


{{-- Photo Viewer --}}
@include('community.partials.photo-viewer')

@endsection


@push('scripts')

    {{-- Photo Viewer --}}
    @include('community.partials.photo-viewer-script')

    {{-- Existing Community Functions --}}
    @vite([
        'resources/js/pages/community-save.js',
        'resources/js/pages/community-like.js',
        'resources/js/pages/community-comment.js'
    ])

@endpush