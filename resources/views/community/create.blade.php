@extends('layouts.app')

@section('title', 'Create Post | Living Heritage Malaysia')

@section('content')

<div class="community-page">
    <div class="container create-post-page">
        <!-- Back Button -->
        <a href="{{ route('community.index') }}" class="back-link">
            ← Back to Community
        </a>
        <!-- Page Header -->
        <div class="create-header">
            <h1>Create Post</h1>
            <p>
                Share your cultural experience with the community.
            </p>
        </div>
        <!-- Create Post Card -->
        <div class="create-card">
            <form 
                id="createPostForm"
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
                        required
                    ></textarea>
                    <div class="character-counter">
                         <span id="charCount">0</span> / 2000
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
                            accept="image/*"
                        >
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
                    <a href="{{ route('community.index') }}"
                        class="cancel-btn">
                        Cancel
                    </a>
                    <button type="submit"
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

const textarea = document.getElementById("content");
const counter = document.getElementById("charCount");

textarea.addEventListener("input", () => {
    counter.textContent = textarea.value.length;
});

const imageInput = document.getElementById("imageInput");
const preview = document.getElementById("imagePreview");

let selectedFiles = [];

imageInput.addEventListener("change", function () {

    const newFiles = Array.from(this.files);

    if (selectedFiles.length + newFiles.length > 10) {
        alert("You can upload a maximum of 10 photos.");
        this.value = "";
        return;
    }

    selectedFiles.push(...newFiles);

    renderPreview();

    this.value = "";
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
                <button type="button"
                        class="remove-image"
                        data-index="${index}">
                    ×
                </button>
            `;

            preview.appendChild(div);

        }

        reader.readAsDataURL(file);

    });

}

preview.addEventListener("click", function (e) {

    if (e.target.classList.contains("remove-image")) {

        const index = e.target.dataset.index;

        selectedFiles.splice(index,1);

        renderPreview();

    }

});

const form = document.getElementById("createPostForm");

form.addEventListener("submit", async function (e) {

    e.preventDefault();

    const formData = new FormData();

    formData.append("content", textarea.value);

    selectedFiles.forEach(file => {
        formData.append("images[]", file);
    });

    formData.append("_token", document.querySelector('meta[name="csrf-token"]').content);

    const response = await fetch(form.action, {
        method: "POST",
        body: formData
    });

    const result = await response.text();

    document.body.innerHTML = result;

});

</script>

@endpush