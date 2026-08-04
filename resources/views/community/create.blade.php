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
                        placeholder="Share your experience, stories, tips, or recommendations..."
                        required>{{ old('content') }}</textarea>

                    <div class="character-counter">
                        <span id="charCount">{{ strlen(old('content')) }}</span> / 2000
                    </div>
                </div>

                <!-- Upload -->
                <div class="form-group">

                    <label>
                        Upload Photos
                        <span class="optional">(Optional)</span>
                    </label>

                    <div class="upload-box">

                        <div class="upload-icon">
                            +
                        </div>

                        <h3>Add Photos</h3>

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

                    <div class="upload-note">
                        Supported formats:
                        JPG, PNG, WEBP • Maximum size:
                        10MB per photo
                    </div>

                    <div id="imagePreview" class="image-preview"></div>

                    <div class="upload-limit">
                        Maximum 10 photos allowed.
                    </div>

                </div>

                <!-- Buttons -->
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

@push('scripts')

<script>

const textarea = document.getElementById('content');
const counter = document.getElementById('charCount');

textarea.addEventListener('input', () => {
    counter.textContent = textarea.value.length;
});

const imageInput = document.getElementById('imageInput');
const preview = document.getElementById('imagePreview');

let selectedFiles = [];

imageInput.addEventListener('change', function () {

    const newFiles = Array.from(this.files);

    if (selectedFiles.length + newFiles.length > 10) {
        alert("You can upload a maximum of 10 photos.");
        this.value = "";
        return;
    }

    selectedFiles.push(...newFiles);

    renderPreview();

    updateFileInput();

});

function renderPreview() {

    preview.innerHTML = "";

    selectedFiles.forEach((file, index) => {

        const reader = new FileReader();

        reader.onload = function (e) {

            const div = document.createElement("div");

            div.className = "preview-item";

            div.innerHTML = `
                <img src="${e.target.result}">
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

preview.addEventListener("click", function (e) {

    if (e.target.classList.contains("remove-image")) {

        selectedFiles.splice(e.target.dataset.index, 1);

        renderPreview();

        updateFileInput();

    }

});

function updateFileInput() {

    const dataTransfer = new DataTransfer();

    selectedFiles.forEach(file => {
        dataTransfer.items.add(file);
    });

    imageInput.files = dataTransfer.files;

}

</script>

@endpush