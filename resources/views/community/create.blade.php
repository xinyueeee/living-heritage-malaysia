@extends('layouts.app')

@section('title', 'Create Post | Living Heritage Malaysia')

@section('content')

<div class="community-page">

    <div class="container create-post-page">

        <!-- ===================================================
             BACK BUTTON
        =================================================== -->

        <a href="{{ route('community.index') }}" class="back-link">
            ← Back to Community
        </a>


        <!-- ===================================================
             HEADER
        =================================================== -->

        <div class="create-header">

            <h1>Create Post</h1>

            <p>
                Share your cultural experience with the community.
            </p>

        </div>


        <!-- ===================================================
             VALIDATION ERRORS
        =================================================== -->

        @if ($errors->any())

            <div class="alert alert-danger">

                <ul style="margin:0;padding-left:20px;">

                    @foreach ($errors->all() as $error)

                        <li>{{ $error }}</li>

                    @endforeach

                </ul>

            </div>

        @endif


        <!-- ===================================================
             CREATE POST CARD
        =================================================== -->

        <div class="create-card">

            <form
                action="{{ route('community.store') }}"
                method="POST"
                enctype="multipart/form-data"
                id="createPostForm">

                @csrf


                <!-- ===================================================
                     POST TO
                =================================================== -->

                <div class="form-group">

                    <div class="form-label-row">

                        <label for="community_group_id">
                            Post to
                        </label>

                    </div>


                    <p class="form-help">
                        Choose where you want your post to appear.
                    </p>


                    <div class="experience-dropdown">

                        <select
                            id="community_group_id"
                            name="community_group_id">

                            <!-- ===================================================
                                 MAIN COMMUNITY
                            =================================================== -->

                            <option
                                value=""
                                @selected(old('community_group_id') === null || old('community_group_id') === '')
                            >
                                Community
                            </option>


                            <!-- ===================================================
                                 JOINED COMMUNITY GROUPS
                            =================================================== -->

                            @foreach ($groups as $group)

                                <option
                                    value="{{ $group->group_id }}"
                                    @selected(old('community_group_id') == $group->group_id)
                                >
                                    {{ $group->name }}
                                </option>

                            @endforeach

                        </select>

                    </div>


                    <!-- ===================================================
                         GROUP POST INFORMATION
                    =================================================== -->

                    <small
                        class="form-help"
                        id="postLocationHelp"
                        style="display:block;margin-top:8px;">

                        Your post will appear in the selected location.

                    </small>


                    @if ($groups->isEmpty())

                        <small
                            class="form-help"
                            style="display:block;margin-top:6px;">

                            You have not joined any community groups yet.
                            Your post will be published to the main Community.

                        </small>

                    @endif

                </div>



                <!-- ===================================================
                     CULTURAL EXPERIENCE
                     OPTIONAL
                =================================================== -->

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


                    <!-- ===================================================
                         SEARCH + SELECT
                    =================================================== -->

                    <div class="experience-select-row">

                        <!-- SEARCH -->

                        <div class="experience-search-box">

                            <span class="search-icon">
                                🔍
                            </span>

                            <input
                                type="search"
                                id="experienceSearch"
                                placeholder="Search experiences..."
                                autocomplete="off">

                        </div>


                        <!-- SELECT -->

                        <div class="experience-dropdown">

                            <select
                                id="experience_id"
                                name="experience_id">

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
                                            old('experience_id') == $experience->experiences_id
                                        )
                                    >

                                        {{ $experience->experiences_name }}

                                    </option>

                                @endforeach

                            </select>

                        </div>

                    </div>


                    <!-- ===================================================
                         SEARCH RESULT MESSAGE
                    =================================================== -->

                    <small
                        id="experienceSearchMessage"
                        class="experience-search-message">
                    </small>


                    <!-- ===================================================
                         SEARCH RESULTS
                    =================================================== -->

                    <div
                        id="experienceSearchResults"
                        class="experience-search-results">
                    </div>

                </div>



                <!-- ===================================================
                     WHAT'S ON YOUR MIND
                =================================================== -->

                <div class="form-group">

                    <label for="content">
                        What's on your mind?
                    </label>


                    <textarea
                        id="content"
                        name="content"
                        rows="7"
                        maxlength="2000"
                        placeholder="Share your experience, stories, tips, or recommendations...">{{ old('content') }}</textarea>


                    <div class="character-counter">

                        <span id="charCount">
                            {{ strlen(old('content')) }}
                        </span>

                        / 2000

                    </div>

                </div>



                <!-- ===================================================
                     UPLOAD PHOTOS
                =================================================== -->

                <div class="form-group">

                    <div class="form-label-row">

                        <label>
                            Upload Photos
                        </label>

                        <span class="optional">
                            (Optional)
                        </span>

                    </div>


                    <!-- ===================================================
                         UPLOAD BOX + PHOTO PREVIEW
                    =================================================== -->

                    <div class="photo-upload-row">


                        <!-- ===================================================
                             ADD PHOTOS
                        =================================================== -->

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
                                accept="image/jpeg,image/png,image/webp">

                        </div>


                        <!-- ===================================================
                             PHOTO PREVIEW
                        =================================================== -->

                        <div
                            id="photoPreviewPanel"
                            class="photo-preview-panel">


                            <div class="photo-preview-header">

                                <strong>
                                    Photo Preview
                                </strong>

                                <span id="photoCount">
                                    (0/10)
                                </span>

                            </div>


                            <div
                                id="imagePreview"
                                class="image-preview">
                            </div>

                        </div>

                    </div>

                </div>



                <!-- ===================================================
                     UPLOAD NOTE
                =================================================== -->

                <div class="upload-note">

                    Supported formats:
                    JPG, PNG, WEBP

                    •

                    Maximum size:
                    10MB per photo

                </div>



                <!-- ===================================================
                     BUTTONS
                =================================================== -->

                <div class="button-group">

                    <a
                        href="{{ route('community.index') }}"
                        class="cancel-btn">

                        Cancel

                    </a>


                    <button
                        type="submit"
                        class="publish-btn"
                        id="publishButton">

                        Publish

                    </button>

                </div>


            </form>

        </div>

    </div>

</div>

@endsection



<!-- ===================================================
     JAVASCRIPT
=================================================== -->

@push('scripts')

<script>

document.addEventListener('DOMContentLoaded', function () {


    /* ===================================================
       CHARACTER COUNTER
    =================================================== */

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



    /* ===================================================
       POST LOCATION SELECT
    =================================================== */

    const groupSelect =
        document.getElementById('community_group_id');

    const postLocationHelp =
        document.getElementById('postLocationHelp');


    if (groupSelect && postLocationHelp) {

        function updatePostLocationMessage() {

            const selectedOption =
                groupSelect.options[
                    groupSelect.selectedIndex
                ];


            if (!groupSelect.value) {

                postLocationHelp.textContent =
                    'Your post will appear in the main Community feed.';

            }
            else {

                postLocationHelp.textContent =
                    'Your post will appear in ' +
                    selectedOption.text +
                    ' only.';

            }

        }


        groupSelect.addEventListener(
            'change',
            updatePostLocationMessage
        );


        updatePostLocationMessage();

    }



    /* ===================================================
       EXPERIENCE SEARCH ELEMENTS
    =================================================== */

    const experienceSearch =
        document.getElementById('experienceSearch');

    const experienceSelect =
        document.getElementById('experience_id');

    const experienceMessage =
        document.getElementById('experienceSearchMessage');

    const experienceResults =
        document.getElementById('experienceSearchResults');



    /* ===================================================
       EXPERIENCE SEARCH
    =================================================== */

    if (
        experienceSearch &&
        experienceSelect &&
        experienceResults
    ) {


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



                /* ===================================================
                   LOOP THROUGH EXPERIENCES
                =================================================== */

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



                        /* ===================================================
                           GET EXPERIENCE DATA
                        =================================================== */

                        const name =
                            option.dataset.name || '';

                        const location =
                            option.dataset.location || '';

                        const type =
                            option.dataset.type || '';

                        const category =
                            option.dataset.category || '';



                        /* ===================================================
                           CREATE RESULT ITEM
                        =================================================== */

                        const resultItem =
                            document.createElement('button');


                        resultItem.type = 'button';

                        resultItem.className =
                            'experience-result-item';



                        /* ===================================================
                           RESULT CONTENT
                        =================================================== */

                        let metaParts = [];


                        if (location) {

                            metaParts.push(
                                '📍 ' + location
                            );

                        }


                        if (type) {

                            metaParts.push(
                                type
                            );

                        }


                        if (category) {

                            metaParts.push(
                                category
                            );

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



                        /* ===================================================
                           CLICK RESULT
                        =================================================== */

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



                /* ===================================================
                   SEARCH MESSAGE
                =================================================== */

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



        /* ===================================================
           SELECT CHANGE
        =================================================== */

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



        /* ===================================================
           OLD SELECTED EXPERIENCE
        =================================================== */

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

    }



    /* ===================================================
       IMAGE UPLOAD
    =================================================== */

    const imageInput =
        document.getElementById('imageInput');

    const preview =
        document.getElementById('imagePreview');

    const photoPreviewPanel =
        document.getElementById('photoPreviewPanel');

    const photoCount =
        document.getElementById('photoCount');


    let selectedFiles = [];



    /* ===================================================
       IMAGE SELECTION
    =================================================== */

    if (imageInput) {

        imageInput.addEventListener(
            'change',
            function () {


                const newFiles =
                    Array.from(this.files);


                /* ===================================================
                   MAXIMUM 10 PHOTOS
                =================================================== */

                if (
                    selectedFiles.length
                    + newFiles.length
                    > 10
                ) {

                    alert(
                        'You can upload a maximum of 10 photos.'
                    );

                    this.value = '';

                    return;

                }


                /* ===================================================
                   ADD NEW FILES
                =================================================== */

                selectedFiles.push(
                    ...newFiles
                );


                renderPreview();

                updateFileInput();

            }
        );

    }



    /* ===================================================
       RENDER IMAGE PREVIEW
    =================================================== */

    function renderPreview() {


        if (!preview) {

            return;

        }


        preview.innerHTML = '';



        /* ===================================================
           SHOW / HIDE PREVIEW PANEL
        =================================================== */

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



        /* ===================================================
           UPDATE PHOTO COUNT
        =================================================== */

        if (photoCount) {

            photoCount.textContent =
                `(${selectedFiles.length}/10)`;

        }



        /* ===================================================
           RENDER EACH IMAGE
        =================================================== */

        selectedFiles.forEach(
            function (file, index) {


                const reader =
                    new FileReader();


                reader.onload =
                    function (event) {


                        const div =
                            document.createElement('div');


                        div.className =
                            'preview-item';


                        div.innerHTML = `

                            <img
                                src="${event.target.result}"
                                alt="Selected photo">


                            <button
                                type="button"
                                class="remove-image"
                                data-index="${index}"
                                aria-label="Remove photo">

                                ×

                            </button>

                        `;


                        preview.appendChild(div);

                    };


                reader.readAsDataURL(file);

            }
        );

    }



    /* ===================================================
       REMOVE IMAGE
    =================================================== */

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

                }

            }
        );

    }



    /* ===================================================
       UPDATE FILE INPUT
    =================================================== */

    function updateFileInput() {


        if (!imageInput) {

            return;

        }


        const dataTransfer =
            new DataTransfer();


        selectedFiles.forEach(
            function (file) {

                dataTransfer.items.add(file);

            }
        );


        imageInput.files =
            dataTransfer.files;

    }



    /* ===================================================
       PREVENT DOUBLE SUBMISSION
    =================================================== */

    const createPostForm =
        document.getElementById('createPostForm');

    const publishButton =
        document.getElementById('publishButton');


    if (
        createPostForm &&
        publishButton
    ) {

        createPostForm.addEventListener(
            'submit',
            function () {

                publishButton.disabled = true;

                publishButton.textContent =
                    'Publishing...';

            }
        );

    }

});

</script>

@endpush