@extends('layouts.app')

@section('title', 'Add Photos to ' . $album->album_name . ' - Living Heritage Malaysia')

@push('styles')
    @vite('resources/css/albums.css')
@endpush

@section('content')

<div class="album-page album-create-page">

    <div class="album-form-card">

        <a href="{{ route('profile.albums.show', $album->album_id) }}" class="album-back-link">
            ← Back to {{ $album->album_name }}
        </a>

        <div class="album-form-header">
            <p class="album-eyebrow">ADD PHOTOS</p>
            <h1>Add Photos to {{ $album->album_name }}</h1>
            <p>Select photos from your device to add to this album.</p>
        </div>

        @if($errors->any())
            <div class="album-error-message" role="alert">
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form 
            action="{{ route('profile.albums.photos.store', $album->album_id) }}" 
            method="POST" 
            enctype="multipart/form-data"
            class="album-form"
            id="photoUploadForm"
        >
            @csrf

            <div class="album-form-group">
                <label for="photos">
                    Select Photos <span class="required-asterisk">*</span>
                    <span style="font-weight: normal; font-size: 0.8rem; color: var(--muted); margin-left: 8px;">
                        (Multiple photos allowed)
                    </span>
                </label>
                
                <!-- File Input with better multi-file support -->
                <div class="album-drop-zone" id="dropZone">
                    <div class="album-drop-zone-content">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 12px; color: #9d3d2d;">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                            <circle cx="8.5" cy="8.5" r="1.5"/>
                            <polyline points="21 15 16 10 5 21"/>
                        </svg>
                        <p style="font-size: 1rem; font-weight: 600; color: var(--ink); margin: 0 0 4px;">
                            Drag & drop your photos here
                        </p>
                        <p style="font-size: 0.85rem; color: var(--muted); margin: 0 0 4px;">
                            or click to browse
                        </p>
                        <p style="font-size: 0.8rem; color: var(--primary); font-weight: 600; margin: 4px 0;">
                            📸 Select multiple photos
                        </p>
                        <span style="font-size: 0.75rem; color: var(--muted);">
                            Supports JPG, PNG, GIF up to 5MB each
                        </span>
                    </div>
                    
                    <input
                        type="file"
                        id="photos"
                        name="photos[]"
                        accept="image/*"
                        multiple
                        required
                        class="album-file-input"
                        style="position: absolute; inset: 0; opacity: 0; cursor: pointer;"
                    >
                </div>

                <!-- Selected Files Count -->
                <div id="fileCount" style="font-size: 0.85rem; color: var(--primary); font-weight: 600; margin-top: 8px; display: none;">
                    <span id="fileCountNumber">0</span> photo(s) selected
                </div>

                <!-- File Preview Grid -->
                <div id="filePreview" style="display: none; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 12px; margin-top: 12px;"></div>

                @error('photos')
                    <span class="album-error-text">{{ $message }}</span>
                @enderror
                @error('photos.*')
                    <span class="album-error-text">{{ $message }}</span>
                @enderror

                <small class="album-help-text">
                    You can select multiple photos at once. Max 5MB per photo.
                </small>
            </div>

            <div class="album-form-actions">
                <a href="{{ route('profile.albums.show', $album->album_id) }}" class="album-secondary-btn">
                    Cancel
                </a>

                <button type="submit" class="album-primary-btn" id="uploadBtn">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
                    </svg>
                    Upload <span id="uploadCount">0</span> Photos
                </button>
            </div>

        </form>

    </div>

</div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('photos');
        const previewContainer = document.getElementById('filePreview');
        const uploadBtn = document.getElementById('uploadBtn');
        const uploadCount = document.getElementById('uploadCount');
        const fileCount = document.getElementById('fileCount');
        const fileCountNumber = document.getElementById('fileCountNumber');

        // Drag and drop events
        dropZone.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.style.borderColor = '#9d3d2d';
            this.style.background = '#fdf3ed';
        });

        dropZone.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.style.borderColor = 'var(--border)';
            this.style.background = 'var(--surface)';
        });

        dropZone.addEventListener('drop', function(e) {
            e.preventDefault();
            this.style.borderColor = 'var(--border)';
            this.style.background = 'var(--surface)';
            
            if (e.dataTransfer.files.length) {
                fileInput.files = e.dataTransfer.files;
                previewFiles(fileInput.files);
            }
        });

        // File input change
        fileInput.addEventListener('change', function() {
            if (this.files.length) {
                previewFiles(this.files);
            }
        });

        // Preview function
        function previewFiles(files) {
            previewContainer.innerHTML = '';
            
            if (files.length === 0) {
                previewContainer.style.display = 'none';
                fileCount.style.display = 'none';
                uploadCount.textContent = '0';
                uploadBtn.innerHTML = `
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
                    </svg>
                    Upload Photos
                `;
                return;
            }

            // Show file count
            fileCount.style.display = 'block';
            fileCountNumber.textContent = files.length;
            uploadCount.textContent = files.length;
            uploadBtn.innerHTML = `
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
                </svg>
                Upload ${files.length} Photos
            `;

            // Show preview
            previewContainer.style.display = 'grid';

            // Limit preview to first 20 images
            const maxPreview = Math.min(files.length, 20);
            const remaining = files.length - maxPreview;

            for (let i = 0; i < maxPreview; i++) {
                const file = files[i];
                const reader = new FileReader();

                const div = document.createElement('div');
                div.style.cssText = `
                    position: relative;
                    aspect-ratio: 1;
                    border-radius: 8px;
                    overflow: hidden;
                    background: #f2ece6;
                    border: 2px solid var(--border);
                    transition: transform 0.2s ease;
                `;

                div.onmouseover = function() { this.style.transform = 'scale(1.05)'; };
                div.onmouseout = function() { this.style.transform = 'scale(1)'; };

                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.style.cssText = `
                        width: 100%;
                        height: 100%;
                        object-fit: cover;
                    `;
                    div.appendChild(img);

                    // Remove button
                    const remove = document.createElement('button');
                    remove.innerHTML = '×';
                    remove.style.cssText = `
                        position: absolute;
                        top: 4px;
                        right: 4px;
                        width: 24px;
                        height: 24px;
                        border-radius: 50%;
                        border: none;
                        background: rgba(220, 53, 69, 0.85);
                        color: white;
                        font-size: 16px;
                        cursor: pointer;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        transition: transform 0.2s ease;
                        z-index: 10;
                    `;
                    remove.onmouseover = function() { this.style.transform = 'scale(1.1)'; };
                    remove.onmouseout = function() { this.style.transform = 'scale(1)'; };
                    remove.onclick = function(e) {
                        e.stopPropagation();
                        // Remove file from input
                        const dt = new DataTransfer();
                        for (let j = 0; j < fileInput.files.length; j++) {
                            if (j !== i) {
                                dt.items.add(fileInput.files[j]);
                            }
                        }
                        fileInput.files = dt.files;
                        previewFiles(fileInput.files);
                    };
                    div.appendChild(remove);
                };

                reader.readAsDataURL(file);
                previewContainer.appendChild(div);
            }

            // Show "and more" indicator
            if (remaining > 0) {
                const moreDiv = document.createElement('div');
                moreDiv.style.cssText = `
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    aspect-ratio: 1;
                    border-radius: 8px;
                    background: #f2ece6;
                    border: 2px dashed var(--border);
                    font-size: 0.85rem;
                    color: var(--muted);
                    font-weight: 600;
                `;
                moreDiv.textContent = `+${remaining} more`;
                previewContainer.appendChild(moreDiv);
            }
        }

        // Form submit - show loading state
        document.getElementById('photoUploadForm').addEventListener('submit', function() {
            const btn = document.getElementById('uploadBtn');
            btn.innerHTML = '⏳ Uploading...';
            btn.disabled = true;
        });

        // Handle multiple file selection from file dialog
        fileInput.setAttribute('multiple', 'multiple');
    });
</script>
@endpush