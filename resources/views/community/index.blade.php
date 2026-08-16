@extends('layouts.app')

@section('title', 'Community | Living Heritage Malaysia')

@section('content')

<div class="community-page">

    <!-- ===================================================
         HERO SECTION
    =================================================== -->

    <section class="community-hero">

        <div class="container community-hero-content">

            <div class="community-intro">

                <p class="community-eyebrow">
                    Share. Inspire. Preserve.
                </p>

                <h1>
                    Community
                </h1>

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


        <div class="community-feed">


            @forelse($posts as $post)


                <!-- ===================================================
                     POST CARD
                =================================================== -->

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

                        <button
                            type="button"
                            class="post-action like-action"
                        >

                            <span class="action-icon">
                                ♡
                            </span>

                            <span>
                                {{ $post->like_count ?? 0 }}
                            </span>

                        </button>



                        <!-- Comment -->

                        <button
                            type="button"
                            class="post-action"
                        >

                            <span class="action-icon">
                                ♡
                            </span>

                            <span>
                                💬
                            </span>

                            <span>
                                0
                            </span>

                        </button>



                        <!-- Save -->

                        <button
                            type="button"
                            class="post-action"
                        >

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


                <!-- ===================================================
                     EMPTY FEED
                =================================================== -->

                <div class="empty-feed">


                    <div class="empty-icon">
                        💬
                    </div>


                    <h2>
                        No Posts Yet
                    </h2>


                    <p>
                        Be the first to share your cultural experience
                        with the community.
                    </p>


                </div>


            @endforelse


        </div>

    </div>

</div>



<!-- ===================================================
     PHOTO VIEWER MODAL
=================================================== -->

<div
    id="photoViewer"
    class="photo-viewer"
>


    <!-- Close -->

    <button
        type="button"
        class="photo-viewer-close"
        id="photoViewerClose"
    >
        ×
    </button>



    <!-- Previous -->

    <button
        type="button"
        class="photo-viewer-prev"
        id="photoViewerPrev"
    >
        ‹
    </button>



    <!-- Viewer -->

    <div class="photo-viewer-content">

        <img
            id="photoViewerImage"
            src=""
            alt="Community Post Image"
        >


        <div
            id="photoViewerCounter"
            class="photo-viewer-counter"
        >
            1 / 1
        </div>

    </div>



    <!-- Next -->

    <button
        type="button"
        class="photo-viewer-next"
        id="photoViewerNext"
    >
        ›
    </button>


</div>

@endsection



<!-- ===================================================
     JAVASCRIPT
=================================================== -->

@push('scripts')

<script>

document.addEventListener('DOMContentLoaded', function () {


    /* ===================================================
       PHOTO VIEWER ELEMENTS
    =================================================== */

    const photoViewer =
        document.getElementById('photoViewer');

    const photoViewerImage =
        document.getElementById('photoViewerImage');

    const photoViewerCounter =
        document.getElementById('photoViewerCounter');

    const closeButton =
        document.getElementById('photoViewerClose');

    const prevButton =
        document.getElementById('photoViewerPrev');

    const nextButton =
        document.getElementById('photoViewerNext');



    /* ===================================================
       PHOTO VIEWER STATE
    =================================================== */

    let currentImages = [];

    let currentIndex = 0;



    /* ===================================================
       OPEN PHOTO VIEWER
    =================================================== */

    document
        .querySelectorAll('.post-gallery')
        .forEach(function (gallery) {


            gallery
                .querySelectorAll('.gallery-item')
                .forEach(function (item) {


                    item.addEventListener(
                        'click',
                        function () {


                            currentImages =
                                JSON.parse(
                                    gallery.dataset.images
                                );


                            currentIndex =
                                parseInt(
                                    item.dataset.index
                                );


                            openPhotoViewer();

                        }
                    );

                });

        });



    /* ===================================================
       OPEN
    =================================================== */

    function openPhotoViewer() {


        if (currentImages.length === 0) {

            return;

        }


        photoViewer.classList.add('active');


        updatePhotoViewer();


        document.body.style.overflow =
            'hidden';

    }



    /* ===================================================
       UPDATE PHOTO
    =================================================== */

    function updatePhotoViewer() {


        photoViewerImage.src =
            currentImages[currentIndex];


        photoViewerCounter.textContent =
            `${currentIndex + 1} / ${currentImages.length}`;


        if (currentImages.length <= 1) {


            prevButton.style.display =
                'none';

            nextButton.style.display =
                'none';


        } else {


            prevButton.style.display =
                'flex';

            nextButton.style.display =
                'flex';

        }

    }



    /* ===================================================
       CLOSE
    =================================================== */

    function closePhotoViewer() {


        photoViewer.classList.remove(
            'active'
        );


        document.body.style.overflow =
            '';


        photoViewerImage.src =
            '';

    }


    closeButton.addEventListener(
        'click',
        function () {

            closePhotoViewer();

        }
    );



    /* ===================================================
       PREVIOUS
    =================================================== */

    prevButton.addEventListener(
        'click',
        function (event) {


            event.stopPropagation();


            currentIndex--;


            if (currentIndex < 0) {

                currentIndex =
                    currentImages.length - 1;

            }


            updatePhotoViewer();

        }
    );



    /* ===================================================
       NEXT
    =================================================== */

    nextButton.addEventListener(
        'click',
        function (event) {


            event.stopPropagation();


            currentIndex++;


            if (
                currentIndex >=
                currentImages.length
            ) {

                currentIndex = 0;

            }


            updatePhotoViewer();

        }
    );



    /* ===================================================
       CLICK BACKGROUND TO CLOSE
    =================================================== */

    photoViewer.addEventListener(
        'click',
        function (event) {


            if (
                event.target ===
                photoViewer
            ) {

                closePhotoViewer();

            }

        }
    );



    /* ===================================================
       KEYBOARD CONTROLS
    =================================================== */

    document.addEventListener(
        'keydown',
        function (event) {


            if (
                !photoViewer.classList.contains(
                    'active'
                )
            ) {

                return;

            }



            /* ESC */

            if (event.key === 'Escape') {

                closePhotoViewer();

            }



            /* LEFT */

            if (event.key === 'ArrowLeft') {


                currentIndex--;


                if (currentIndex < 0) {

                    currentIndex =
                        currentImages.length - 1;

                }


                updatePhotoViewer();

            }



            /* RIGHT */

            if (event.key === 'ArrowRight') {


                currentIndex++;


                if (
                    currentIndex >=
                    currentImages.length
                ) {

                    currentIndex = 0;

                }


                updatePhotoViewer();

            }

        }
    );


});

</script>

@endpush