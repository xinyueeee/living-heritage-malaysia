@extends('layouts.app')

@section('title', 'Edit Post | Living Heritage Malaysia')

@section('content')

<div class="community-page">

    <div class="container create-post-page">

        {{-- =========================================================
             BACK BUTTON
        ========================================================== --}}

        @php
            $from = $from ?? request('from', 'community');
        @endphp

        <a
            href="{{ $from === 'profile'
                ? route('profile.my-posts')
                : ($post->community_group_id
                    ? route('community.groups.show', $post->community_group_id)
                    : route('community.index')
                )
            }}"
            class="back-link"
        >
            ← Back
        </a>


        {{-- =========================================================
             HEADER
        ========================================================== --}}

        <div class="create-header">

            <h1>
                Edit Post
            </h1>

            <p>
                Update your cultural experience and photos.
            </p>

        </div>


        {{-- =========================================================
             VALIDATION ERRORS
        ========================================================== --}}

        @if ($errors->any())

            <div class="alert alert-danger">

                <ul style="margin:0;padding-left:20px;">

                    @foreach ($errors->all() as $error)

                        <li>
                            {{ $error }}
                        </li>

                    @endforeach

                </ul>

            </div>

        @endif


        {{-- =========================================================
             EDIT POST CARD
        ========================================================== --}}

        <div class="create-card">

            <form
                action="{{ route('community.posts.update', $post->post_id) }}"
                method="POST"
                enctype="multipart/form-data"
                id="editPostForm"
            >

                @csrf

                @method('PUT')


                {{-- =================================================
                     KEEP WHERE THE USER CAME FROM
                ================================================== --}}

                <input
                    type="hidden"
                    name="from"
                    value="{{ $from }}"
                >


                {{-- =================================================
                     POST TO
                ================================================== --}}

                <div class="form-group">

                    <div class="form-label-row">

                        <label for="community_group_display">
                            Post to
                        </label>

                    </div>

                    <p class="form-help">
                        Your post will remain in its current location.
                    </p>


                    <div class="experience-dropdown">

                        <select
                            id="community_group_display"
                            disabled
                        >

                            @if ($post->community_group_id)

                                <option selected>
                                    Current Community Group
                                </option>

                            @else

                                <option selected>
                                    Community
                                </option>

                            @endif

                        </select>

                    </div>


                    <small
                        class="form-help"
                        style="display:block;margin-top:8px;"
                    >
                        You cannot change the post location while editing.
                    </small>

                </div>


                {{-- =================================================
                     CULTURAL EXPERIENCE
                ================================================== --}}

                <div class="form-group experience-selector">

                    <div class="form-label-row">

                        <label for="experienceSearch">
                            Cultural Experience
                        </label>

                        <span class="optional">
                            (Optional)
                        </span>

                    </div>


                    <p class="form-help">
                        Link your post to a cultural experience or festival.
                    </p>


                    {{-- SEARCH + SELECT --}}

                    <div class="experience-select-row">


                        {{-- SEARCH BOX --}}

                        <div class="experience-search-box">

                            <span class="search-icon">
                                🔍
                            </span>

                            <input
                                type="search"
                                id="experienceSearch"
                                placeholder="Search experiences..."
                                autocomplete="off"
                                value=""
                            >

                        </div>


                        {{-- SELECT --}}

                        <div class="experience-dropdown">

                            <select
                                id="experience_id"
                                name="experience_id"
                            >

                                <option value="">
                                    No experience selected
                                </option>


                                @foreach ($experiences as $experience)

                                    <option
                                        value="{{ $experience->experiences_id }}"

                                        data-name="{{ $experience->experiences_name }}"

                                        data-location="{{ $experience->location_name ?? '' }}"

                                        data-type="{{ $experience->type?->type_name ?? '' }}"

                                        data-category="{{ $experience->category?->category_name ?? '' }}"

                                        data-search="{{ strtolower(
                                            $experience->experiences_name
                                            . ' '
                                            . ($experience->location_name ?? '')
                                            . ' '
                                            . ($experience->type?->type_name ?? '')
                                            . ' '
                                            . ($experience->category?->category_name ?? '')
                                        ) }}"

                                        @selected(
                                            old(
                                                'experience_id',
                                                $post->experience_id
                                            ) == $experience->experiences_id
                                        )
                                    >

                                        {{ $experience->experiences_name }}

                                    </option>

                                @endforeach

                            </select>

                        </div>

                    </div>


                    {{-- SEARCH MESSAGE --}}

                    <small
                        id="experienceSearchMessage"
                        class="experience-search-message"
                    ></small>


                    {{-- SEARCH RESULTS --}}

                    <div
                        id="experienceSearchResults"
                        class="experience-search-results"
                    ></div>

                </div>


                {{-- =================================================
                     WHAT'S ON YOUR MIND
                ================================================== --}}

                <div class="form-group">

                    <label for="content">
                        What's on your mind?
                    </label>


                    <textarea
                        id="content"
                        name="content"
                        rows="7"
                        maxlength="2000"
                        placeholder="Share your experience, stories, tips, or recommendations..."
                    >{{ old('content', $post->content) }}</textarea>


                    <div class="character-counter">

                        <span id="charCount">
                            {{ strlen(old('content', $post->content ?? '')) }}
                        </span>

                        / 2000

                    </div>

                </div>


                {{-- =================================================
                     EXISTING PHOTOS
                ================================================== --}}

                @php

                    $existingImages = [];

                    if (!empty($post->post_images)) {

                        $decodedImages = json_decode(
                            $post->post_images,
                            true
                        );

                        if (is_array($decodedImages)) {

                            $existingImages = $decodedImages;

                        }

                    }

                @endphp


                @if (count($existingImages) > 0)

                    <div class="form-group">

                        <div class="form-label-row">

                            <label>
                                Current Photos
                            </label>

                            <span class="optional">
                                (Click × to remove)
                            </span>

                        </div>


                        <p class="form-help">
                            Remove any photos you no longer want in this post.
                        </p>


                        <div class="edit-current-photos">

                            @foreach ($existingImages as $index => $image)

                                <div
                                    class="edit-current-photo"
                                    id="current-photo-{{ $index }}"
                                >

                                    <img
                                        src="{{ $image }}"
                                        alt="Current post photo"
                                    >


                                    {{-- Keep image by default --}}

                                    <input
                                        type="checkbox"
                                        name="keep_images[]"
                                        value="{{ $image }}"
                                        id="keep-image-{{ $index }}"
                                        class="keep-image-checkbox"
                                        checked
                                    >


                                    <button
                                        type="button"
                                        class="edit-remove-image"
                                        data-index="{{ $index }}"
                                        aria-label="Remove photo"
                                    >
                                        ×
                                    </button>

                                </div>

                            @endforeach

                        </div>


                        <small class="form-help">
                            Removed photos will no longer appear in this post.
                        </small>

                    </div>

                @endif


                {{-- =================================================
                     ADD NEW PHOTOS
                ================================================== --}}

                <div class="form-group">

                    <div class="form-label-row">

                        <label>
                            Add Photos
                        </label>

                        <span class="optional">
                            (Optional)
                        </span>

                    </div>


                    <p class="form-help">
                        Add new photos to your post.
                    </p>


                    <div class="photo-upload-row">


                        {{-- UPLOAD BOX --}}

                        <div class="upload-box">

                            <div class="upload-icon">
                                +
                            </div>

                            <h3>
                                Add Photos
                            </h3>

                            <p>
                                Click or drag files here
                            </p>

                            <small>
                                Maximum 10 photos
                            </small>


                            <input
                                type="file"
                                id="imageInput"
                                name="images[]"
                                multiple
                                accept="image/jpeg,image/png,image/webp"
                            >

                        </div>


                        {{-- NEW PHOTO PREVIEW --}}

                        <div
                            id="photoPreviewPanel"
                            class="photo-preview-panel"
                        >

                            <div class="photo-preview-header">

                                <strong>
                                    Photo Preview
                                </strong>

                                <span id="photoCount">
                                    ({{ count($existingImages) }}/10)
                                </span>

                            </div>


                            <div
                                id="imagePreview"
                                class="image-preview"
                            ></div>

                        </div>

                    </div>

                </div>


                {{-- =================================================
                     UPLOAD NOTE
                ================================================== --}}

                <div class="upload-note">

                    Supported formats:
                    JPG, PNG, WEBP

                    •

                    Maximum size:
                    10MB per photo

                </div>


                {{-- =================================================
                     BUTTONS
                ================================================== --}}

                <div class="button-group">


                    {{-- CANCEL --}}

                    <a
                        href="{{ $from === 'profile'
                            ? route('profile.my-posts')
                            : ($post->community_group_id
                                ? route('community.groups.show', $post->community_group_id)
                                : route('community.index')
                            )
                        }}"
                        class="cancel-btn"
                    >
                        Cancel
                    </a>


                    {{-- SAVE --}}

                    <button
                        type="submit"
                        class="publish-btn"
                        id="saveChangesButton"
                    >
                        Save Changes
                    </button>

                </div>


            </form>

        </div>

    </div>

</div>

@endsection


{{-- ================================================================
     EDIT POST STYLES
================================================================ --}}

@push('styles')

<style>

    .edit-current-photos {
        display: flex;
        align-items: flex-start;
        gap: 8px;
        flex-wrap: wrap;
        width: 100%;
    }


    .edit-current-photo {
        position: relative;
        width: 82px;
        height: 130px;
        flex: 0 0 82px;
        overflow: hidden;
        border-radius: 7px;
        background: #f5f5f5;
        transition:
            opacity .2s ease,
            transform .2s ease;
    }


    .edit-current-photo img {
        display: block;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }


    .edit-remove-image {
        position: absolute;
        top: 4px;
        right: 4px;
        width: 21px;
        height: 21px;
        padding: 0;
        border: none;
        border-radius: 50%;
        background: #fff;
        color: #8A3A2D;
        font-size: 16px;
        font-weight: 700;
        line-height: 21px;
        text-align: center;
        cursor: pointer;
        z-index: 20;
        box-shadow: 0 1px 4px rgba(0,0,0,.15);
    }


    .edit-remove-image:hover {
        background: #8A3A2D;
        color: #fff;
    }


    .edit-current-photo.is-removed {
        opacity: .35;
    }


    .edit-current-photo.is-removed img {
        filter: grayscale(1);
    }


    .edit-current-photo.is-removed::after {
        content: "Removed";
        position: absolute;
        left: 0;
        right: 0;
        bottom: 0;
        padding: 5px 2px;
        background: rgba(138,58,45,.9);
        color: #fff;
        font-size: 10px;
        text-align: center;
        z-index: 10;
    }


    @media (max-width: 600px) {

        .edit-current-photos {
            gap: 6px;
        }

        .edit-current-photo {
            width: 75px;
            height: 115px;
            flex-basis: 75px;
        }

    }

</style>

@endpush


{{-- ================================================================
     JAVASCRIPT
================================================================ --}}

@push('scripts')

<script>

document.addEventListener('DOMContentLoaded', function () {


    /* ============================================================
       CHARACTER COUNTER
    ============================================================ */

    const textarea =
        document.getElementById('content');

    const counter =
        document.getElementById('charCount');


    if (textarea && counter) {

        textarea.addEventListener(
            'input',
            function () {

                counter.textContent =
                    this.value.length;

            }
        );

    }


    /* ============================================================
       EXPERIENCE SEARCH
    ============================================================ */

    const experienceSearch =
        document.getElementById('experienceSearch');

    const experienceSelect =
        document.getElementById('experience_id');

    const experienceMessage =
        document.getElementById('experienceSearchMessage');

    const experienceResults =
        document.getElementById('experienceSearchResults');


    if (
        experienceSearch &&
        experienceSelect &&
        experienceResults
    ) {


        /* ========================================================
           SHOW CURRENT EXPERIENCE
        ======================================================== */

        const initiallySelected =
            experienceSelect.options[
                experienceSelect.selectedIndex
            ];


        if (
            initiallySelected &&
            experienceSelect.value
        ) {

            experienceSearch.value =
                initiallySelected.dataset.name || '';

            experienceMessage.textContent =
                'Experience selected.';

        }


        /* ========================================================
           SEARCH
        ======================================================== */

        experienceSearch.addEventListener(
            'input',
            function () {

                const keyword =
                    this.value
                        .trim()
                        .toLowerCase();


                let visibleCount = 0;


                experienceResults.innerHTML = '';


                if (keyword === '') {

                    experienceMessage.textContent = '';

                    experienceResults.classList.remove(
                        'active'
                    );

                    return;

                }


                /* =================================================
                   LOOP THROUGH EXPERIENCES
                ================================================== */

                Array.from(
                    experienceSelect.options
                ).forEach(
                    function (option, index) {

                        if (index === 0) {
                            return;
                        }


                        const searchText =
                            option.dataset.search || '';


                        const matched =
                            searchText.includes(keyword);


                        if (!matched) {
                            return;
                        }


                        visibleCount++;


                        const name =
                            option.dataset.name || '';

                        const location =
                            option.dataset.location || '';

                        const type =
                            option.dataset.type || '';

                        const category =
                            option.dataset.category || '';


                        /* =========================================
                           CREATE RESULT
                        ========================================== */

                        const resultItem =
                            document.createElement('button');


                        resultItem.type = 'button';

                        resultItem.className =
                            'experience-result-item';


                        let metaParts = [];


                        if (location) {

                            metaParts.push(
                                '📍 ' + location
                            );

                        }


                        if (type) {

                            metaParts.push(type);

                        }


                        if (category) {

                            metaParts.push(category);

                        }


                        resultItem.innerHTML = `

                            <div class="experience-result-name">
                                ${name}
                            </div>

                            ${
                                metaParts.length > 0
                                ?
                                `
                                <div class="experience-result-meta">
                                    ${metaParts.join(' · ')}
                                </div>
                                `
                                :
                                ''
                            }

                        `;


                        /* =========================================
                           SELECT RESULT
                        ========================================== */

                        resultItem.addEventListener(
                            'click',
                            function () {

                                experienceSelect.value =
                                    option.value;


                                experienceSearch.value =
                                    name;


                                experienceResults.innerHTML =
                                    '';

                                experienceResults.classList.remove(
                                    'active'
                                );


                                experienceMessage.textContent =
                                    'Experience selected.';

                            }
                        );


                        experienceResults.appendChild(
                            resultItem
                        );

                    }
                );


                /* =================================================
                   SEARCH MESSAGE
                ================================================== */

                if (visibleCount === 0) {

                    experienceMessage.textContent =
                        'No matching experiences found.';

                    experienceResults.classList.remove(
                        'active'
                    );

                }
                else {

                    experienceMessage.textContent =
                        visibleCount
                        + ' experience'
                        + (
                            visibleCount === 1
                            ? ''
                            : 's'
                        )
                        + ' found.';


                    experienceResults.classList.add(
                        'active'
                    );

                }

            }
        );


        /* ========================================================
           SELECT CHANGE
        ======================================================== */

        experienceSelect.addEventListener(
            'change',
            function () {

                const selectedOption =
                    this.options[
                        this.selectedIndex
                    ];


                if (!this.value) {

                    experienceSearch.value = '';

                    experienceMessage.textContent = '';

                    experienceResults.innerHTML = '';

                    experienceResults.classList.remove(
                        'active'
                    );

                    return;

                }


                const selectedName =
                    selectedOption.dataset.name || '';


                experienceSearch.value =
                    selectedName;


                experienceMessage.textContent =
                    'Experience selected.';


                experienceResults.innerHTML = '';

                experienceResults.classList.remove(
                    'active'
                );

            }
        );

    }


    /* ============================================================
       CURRENT PHOTO REMOVAL
    ============================================================ */

    const currentPhotoButtons =
        document.querySelectorAll(
            '.edit-remove-image'
        );


    currentPhotoButtons.forEach(
        function (button) {

            button.addEventListener(
                'click',
                function () {

                    const photo =
                        this.closest(
                            '.edit-current-photo'
                        );


                    if (!photo) {
                        return;
                    }


                    const checkbox =
                        photo.querySelector(
                            '.keep-image-checkbox'
                        );


                    if (!checkbox) {
                        return;
                    }


                    /* ==============================================
                       REMOVE PHOTO
                    =============================================== */

                    if (checkbox.checked) {

                        checkbox.checked = false;

                        photo.classList.add(
                            'is-removed'
                        );

                    }


                    /* ==============================================
                       RESTORE PHOTO
                    =============================================== */

                    else {

                        checkbox.checked = true;

                        photo.classList.remove(
                            'is-removed'
                        );

                    }


                    updatePhotoCount();

                }
            );

        }
    );


    /* ============================================================
       NEW IMAGE UPLOAD
    ============================================================ */

    const imageInput =
        document.getElementById('imageInput');

    const preview =
        document.getElementById('imagePreview');

    const photoPreviewPanel =
        document.getElementById('photoPreviewPanel');

    const photoCount =
        document.getElementById('photoCount');


    let selectedFiles = [];


    /* ============================================================
       GET KEPT IMAGE COUNT
    ============================================================ */

    function getKeptImageCount() {

        return document.querySelectorAll(
            '.keep-image-checkbox:checked'
        ).length;

    }


    /* ============================================================
       UPDATE TOTAL PHOTO COUNT
    ============================================================ */

    function updatePhotoCount() {

        const currentCount =
            getKeptImageCount();


        const totalCount =
            currentCount +
            selectedFiles.length;


        if (photoCount) {

            photoCount.textContent =
                `(${totalCount}/10)`;

        }

    }


    /* ============================================================
       IMAGE SELECTION
    ============================================================ */

    if (imageInput) {

        imageInput.addEventListener(
            'change',
            function () {

                const newFiles =
                    Array.from(this.files);


                const currentCount =
                    getKeptImageCount();


                if (
                    currentCount +
                    selectedFiles.length +
                    newFiles.length
                    > 10
                ) {

                    alert(
                        'You can upload a maximum of 10 photos in total.'
                    );


                    this.value = '';

                    return;

                }


                selectedFiles.push(
                    ...newFiles
                );


                renderPreview();

                updateFileInput();

                updatePhotoCount();

            }
        );

    }


    /* ============================================================
       RENDER NEW IMAGE PREVIEW
    ============================================================ */

    function renderPreview() {

        if (!preview) {
            return;
        }


        preview.innerHTML = '';


        if (photoPreviewPanel) {

            if (selectedFiles.length > 0) {

                photoPreviewPanel.classList.add(
                    'has-photos'
                );

            }
            else {

                photoPreviewPanel.classList.remove(
                    'has-photos'
                );

            }

        }


        selectedFiles.forEach(
            function (file, index) {

                const reader =
                    new FileReader();


                reader.onload =
                    function (event) {

                        const div =
                            document.createElement(
                                'div'
                            );


                        div.className =
                            'preview-item';


                        div.innerHTML = `

                            <img
                                src="${event.target.result}"
                                alt="Selected photo"
                            >

                            <button
                                type="button"
                                class="remove-image"
                                data-index="${index}"
                                aria-label="Remove photo"
                            >
                                ×
                            </button>

                        `;


                        preview.appendChild(div);

                    };


                reader.readAsDataURL(file);

            }
        );

    }


    /* ============================================================
       REMOVE NEW IMAGE
    ============================================================ */

    if (preview) {

        preview.addEventListener(
            'click',
            function (event) {

                if (
                    event.target.classList.contains(
                        'remove-image'
                    )
                ) {

                    const index =
                        parseInt(
                            event.target.dataset.index
                        );


                    selectedFiles.splice(
                        index,
                        1
                    );


                    renderPreview();

                    updateFileInput();

                    updatePhotoCount();

                }

            }
        );

    }


    /* ============================================================
       UPDATE FILE INPUT
    ============================================================ */

    function updateFileInput() {

        if (!imageInput) {
            return;
        }


        const dataTransfer =
            new DataTransfer();


        selectedFiles.forEach(
            function (file) {

                dataTransfer.items.add(
                    file
                );

            }
        );


        imageInput.files =
            dataTransfer.files;

    }


    /* ============================================================
       INITIAL PHOTO COUNT
    ============================================================ */

    updatePhotoCount();


    /* ============================================================
       PREVENT DOUBLE SUBMISSION
    ============================================================ */

    const editPostForm =
        document.getElementById(
            'editPostForm'
        );

    const saveChangesButton =
        document.getElementById(
            'saveChangesButton'
        );


    if (
        editPostForm &&
        saveChangesButton
    ) {

        editPostForm.addEventListener(
            'submit',
            function () {

                saveChangesButton.disabled =
                    true;


                saveChangesButton.textContent =
                    'Saving...';

            }
        );

    }

});

</script>

@endpush