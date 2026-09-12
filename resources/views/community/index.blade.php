@extends('layouts.app')

@section('title', 'Community | Living Heritage Malaysia')

@section('content')

<div class="community-page">
    <section 
        class="community-hero"
        style="--community-hero-image: url('{{ asset('images/community/community-hero.png') }}')">
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
                <a
                    href="{{ route('community.create') }}"
                    class="create-post-btn"
                >
                    + Create Post
                </a>
            </div>
        </div>
    </section>

    <!-- ===================================================
         SUCCESS MESSAGE
    =================================================== -->

    @if(session('success'))
        <div class="container">
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        </div>
    @endif

    @if (session('status'))
        <div class="container">
            <div class="alert alert-success">
                {{ session('status') }}
            </div>
        </div>
    @endif


    <!-- ===================================================
         COMMUNITY FEED
    =================================================== -->

    <div class="container community-content">

        <!-- ===================================================
             FEED HEADER
        =================================================== -->

        <div class="community-feed-header">
            <h2>
                Latest Community Posts
            </h2>
        </div>

        <!-- ===================================================
             TAB NAVIGATION - 只显示用户加入的小组
        =================================================== -->

        @auth
            @if($userGroups->count() > 0)
                <div class="community-tabs">
                    <a href="{{ route('community.index') }}" 
                       class="community-tab {{ !$selectedGroupId ? 'active' : '' }}">
                        All Posts
                    </a>
                    
                    @foreach($userGroups as $group)
                        <a href="{{ route('community.index', ['group_id' => $group->group_id]) }}" 
                           class="community-tab {{ $selectedGroupId == $group->group_id ? 'active' : '' }}">
                            {{ $group->name }}
                        </a>
                    @endforeach
                </div>
            @endif
        @endauth

        <div class="community-feed">

            @forelse($posts as $post)
                @include('community.partials.post-card', [
                    'post' => $post,
                    'isSaved' => in_array($post->post_id, $savedPostIds ?? [], true),
                ])
            @empty
                <!-- ===================================================
                     EMPTY FEED
                =================================================== -->

                <div class="empty-feed">
                    <div class="empty-icon">💬</div>
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

@include('community.partials.photo-viewer')

@endsection



<!-- ===================================================
     JAVASCRIPT
=================================================== -->

@push('scripts')
    @include('community.partials.photo-viewer-script')
    @vite([
        'resources/js/pages/community-save.js',
        'resources/js/pages/community-like.js',
        'resources/js/pages/community-comment.js'
    ])
@endpush