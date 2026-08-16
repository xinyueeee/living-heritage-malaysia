@extends('layouts.app')

@section('title', 'Create Post | Living Heritage Malaysia')

@section('content')

<div class="community-page">

    <div class="container create-post-page">

        <!-- Back Button -->
        <a href="{{ route('community.index') }}" class="back-link">
            ← Back to Community
        </a>


        <!-- Header -->
        <div class="create-header">

            <h1>Create Post</h1>

            <p>
                Share your cultural experience with the community.
            </p>

        </div>


        <!-- Validation Errors -->
        @if ($errors->any())

            <div class="alert alert-danger">

                <ul style="margin:0;padding-left:20px;">

                    @foreach ($errors->all() as $error)

                        <li>{{ $error }}</li>

                    @endforeach

                </ul>

            </div>

        @endif


        <!-- Create Post Card -->
        <div class="create-card">

            <form
                action="{{ route('community.store') }}"
                method="POST"
                enctype="multipart/form-data">

                @csrf


                <!-- ===================================================
                     DYNAMIC FORM LAYOUT
                     =================================================== -->

                <div
                    id="postFormLayout"
                    class="create-form-layout">


                    <!-- ===================================================
                         LEFT SIDE
                         =================================================== -->

                    <div class="post-form-left">


                        <!-- Caption -->
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


                        <!-- Upload -->
                        <div class="form-group">

                            <label>

                                Upload Photos

                                <span class="optional">
                                    (Optional)
                                </span>

                            </label>


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
                                    (Maximum 10 photos)
                                </small>


                                <input
                                    type="file"
                                    id="imageInput"
                                    name="images[]"
                                    multiple
                                    accept="image/*">

                            </div>

                        </div>

                    </div>


                    <!-- ===================================================
                         RIGHT SIDE - PHOTO PREVIEW
                         =================================================== -->

                    <div
                        id="photoPreviewPanel"
                        class="photo-preview-panel">


                        <!-- Photo Preview Header -->
                        <div class="photo-preview-header">

                            <strong>
                                Photo Preview
                            </strong>

                            <span id="photoCount">
                                (0/10)
                            </span>

                        </div>


                        <!-- Selected Images -->
                        <div
                            id="imagePreview"
                            class="image-preview">
                        </div>


                    </div>

                </div>


                <!-- ===================================================
                     UPLOAD NOTE
                     =================================================== -->

                <div class="upload-note">

                    Supported formats:
                    JPG, PNG, WEBP
                    • Maximum size:
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
                        class="publish-btn">

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


// ===================================================
// CHARACTER COUNTER
// ===================================================

const textarea = document.getElementById('content');
const counter = document.getElementById('charCount');


textarea.addEventListener('input', () => {

    counter.textContent = textarea.value.length;

});


// ===================================================
// IMAGE UPLOAD
// ===================================================

const imageInput = document.getElementById('imageInput');

const preview = document.getElementById('imagePreview');

const formLayout = document.getElementById('postFormLayout');

const photoCount = document.getElementById('photoCount');


// Store selected files

let selectedFiles = [];


// ===================================================
// IMAGE SELECTION
// ===================================================

imageInput.addEventListener('change', function () {

    const newFiles = Array.from(this.files);


    // Check maximum 10 photos

    if (selectedFiles.length + newFiles.length > 10) {

        alert("You can upload a maximum of 10 photos.");

        this.value = "";

        return;

    }


    // Add new files

    selectedFiles.push(...newFiles);


    // Update UI

    renderPreview();

    updateFileInput();

});


// ===================================================
// RENDER IMAGE PREVIEW
// ===================================================

function renderPreview() {

    // Clear existing preview

    preview.innerHTML = "";


    // Update layout

    if (selectedFiles.length > 0) {

        formLayout.classList.add('has-photos');

    } else {

        formLayout.classList.remove('has-photos');

    }


    // Update photo counter

    photoCount.textContent =
        `(${selectedFiles.length}/10)`;


    // Render each image

    selectedFiles.forEach((file, index) => {

        const reader = new FileReader();


        reader.onload = function (e) {

            const div = document.createElement("div");

            div.className = "preview-item";


            div.innerHTML = `

                <img
                    src="${e.target.result}"
                    alt="Selected photo">

                <button
                    type="button"
                    class="remove-image"
                    data-index="${index}">

                    ×

                </button>

            `;


            preview.appendChild(div);

        };


        reader.readAsDataURL(file);

    });

}


// ===================================================
// REMOVE IMAGE
// ===================================================

preview.addEventListener("click", function (e) {

    if (e.target.classList.contains("remove-image")) {

        const index = parseInt(
            e.target.dataset.index
        );


        // Remove selected file

        selectedFiles.splice(index, 1);


        // Re-render preview

        renderPreview();

        updateFileInput();

    }

});


// ===================================================
// UPDATE FILE INPUT
// ===================================================

function updateFileInput() {

    const dataTransfer = new DataTransfer();


    selectedFiles.forEach(file => {

        dataTransfer.items.add(file);

    });


    imageInput.files = dataTransfer.files;

}

</script>

@endpush