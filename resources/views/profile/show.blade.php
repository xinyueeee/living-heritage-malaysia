@extends('layouts.app')

@section('title', 'My Profile - Living Heritage Malaysia')

@section('content')
    <section class="profile-hero">
        <div class="profile-hero-illustration" aria-hidden="true">
            <svg viewBox="0 0 400 220" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice">
                <style>
                    .ph-line { fill: none; stroke: var(--primary); stroke-width: 3; stroke-linecap: round; stroke-linejoin: round; }
                    .ph-accent { fill: none; stroke: var(--gold); stroke-width: 3; stroke-linecap: round; stroke-linejoin: round; }
                </style>
                <line class="ph-line" x1="0" y1="190" x2="400" y2="190" />
                <path class="ph-line" d="M20 190v-60h30v-20h30v20h30v60" />
                <path class="ph-accent" d="M110 190v-90l30-25 30 25v90" />
                <path class="ph-line" d="M190 190V80h60v110" />
                <path class="ph-accent" d="M200 80v-25l10-15 10 15v25" />
                <path class="ph-accent" d="M230 80v-25l10-15 10 15v25" />
                <path class="ph-line" d="M270 190v-70l25-20 25 20v70" />
                <circle class="ph-accent" cx="60" cy="50" r="12" />
                <circle class="ph-accent" cx="340" cy="70" r="9" />
            </svg>
        </div>
        <div class="container profile-hero-content">
            <h1>My Profile</h1>
            <p>Manage your information, interests and track your cultural journey.</p>
        </div>
    </section>

    <div class="container profile-layout">
        @include('profile.partials.sidebar', ['active' => 'overview'])

        <div>
            <div class="profile-card profile-header-card">
                @php
                    $photoItems = $photoHistory->map(fn ($photo) => [
                        'id' => $photo->profile_photo_id,
                        'url' => $photo->photo_url,
                        'uploaded_at' => optional($photo->uploaded_at)->format('j M Y'),
                        'is_current' => $photo->photo_url === $user->profile_photo,
                    ])->values();

                    if ($photoItems->isEmpty() && $user->profile_photo) {
                        $photoItems = collect([[
                            'id' => null,
                            'url' => $user->profile_photo,
                            'uploaded_at' => null,
                            'is_current' => true,
                        ]]);
                    }
                @endphp
                <div class="profile-avatar-wrap" data-photo-upload data-photo-history='@json($photoItems)'>
                    @if ($user->profile_photo)
                        <button type="button" class="profile-avatar-view-trigger" data-action="view" aria-label="View profile photo">
                            <img class="profile-avatar-lg" src="{{ $user->profile_photo }}" alt="" data-avatar-image>
                        </button>
                    @else
                        <span class="profile-avatar-lg profile-avatar-lg-fallback" data-avatar-fallback>{{ \Illuminate\Support\Str::substr($user->user_name ?? '?', 0, 1) }}</span>
                    @endif
                    <button type="button" class="profile-avatar-camera" aria-label="Change profile photo" data-action="edit">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                    </button>
                    <input type="file" accept="image/jpeg,image/png,image/webp" hidden data-photo-input>
                    <p class="profile-avatar-error" data-avatar-error hidden></p>
                </div>

                <div class="profile-header-info">
                    <h2>{{ $user->user_name ?? 'Tourist' }}</h2>
                    <p class="profile-meta">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16v16H4z"/><path d="M22 6l-10 7L2 6"/></svg>
                        {{ $user->user_email }}
                    </p>
                    <p class="profile-meta">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h18"/></svg>
                        Member since {{ $user->created_at?->format('F Y') ?? '—' }}
                    </p>
                </div>

                <a href="{{ route('profile.personal-information') }}" class="profile-edit-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.85 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5z"/></svg>
                    Edit Profile
                </a>
            </div>

            <div class="profile-stats">
                <div class="profile-stat-tile">
                    <span class="profile-stat-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                    </span>
                    <div>
                        <p class="profile-stat-value">{{ $stats['experiences_completed'] }}</p>
                        <p class="profile-stat-label">Experiences Completed</p>
                    </div>
                </div>
                <div class="profile-stat-tile">
                    <span class="profile-stat-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15 15 0 0 1 0 20 15 15 0 0 1 0-20z"/></svg>
                    </span>
                    <div>
                        <p class="profile-stat-value">{{ $stats['passport_stamps'] }}</p>
                        <p class="profile-stat-label">Passport Stamps</p>
                    </div>
                </div>
                <div class="profile-stat-tile">
                    <span class="profile-stat-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="6"/><path d="M9 13.5 7 22l5-3 5 3-2-8.5"/></svg>
                    </span>
                    <div>
                        <p class="profile-stat-value">{{ $stats['badges_earned'] }}</p>
                        <p class="profile-stat-label">Badges Earned</p>
                    </div>
                </div>
            </div>

            <div class="profile-columns">
                <div class="profile-card">
                    <div class="profile-card-header-row">
                        <h3>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.8 4.6a5 5 0 0 0-7.1 0L12 6.3l-1.7-1.7a5 5 0 0 0-7.1 7.1L12 21l8.8-9.3a5 5 0 0 0 0-7.1z"/></svg>
                            My Interests
                        </h3>
                        <a href="{{ route('profile.interests') }}" class="profile-card-header-link">Edit</a>
                    </div>

                    @if ($interests->isEmpty())
                        <p class="profile-empty">You haven't selected any cultural interests yet.</p>
                    @else
                        <div class="profile-interest-chips">
                            @foreach ($interests as $interest)
                                <span class="profile-interest-chip">{{ $interest }}</span>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="profile-card">
                    <div class="profile-card-header-row">
                        <h3>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 21h8"/><path d="M12 17v4"/><path d="M7 4h10v5a5 5 0 0 1-10 0z"/></svg>
                            Recent Achievements
                        </h3>
                        <a href="{{ route('profile.achievements') }}" class="profile-card-header-link">View All</a>
                    </div>

                    @if ($achievements->isEmpty())
                        <p class="profile-empty">No achievements unlocked yet — keep exploring!</p>
                    @else
                        <div class="profile-achievement-list">
                            @foreach ($achievements as $achievement)
                                <div class="profile-achievement-item">
                                    <span class="profile-achievement-icon" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="6"/><path d="M9 13.5 7 22l5-3 5 3-2-8.5"/></svg>
                                    </span>
                                    <div class="profile-achievement-info">
                                        <p class="profile-achievement-title">{{ $achievement->badge_name }}</p>
                                        @if ($achievement->description)
                                            <p class="profile-achievement-desc">{{ $achievement->description }}</p>
                                        @endif
                                    </div>
                                    <span class="profile-achievement-date">{{ \Illuminate\Support\Carbon::parse($achievement->unlocked_date)->format('j M Y') }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div id="profilePhotoModal" class="profile-photo-modal">
        <div class="profile-photo-modal-content">
            <button type="button" class="profile-photo-modal-close" id="profilePhotoModalClose" aria-label="Close">&times;</button>

            <div class="profile-photo-modal-main">
                <button type="button" class="profile-photo-modal-nav profile-photo-modal-prev" id="profilePhotoModalPrev" aria-label="Previous photo">‹</button>
                <img id="profilePhotoModalImage" src="" alt="Profile photo">
                <button type="button" class="profile-photo-modal-nav profile-photo-modal-next" id="profilePhotoModalNext" aria-label="Next photo">›</button>
            </div>

            <div class="profile-photo-modal-meta">
                <span id="profilePhotoModalDate"></span>
                <button type="button" class="button button-primary" id="profilePhotoModalSetCurrent" hidden>Set as current photo</button>
            </div>

            <div class="profile-photo-modal-thumbs" id="profilePhotoModalThumbs"></div>
        </div>
    </div>

    @push('scripts')
        @vite(['resources/js/pages/profile-photo.js', 'resources/js/pages/profile-photo-history.js'])
    @endpush
@endsection
