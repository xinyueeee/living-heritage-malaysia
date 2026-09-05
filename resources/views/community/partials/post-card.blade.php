{{-- Expects: $post (App\Models\Post), $isSaved (bool) --}}

<article
    class="post-card"
    id="post-{{ $post->post_id }}"
>

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


        <!-- ===================================================
             MORE OPTIONS
             ONLY POST OWNER CAN SEE THIS
        =================================================== -->

        @auth

            @if ($post->user_id === Auth::user()->user_id)

                <div class="post-options-wrapper">

                    <button
                        type="button"
                        class="post-more-btn"
                        aria-label="More options"
                    >
                        ⋯
                    </button>


                    <div class="post-options-menu">

                        <!-- Edit -->

                        <a
                            href="{{ route('community.posts.edit',[
                                'post' => $post->post_id,
                                'from' => isset($fromProfile) && $fromProfile ? 'profile' : 'community',
                            ]) }}"
                            class="post-option-edit"
                        >
                            Edit
                        </a>


                        <!-- Delete -->

                        <form
                            method="POST"
                            action="{{ route('community.posts.destroy', $post->post_id) }}"
                            onsubmit="return confirm('Are you sure you want to delete this post?');"
                        >

                            @csrf

                            @method('DELETE')

                            <button
                                type="submit"
                                class="post-option-delete"
                            >
                                Delete
                            </button>

                        </form>

                    </div>

                </div>

            @endif

        @endauth

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

                <h3 class="post-experience-name">
                    {{ $post->experience->experiences_name }}
                </h3>


                <div class="post-experience-meta">

                    @if($post->experience->location_name)

                        <span class="experience-meta-item">
                            📍 {{ $post->experience->location_name }}
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


        <!-- ===================================================
             LIKE
        =================================================== -->

        @auth

            @php
                $isLiked = (bool) ($post->is_liked_by_user ?? false);
            @endphp


            <form
                method="POST"
                action="{{ route('community.posts.like', $post) }}"
                class="post-like-form"
                data-liked="{{ $isLiked ? '1' : '0' }}"
            >

                @csrf

                @if($isLiked)
                    @method('DELETE')
                @endif


                <button
                    type="submit"
                    class="post-action like-action {{ $isLiked ? 'is-liked' : '' }}"
                >

                    <span class="action-icon like-icon">
                        {{ $isLiked ? '♥' : '♡' }}
                    </span>

                    <span class="like-count">
                        {{ $post->like_count ?? 0 }}
                    </span>

                </button>

            </form>

        @else

            <a
                href="{{ route('login') }}"
                class="post-action like-action"
            >

                <span class="action-icon like-icon">
                    ♡
                </span>

                <span class="like-count">
                    {{ $post->like_count ?? 0 }}
                </span>

            </a>

        @endauth



        <!-- ===================================================
             COMMENT BUTTON
        =================================================== -->

        <button
            type="button"
            class="post-action comment-toggle"
            data-post-id="{{ $post->post_id }}"
            aria-expanded="false"
            aria-label="View comments"
        >

            <span class="action-icon">
                💬
            </span>

            <span class="comment-count">
                {{ $post->post_comments_count ?? 0 }}
            </span>

        </button>



        <!-- ===================================================
             SAVE
        =================================================== -->

        @auth

            <form
                method="POST"
                action="{{ route('community.posts.saved.store', $post) }}"
                class="post-save-form"
                data-saved="{{ $isSaved ? '1' : '0' }}"
                data-post-id="{{ $post->post_id }}"
            >

                @csrf

                @if($isSaved)
                    @method('DELETE')
                @endif


                <button
                    type="submit"
                    class="post-action post-save-action {{ $isSaved ? 'is-saved' : '' }}"
                >

                    <span class="action-icon">
                        🔖
                    </span>

                    <span class="post-save-label">
                        {{ $isSaved ? 'Saved' : 'Save' }}
                    </span>

                </button>

            </form>

        @else

            <button
                type="button"
                class="post-action"
                disabled
            >

                <span class="action-icon">
                    🔖
                </span>

                <span>
                    Save
                </span>

            </button>

        @endauth

    </div>



    <!-- ===================================================
         COMMENTS SECTION
         HIDDEN BY DEFAULT
    =================================================== -->

    <div
        id="comments-{{ $post->post_id }}"
        class="comment-section comments-hidden"
    >


        <!-- ===================================================
             EXISTING COMMENTS
        =================================================== -->

        <div class="comments-list">

            @forelse($post->postComments as $comment)

                <div class="comment-item">


                    <!-- Commenter's Avatar -->

                    <img
                        src="{{ $comment->user->profile_photo ?? asset('images/default-avatar.png') }}"
                        class="comment-avatar"
                        alt="Avatar"
                    >


                    <!-- Comment Content -->

                    <div class="comment-content">

                        <strong>
                            {{ $comment->user->user_name ?? 'Anonymous' }}
                        </strong>


                        <p>
                            {{ $comment->comment }}
                        </p>


                        <small>
                            {{ \Carbon\Carbon::parse($comment->created_at)->diffForHumans() }}
                        </small>

                    </div>

                </div>


            @empty

                <p class="no-comments">
                    No comments yet. Be the first to comment!
                </p>

            @endforelse

        </div>



        <!-- ===================================================
             ENTER COMMENT
        =================================================== -->

        @auth

            <form
                method="POST"
                action="{{ route('comments.store', $post->post_id) }}"
                class="comment-form"
            >

                @csrf


                <input
                    type="text"
                    name="comment"
                    placeholder="Write a comment..."
                    maxlength="1000"
                    autocomplete="off"
                    required
                >


                <button
                    type="submit"
                    class="comment-submit-btn"
                >
                    Post
                </button>

            </form>

        @else

            <a
                href="{{ route('login') }}"
                class="comment-login"
            >
                Login to comment
            </a>

        @endauth

    </div>

</article>

<script>
    // Handle Post More Options menu
    // This partial may appear multiple times on the page,
    // so make sure the event listener is only added once.

    if (!window.postOptionsMenuInitialized) {

        window.postOptionsMenuInitialized = true;

        document.addEventListener('click', function (event) {

            const moreButton = event.target.closest('.post-more-btn');

            // ==========================================
            // Clicked the "⋯" button
            // ==========================================

            if (moreButton) {

                event.stopPropagation();

                const wrapper =
                    moreButton.closest('.post-options-wrapper');

                if (!wrapper) {
                    return;
                }

                const menu =
                    wrapper.querySelector('.post-options-menu');

                if (!menu) {
                    return;
                }


                // Close other open menus first

                document
                    .querySelectorAll('.post-options-menu.show')
                    .forEach(function (otherMenu) {

                        if (otherMenu !== menu) {

                            otherMenu.classList.remove('show');

                            otherMenu.setAttribute(
                                'aria-hidden',
                                'true'
                            );

                            const otherButton =
                                otherMenu
                                    .closest('.post-options-wrapper')
                                    ?.querySelector('.post-more-btn');

                            if (otherButton) {

                                otherButton.setAttribute(
                                    'aria-expanded',
                                    'false'
                                );

                            }

                        }

                    });


                // Toggle current menu

                const isOpen =
                    menu.classList.contains('show');


                if (isOpen) {

                    menu.classList.remove('show');

                    menu.setAttribute(
                        'aria-hidden',
                        'true'
                    );

                    moreButton.setAttribute(
                        'aria-expanded',
                        'false'
                    );

                } else {

                    menu.classList.add('show');

                    menu.setAttribute(
                        'aria-hidden',
                        'false'
                    );

                    moreButton.setAttribute(
                        'aria-expanded',
                        'true'
                    );

                }

                return;
            }


            // ==========================================
            // Clicked somewhere else
            // Close all menus
            // ==========================================

            document
                .querySelectorAll('.post-options-menu.show')
                .forEach(function (menu) {

                    menu.classList.remove('show');

                    menu.setAttribute(
                        'aria-hidden',
                        'true'
                    );


                    const button =
                        menu
                            .closest('.post-options-wrapper')
                            ?.querySelector('.post-more-btn');


                    if (button) {

                        button.setAttribute(
                            'aria-expanded',
                            'false'
                        );

                    }

                });

        });

    }
</script>