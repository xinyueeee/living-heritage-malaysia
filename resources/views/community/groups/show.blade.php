@extends('layouts.app')

@section('content')
<div class="container py-5 community-groups-page">

    {{-- Back --}}
    <div class="mb-4">
        <a href="{{ route('community.groups.index') }}" class="text-decoration-none" style="color: #8A3A2D; font-weight: 600; font-family: Georgia, serif;">
            ← Back to Community Groups
        </a>
    </div>

    {{-- Success Message --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Group Header --}}
    <div class="card shadow-sm mb-4" style="border: 1px solid #eee3dc; border-radius: 20px; overflow: hidden;">

        {{-- Cover Image --}}
        @if($group->cover_image)
            <img src="{{ asset($group->cover_image) }}" alt="{{ $group->name }}"
                style="width: 100%; height: 250px; object-fit: cover; border-top-left-radius: calc(0.25rem - 1px); border-top-right-radius: calc(0.25rem - 1px);">
        @else
            <div style="width: 100%; height: 250px; background: #f5eee8; display: flex; align-items: center; justify-content: center; color: #9a8980; border-top-left-radius: calc(0.25rem - 1px); border-top-right-radius: calc(0.25rem - 1px);">
                <span style="font-family: Georgia, serif;">No Cover Image</span>
            </div>
        @endif

        <div class="card-body" style="padding: 28px 32px;">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                {{-- Left: Group Info --}}
                <div>
                    <h1 style="margin: 0 0 8px; color: #2C1E16; font-family: Georgia, serif; font-size: clamp(1.8rem, 3vw, 2.6rem);">
                        {{ $group->name }}
                    </h1>
                    <p style="margin: 0 0 8px; color: #71645d; font-size: 1rem; line-height: 1.7;">
                        {{ $group->description }}
                    </p>
                    <div style="color: #81736b; font-size: 0.9rem; font-weight: 600;">
                        <span class="badge bg-secondary">{{ $group->members_count }} members</span>
                    </div>
                </div>

                {{-- Right: Action Buttons --}}
                <div class="group-action-buttons">
                    @auth
                        @if($group->is_joined)
                            {{-- ✅ 已加入 → Leave Group + Create Post --}}
                            <form action="{{ route('community.groups.leave', $group->group_id) }}" method="POST"
                                  onsubmit="return confirm('Are you sure you want to leave this community?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger">
                                    Leave Group
                                </button>
                            </form>

                            <a href="{{ route('community.create') }}" class="btn btn-create-post">
                                + Create Post
                            </a>
                        @else
                            {{-- ❌ 未加入 → 只显示 Join Group --}}
                            <form action="{{ route('community.groups.join', $group->group_id) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-primary">
                                    Join Group
                                </button>
                            </form>
                        @endif
                    @else
                        {{-- 未登录 --}}
                        <a href="{{ route('login') }}" class="btn btn-primary">
                            Login to Join
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </div>

    {{-- Group Posts Section --}}
    <div style="margin-bottom: 20px;">
        <h2 style="margin: 0; color: #2C1E16; font-family: Georgia, serif; font-size: 1.6rem; font-weight: 700;">
            Group Posts
        </h2>
    </div>

    @auth
        @if($group->is_joined)
            @if($posts->count() > 0)
                <div class="posts-container">
                    @foreach($posts as $post)
                        @include('community.partials.post-card', [
                            'post' => $post,
                            'savedPostIds' => $savedPostIds ?? [],
                            'isSaved' => in_array($post->post_id, $savedPostIds ?? [])
                        ])
                    @endforeach
                </div>
            @else
                <div class="card shadow-sm" style="border: 1px solid #eee3dc; border-radius: 18px;">
                    <div class="card-body text-center py-5">
                        <div style="font-size: 3rem; margin-bottom: 16px;">📝</div>
                        <h5 style="color: #2C1E16; font-family: Georgia, serif;">No posts yet</h5>
                        <p style="color: #81736b; margin-bottom: 16px;">
                            Be the first person to share something with this community.
                        </p>
                        <a href="{{ route('community.create') }}" class="btn btn-primary" 
                           style="background: #8A3A2D; border: none; border-radius: 10px; padding: 10px 28px; font-weight: 600; font-family: Georgia, serif; text-decoration: none; color: #fff;">
                            Create First Post
                        </a>
                    </div>
                </div>
            @endif
        @else
            <div class="card shadow-sm" style="border: 1px solid #eee3dc; border-radius: 18px;">
                <div class="card-body text-center py-5">
                    <div style="font-size: 3rem; margin-bottom: 16px;">🔒</div>
                    <h5 style="color: #2C1E16; font-family: Georgia, serif;">Join to see posts</h5>
                    <p style="color: #81736b; margin-bottom: 0;">
                        This community is private. Join to see posts from members.
                    </p>
                </div>
            </div>
        @endif
    @else
        <div class="card shadow-sm" style="border: 1px solid #eee3dc; border-radius: 18px;">
            <div class="card-body text-center py-5">
                <div style="font-size: 3rem; margin-bottom: 16px;">🔒</div>
                <h5 style="color: #2C1E16; font-family: Georgia, serif;">Please login to view posts</h5>
                <p style="color: #81736b; margin-bottom: 16px;">
                    Login to join this community and see posts from members.
                </p>
                <a href="{{ route('login') }}" class="btn btn-primary" 
                   style="background: #8A3A2D; border: none; border-radius: 10px; padding: 10px 28px; font-weight: 600; font-family: Georgia, serif; text-decoration: none; color: #fff;">
                    Login Now
                </a>
            </div>
        </div>
    @endauth

</div>
@endsection