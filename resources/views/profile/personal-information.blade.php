@extends('layouts.app')

@section('title', 'Personal Information - Living Heritage Malaysia')

@section('content')
    <section class="profile-hero">
        <div class="container profile-hero-content">
            <h1>Personal Information</h1>
            <p>Update your details — one field at a time.</p>
        </div>
    </section>

    <div class="container profile-layout">
        @include('profile.partials.sidebar', ['active' => 'personal-information'])

        <div>
            <div class="profile-card">
                <div class="profile-field" data-field="user_name">
                    <div class="profile-field-label">Full Name</div>
                    <div class="profile-field-display">
                        <span class="profile-field-value" data-raw="{{ $user->user_name }}">{{ $user->user_name }}</span>
                        <button type="button" class="profile-field-edit-btn" data-action="edit" aria-label="Edit full name">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.85 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5z"/></svg>
                        </button>
                        <span class="profile-field-success" hidden>Saved</span>
                    </div>
                    <div class="profile-field-form" hidden>
                        <input type="text" class="profile-field-input" maxlength="100" value="{{ $user->user_name }}">
                        <div class="profile-field-actions">
                            <button type="button" class="button button-primary" data-action="save">Save</button>
                            <button type="button" class="profile-field-cancel" data-action="cancel">Cancel</button>
                        </div>
                        <p class="profile-field-error" hidden></p>
                    </div>
                </div>

                <div class="profile-field" data-field="user_email">
                    <div class="profile-field-label">Email Address</div>
                    <div class="profile-field-display">
                        <span class="profile-field-value" data-raw="{{ $user->user_email }}">{{ $user->user_email }}</span>
                        <button type="button" class="profile-field-edit-btn" data-action="edit" aria-label="Edit email address">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.85 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5z"/></svg>
                        </button>
                        <span class="profile-field-success" hidden>Saved</span>
                    </div>
                    <div class="profile-field-form" hidden>
                        <input type="email" class="profile-field-input" maxlength="100" value="{{ $user->user_email }}">
                        <div class="profile-field-actions">
                            <button type="button" class="button button-primary" data-action="save">Save</button>
                            <button type="button" class="profile-field-cancel" data-action="cancel">Cancel</button>
                        </div>
                        <p class="profile-field-error" hidden></p>
                    </div>
                </div>

                <div class="profile-field" data-field="bio">
                    <div class="profile-field-label">Bio</div>
                    <div class="profile-field-display">
                        <span class="profile-field-value profile-field-value-multiline" data-raw="{{ $user->bio }}">{{ $user->bio ?: 'Not set yet.' }}</span>
                        <button type="button" class="profile-field-edit-btn" data-action="edit" aria-label="Edit bio">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.85 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5z"/></svg>
                        </button>
                        <span class="profile-field-success" hidden>Saved</span>
                    </div>
                    <div class="profile-field-form" hidden>
                        <textarea class="profile-field-input profile-field-textarea" maxlength="500" rows="4">{{ $user->bio }}</textarea>
                        <div class="profile-field-actions">
                            <button type="button" class="button button-primary" data-action="save">Save</button>
                            <button type="button" class="profile-field-cancel" data-action="cancel">Cancel</button>
                        </div>
                        <p class="profile-field-error" hidden></p>
                    </div>
                </div>

                <div class="profile-field" data-field="gender">
                    <div class="profile-field-label">Gender</div>
                    <div class="profile-field-display">
                        <span class="profile-field-value" data-raw="{{ $user->gender }}">{{ $user->gender ?: 'Not set yet.' }}</span>
                        <button type="button" class="profile-field-edit-btn" data-action="edit" aria-label="Edit gender">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.85 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5z"/></svg>
                        </button>
                        <span class="profile-field-success" hidden>Saved</span>
                    </div>
                    <div class="profile-field-form" hidden>
                        <select class="profile-field-input">
                            <option value="" @selected(! $user->gender)>Not set</option>
                            @foreach (['Male', 'Female', 'Other', 'Prefer not to say'] as $option)
                                <option value="{{ $option }}" @selected($user->gender === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                        <div class="profile-field-actions">
                            <button type="button" class="button button-primary" data-action="save">Save</button>
                            <button type="button" class="profile-field-cancel" data-action="cancel">Cancel</button>
                        </div>
                        <p class="profile-field-error" hidden></p>
                    </div>
                </div>

                <div class="profile-field profile-field-last" data-field="birthday">
                    <div class="profile-field-label">Birthday</div>
                    <div class="profile-field-display">
                        <span class="profile-field-value" data-raw="{{ $user->birthday?->format('Y-m-d') }}">{{ $user->birthday?->format('d M Y') ?? 'Not set yet.' }}</span>
                        <button type="button" class="profile-field-edit-btn" data-action="edit" aria-label="Edit birthday">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.85 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5z"/></svg>
                        </button>
                        <span class="profile-field-success" hidden>Saved</span>
                    </div>
                    <div class="profile-field-form" hidden>
                        <input type="date" class="profile-field-input" value="{{ $user->birthday?->format('Y-m-d') }}">
                        <div class="profile-field-actions">
                            <button type="button" class="button button-primary" data-action="save">Save</button>
                            <button type="button" class="profile-field-cancel" data-action="cancel">Cancel</button>
                        </div>
                        <p class="profile-field-error" hidden></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        @vite(['resources/js/pages/personal-information.js'])
    @endpush
@endsection
