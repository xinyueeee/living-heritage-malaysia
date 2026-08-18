@extends('layouts.app')

@section('title', 'Feedback & Support - Living Heritage Malaysia')

@section('content')
    <section class="profile-hero">
        <div class="container profile-hero-content">
            <h1>Feedback &amp; Support</h1>
            <p>We value your feedback! Let us know how we can improve your experience.</p>
        </div>
    </section>

    <div class="container profile-layout">
        @include('profile.partials.sidebar', ['active' => 'feedback'])

        <div>
            <div class="profile-card">
                <div class="profile-card-header-row">
                    <h3>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                        Send Feedback or Report an Issue
                    </h3>
                </div>
                <p>Let us know your thoughts or report any issues you encounter. Your feedback helps us improve and provide better experiences for everyone.</p>

                @if (session('status'))
                    <p class="profile-saved-status" role="status">{{ session('status') }}</p>
                @endif

                <form method="POST" action="{{ route('profile.feedback.store') }}" enctype="multipart/form-data">
                    @csrf

                    <div class="feedback-form-group">
                        <label for="subject">Subject <span class="feedback-required">*</span></label>
                        <select id="subject" name="subject" class="profile-field-input">
                            <option value="" disabled {{ old('subject') ? '' : 'selected' }}>Select a subject</option>
                            @foreach ($subjectOptions as $option)
                                <option value="{{ $option }}" {{ old('subject') === $option ? 'selected' : '' }}>{{ $option }}</option>
                            @endforeach
                        </select>
                        @error('subject')
                            <p class="profile-field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="feedback-form-group">
                        <label for="description">Description <span class="feedback-required">*</span></label>
                        <textarea id="description" name="description" rows="5" maxlength="1000" class="profile-field-input profile-field-textarea" placeholder="Please provide detailed information...">{{ old('description') }}</textarea>
                        <div class="feedback-char-count"><span id="feedbackCharCount">{{ strlen(old('description', '')) }}</span> / 1000</div>
                        @error('description')
                            <p class="profile-field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="feedback-form-group">
                        <label>Attach Images <span class="optional">(Optional)</span></label>
                        <p class="form-help">Upload screenshots or images to help us understand the issue better.</p>

                        <div class="photo-upload-row">
                            <div class="upload-box">
                                <div class="upload-icon">+</div>
                                <h3>Click to upload or drag and drop</h3>
                                <p>PNG, JPG, JPEG up to 5MB</p>
                                <small>Maximum 5 images</small>
                                <input type="file" id="feedbackImageInput" name="images[]" multiple accept="image/jpeg,image/png,image/jpg">
                            </div>

                            <div id="feedbackPhotoPreviewPanel" class="photo-preview-panel">
                                <div class="photo-preview-header">
                                    <strong>Photo Preview</strong>
                                    <span id="feedbackPhotoCount">(0/5)</span>
                                </div>
                                <div id="feedbackImagePreview" class="image-preview"></div>
                            </div>
                        </div>
                        @error('images')
                            <p class="profile-field-error">{{ $message }}</p>
                        @enderror
                        @error('images.*')
                            <p class="profile-field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="feedback-form-group" style="display:flex; justify-content:flex-end; margin-bottom:0;">
                        <button type="submit" class="button button-primary">Submit</button>
                    </div>
                </form>
            </div>

            <div class="profile-card">
                <div class="profile-card-header-row">
                    <h3>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/></svg>
                        History
                    </h3>
                </div>
                <p>View your previous feedback and reported issues.</p>

                @if ($history->isEmpty())
                    <p class="profile-empty">You haven't submitted any feedback yet.</p>
                @else
                    <div class="feedback-history-table-wrap">
                        <table class="feedback-history-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Subject</th>
                                    <th>Description</th>
                                    <th>Attachments</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($history as $entry)
                                    <tr>
                                        <td>{{ $entry->submitted_at->timezone('Asia/Kuala_Lumpur')->format('j M Y, h:i A') }}</td>
                                        <td>{{ $entry->subject }}</td>
                                        <td class="feedback-history-desc">{{ \Illuminate\Support\Str::limit($entry->description, 80) }}</td>
                                        <td>
                                            @if ($entry->photos->isNotEmpty())
                                                <span class="feedback-history-attachments" data-images="{{ $entry->photos->pluck('file_path')->toJson() }}">
                                                    📷 {{ $entry->photos->count() }}
                                                </span>
                                            @else
                                                <span class="feedback-history-no-attachments">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <p class="feedback-history-count">
                        Showing {{ $history->firstItem() }} to {{ $history->lastItem() }} of {{ $history->total() }} entries
                    </p>

                    {{ $history->onEachSide(1)->links('components.pagination') }}
                @endif
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const textarea = document.getElementById('description');
                const counter = document.getElementById('feedbackCharCount');

                if (textarea && counter) {
                    textarea.addEventListener('input', function () {
                        counter.textContent = this.value.length;
                    });
                }

                const imageInput = document.getElementById('feedbackImageInput');
                const preview = document.getElementById('feedbackImagePreview');
                const previewPanel = document.getElementById('feedbackPhotoPreviewPanel');
                const photoCount = document.getElementById('feedbackPhotoCount');
                let selectedFiles = [];

                function renderPreview() {
                    if (!preview) return;

                    preview.innerHTML = '';
                    previewPanel?.classList.toggle('has-photos', selectedFiles.length > 0);

                    if (photoCount) {
                        photoCount.textContent = `(${selectedFiles.length}/5)`;
                    }

                    selectedFiles.forEach(function (file, index) {
                        const reader = new FileReader();

                        reader.onload = function (event) {
                            const div = document.createElement('div');
                            div.className = 'feedback-preview-item';
                            div.innerHTML = `
                                <img src="${event.target.result}" alt="Selected image">
                                <button type="button" class="remove-image" data-index="${index}" aria-label="Remove image">×</button>
                            `;
                            preview.appendChild(div);
                        };

                        reader.readAsDataURL(file);
                    });
                }

                function updateFileInput() {
                    if (!imageInput) return;

                    const dataTransfer = new DataTransfer();
                    selectedFiles.forEach((file) => dataTransfer.items.add(file));
                    imageInput.files = dataTransfer.files;
                }

                if (imageInput) {
                    imageInput.addEventListener('change', function () {
                        const newFiles = Array.from(this.files);

                        if (selectedFiles.length + newFiles.length > 5) {
                            alert('You can upload a maximum of 5 images.');
                            this.value = '';
                            return;
                        }

                        selectedFiles.push(...newFiles);
                        renderPreview();
                        updateFileInput();
                    });
                }

                preview?.addEventListener('click', function (event) {
                    if (event.target.classList.contains('remove-image')) {
                        const index = parseInt(event.target.dataset.index);
                        selectedFiles.splice(index, 1);
                        renderPreview();
                        updateFileInput();
                    }
                });

                const attachmentsPopup = document.createElement('div');
                attachmentsPopup.className = 'feedback-history-attachments-popup';
                document.body.appendChild(attachmentsPopup);

                const attachmentBadges = document.querySelectorAll('.feedback-history-attachments');

                // Preload attachment images so the popup already knows their size on first hover.
                attachmentBadges.forEach(function (badge) {
                    JSON.parse(badge.dataset.images || '[]').forEach((src) => {
                        const preloadImg = new Image();
                        preloadImg.src = src;
                    });
                });

                function positionAttachmentsPopup(badge) {
                    const rect = badge.getBoundingClientRect();
                    const popupRect = attachmentsPopup.getBoundingClientRect();

                    let left = Math.min(rect.left, window.innerWidth - popupRect.width - 12);
                    left = Math.max(left, 12);

                    let top = rect.top - popupRect.height - 8;
                    if (top < 8) {
                        top = rect.bottom + 8;
                    }

                    attachmentsPopup.style.left = `${left}px`;
                    attachmentsPopup.style.top = `${top}px`;
                }

                attachmentBadges.forEach(function (badge) {
                    badge.addEventListener('mouseenter', function () {
                        const images = JSON.parse(badge.dataset.images || '[]');
                        attachmentsPopup.innerHTML = images.map((src) => `<img src="${src}" alt="Attached image">`).join('');
                        attachmentsPopup.style.display = 'flex';

                        positionAttachmentsPopup(badge);

                        attachmentsPopup.querySelectorAll('img').forEach((img) => {
                            if (!img.complete) {
                                img.addEventListener('load', () => positionAttachmentsPopup(badge), { once: true });
                            }
                        });
                    });

                    badge.addEventListener('mouseleave', function () {
                        attachmentsPopup.style.display = 'none';
                    });
                });
            });
        </script>
    @endpush
@endsection
