{{-- Expects: $post (App\Models\Post), $isSaved (bool) --}}
<article class="post-card">

    <!-- ===================================================
         POST HEADER
    =================================================== -->

    <div class="post-header">

        <!-- Avatar -->

        <img
            src="{{ $post->user->profile_photo ?? asset('images/default-avatar.png') }}"
            class="avatar"
            alt="Avatar"
        >

        <!-- User Information -->

        <div class="post-user">

            <h4>
                {{ $post->user->user_name ?? 'Anonymous' }}
            </h4>

            <small>

                {{ \Carbon\Carbon::parse($post->created_at)->diffForHumans() }}

                <span class="post-separator">
                    ·
                </span>

                <span class="post-location">

                    {{ $post->experience?->location_name ?? 'Malaysia' }}

                </span>

            </small>

        </div>

        <!-- More Button -->

        <button
            type="button"
            class="post-more-btn"
            aria-label="More options"
        >
            ⋯
        </button>

    </div>



    <!-- ===================================================
         POST BODY
    =================================================== -->

    <div class="post-body">

        <!-- ===================================================
             EXPERIENCE INFORMATION
        =================================================== -->

        @if($post->experience)

            <div class="post-experience-info">

                <!-- Experience Title -->

                <h3 class="post-experience-name">

                    {{ $post->experience->experiences_name }}

                </h3>

                <!-- Experience Meta -->

                <div class="post-experience-meta">

                    @if($post->experience->location_name)

                        <span class="experience-meta-item">

                            📍
                            {{ $post->experience->location_name }}

                        </span>

                    @endif

                    @if($post->experience->type?->type_name)

                        <span class="experience-meta-item">

                            {{ $post->experience->type->type_name }}

                        </span>

                    @endif

                    @if($post->experience->category?->category_name)

                        <span class="experience-meta-item">

                            {{ $post->experience->category->category_name }}

                        </span>

                    @endif

                </div>

            </div>

        @endif



        <!-- ===================================================
             POST CAPTION
        =================================================== -->

        @if($post->content)

            <div class="post-caption">

                {{ $post->content }}

            </div>

        @endif



        <!-- ===================================================
             POST IMAGES
        =================================================== -->

        @if($post->post_images)

            @php

                $images = json_decode(
                    $post->post_images,
                    true
                );

            @endphp

            @if(is_array($images) && count($images) > 0)

                @php

                    $totalImages = count($images);

                    $displayImages = array_slice(
                        $images,
                        0,
                        3
                    );

                    $galleryImages = collect($images)
                        ->values()
                        ->toArray();

                @endphp

                <div
                    class="post-gallery post-gallery-{{ count($displayImages) }}"
                    data-images='@json($galleryImages)'
                >

                    @foreach($displayImages as $index => $image)

                        <div
                            class="gallery-item"
                            data-index="{{ $index }}"
                        >

                            <img
                                src="{{ $image }}"
                                alt="Community Post Image"
                            >

                            <!-- +X MORE -->

                            @if(
                                $index === 2
                                && $totalImages > 3
                            )

                                <div class="more-images">

                                    +{{ $totalImages - 3 }}

                                </div>

                            @endif

                        </div>

                    @endforeach

                </div>

            @endif

        @endif

    </div>



    <!-- ===================================================
         POST FOOTER
    =================================================== -->

    <div class="post-footer">

        <!-- Like -->
              <!-- Like -->
        @auth
            @php
                $isLiked = $post->isLikedBy(Auth::user());
            @endphp

            <form method="POST" action="{{ route('community.posts.like', $post) }}" class="post-like-form" data-liked="{{ $isLiked ? '1' : '0' }}">
                @csrf

                @if($isLiked)
                    @method('DELETE')
                @endif

                <button type="submit" class="post-action like-action {{ $isLiked ? 'is-liked' : '' }}">
                    <span class="action-icon like-icon">{{ $isLiked ? '♥' : '♡' }}</span>
                    <span class="like-count">{{ $post->like_count ?? 0 }}</span>
                </button>
            </form>
        @else
            <a href="{{ route('login') }}" class="post-action like-action">
                <span class="action-icon like-icon">♡</span>
                <span class="like-count">{{ $post->like_count ?? 0 }}</span>
             </a>
         @endauth
         



        <!-- Comment -->

        <button
            type="button"
            class="post-action"
        >

            

            <span>
                💬
            </span>

           
        </button>

        <!-- Save -->

        @auth
            <form
                method="POST"
                action="{{ route('community.posts.saved.store', $post) }}"
                class="post-save-form"
                data-saved="{{ $isSaved ? '1' : '0' }}"
            >
                @csrf
                @if ($isSaved)
                    @method('DELETE')
                @endif
                <button
                    type="submit"
                    class="post-action post-save-action {{ $isSaved ? 'is-saved' : '' }}"
                >
                    <span class="action-icon">🔖</span>
                    <span class="post-save-label">
                        {{ $isSaved ? 'Saved' : 'Save' }}
                    </span>
                </button>
            </form>
        @else
            <button type="button" class="post-action" disabled>
                <span class="action-icon">🔖</span>
                <span>Save</span>
            </button>
        @endauth

    </div>

</article>
