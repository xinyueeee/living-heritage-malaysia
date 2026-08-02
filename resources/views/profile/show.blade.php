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
        <nav class="profile-sidebar" aria-label="Profile navigation">
            <a href="{{ route('profile') }}" class="active">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 4-6 8-6s8 2 8 6"/></svg>
                Overview
            </a>
            <a href="#" aria-disabled="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 4-6 8-6s8 2 8 6"/></svg>
                Personal Information
            </a>
            <a href="#" aria-disabled="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.8 4.6a5 5 0 0 0-7.1 0L12 6.3l-1.7-1.7a5 5 0 0 0-7.1 7.1L12 21l8.8-9.3a5 5 0 0 0 0-7.1z"/></svg>
                Interests
            </a>
            <a href="#" aria-disabled="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
                Saved Posts
            </a>
            <a href="#" aria-disabled="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 21h8"/><path d="M12 17v4"/><path d="M7 4h10v5a5 5 0 0 1-10 0z"/><path d="M7 6H4a1 1 0 0 0-1 1 4 4 0 0 0 4 4"/><path d="M17 6h3a1 1 0 0 1 1 1 4 4 0 0 1-4 4"/></svg>
                Achievements &amp; Stats
            </a>
            <a href="#" aria-disabled="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                Feedback &amp; Support
            </a>

            <div class="sidebar-divider"></div>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="sidebar-logout">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>
                    Logout
                </button>
            </form>
        </nav>

        <div>
            <div class="profile-card profile-header-card">
                <div class="profile-avatar-wrap">
                    @if ($user->profile_photo)
                        <img class="profile-avatar-lg" src="{{ $user->profile_photo }}" alt="">
                    @else
                        <span class="profile-avatar-lg profile-avatar-lg-fallback">{{ \Illuminate\Support\Str::substr($user->user_name ?? '?', 0, 1) }}</span>
                    @endif
                    <span class="profile-avatar-camera" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                    </span>
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

                <button type="button" class="profile-edit-btn" aria-disabled="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.85 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5z"/></svg>
                    Edit Profile
                </button>
            </div>

            <div class="profile-stats">
                <div class="stat-tile">
                    <span class="stat-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                    </span>
                    <div>
                        <p class="stat-value">{{ $stats['experiences_completed'] }}</p>
                        <p class="stat-label">Experiences Completed</p>
                    </div>
                </div>
                <div class="stat-tile">
                    <span class="stat-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15 15 0 0 1 0 20 15 15 0 0 1 0-20z"/></svg>
                    </span>
                    <div>
                        <p class="stat-value">{{ $stats['passport_stamps'] }}</p>
                        <p class="stat-label">Passport Stamps</p>
                    </div>
                </div>
                <div class="stat-tile">
                    <span class="stat-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="6"/><path d="M9 13.5 7 22l5-3 5 3-2-8.5"/></svg>
                    </span>
                    <div>
                        <p class="stat-value">{{ $stats['badges_earned'] }}</p>
                        <p class="stat-label">Badges Earned</p>
                    </div>
                </div>
            </div>

            <div class="profile-columns">
                <div class="profile-card">
                    <div class="card-header-row">
                        <h3>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.8 4.6a5 5 0 0 0-7.1 0L12 6.3l-1.7-1.7a5 5 0 0 0-7.1 7.1L12 21l8.8-9.3a5 5 0 0 0 0-7.1z"/></svg>
                            My Interests
                        </h3>
                        <span class="card-header-link" aria-disabled="true">Edit</span>
                    </div>

                    @if ($interests->isEmpty())
                        <p class="profile-empty">You haven't selected any cultural interests yet.</p>
                    @else
                        <div class="interest-chips">
                            @foreach ($interests as $interest)
                                <span class="interest-chip">{{ $interest }}</span>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="profile-card">
                    <div class="card-header-row">
                        <h3>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 21h8"/><path d="M12 17v4"/><path d="M7 4h10v5a5 5 0 0 1-10 0z"/></svg>
                            Recent Achievements
                        </h3>
                        <span class="card-header-link" aria-disabled="true">View All</span>
                    </div>

                    @if ($achievements->isEmpty())
                        <p class="profile-empty">No achievements unlocked yet — keep exploring!</p>
                    @else
                        <div class="achievement-list">
                            @foreach ($achievements as $achievement)
                                <div class="achievement-item">
                                    <span class="achievement-icon" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="6"/><path d="M9 13.5 7 22l5-3 5 3-2-8.5"/></svg>
                                    </span>
                                    <div class="achievement-info">
                                        <p class="title">{{ $achievement->badge_name }}</p>
                                        @if ($achievement->description)
                                            <p class="desc">{{ $achievement->description }}</p>
                                        @endif
                                    </div>
                                    <span class="achievement-date">{{ \Illuminate\Support\Carbon::parse($achievement->unlocked_date)->format('j M Y') }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <div class="profile-feedback-banner">
                <div>
                    <h3>Have feedback or need help?</h3>
                    <p>We'd love to hear from you!</p>
                </div>
                <button type="button" class="button button-primary" aria-disabled="true">Go to Feedback &amp; Support →</button>
            </div>
        </div>
    </div>
@endsection
