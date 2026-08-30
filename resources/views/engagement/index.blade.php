@extends('layouts.app')

@section('title', 'Engagement & Rewards')

@push('styles')
    @vite('resources/css/engagement.css')
@endpush

@section('content')

<div class="engagement-page">

    <section class="hero-banner">
        <div class="hero-overlay">
            <div class="hero-content">
                <span class="hero-subtitle">ENGAGEMENT & REWARDS</span>
                <h1>Collect stamps <br>across Malaysia</h1>
                <p class="hero-text">
                    Completed a cultural experience? Share your journey
                    in the Community and select the experience you joined
                    to collect its category stamp and grow your achievement
                    progress.
                </p>

                <div class="engagement-hero-actions">
                    <a
                        href="{{ route('community.index') }}"
                        class="hero-btn"
                    >
                        Share an Experience
                        <span aria-hidden="true">→</span>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section class="progress-section container" id="cultural-journey">
        <div class="journey-section-header">
            <div>
                <h2>Your Cultural Journey</h2>

                <p>
                    Track your cultural experiences, stamps, and achievements.
                </p>
            </div>

            <a
                href="{{ route('engagement.leaderboard') }}"
                class="outline-btn"
            >
                View Overall Leaderboard
            </a>
        </div>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📅</div>

                <div>
                    <h3>{{ $experiencesThisMonthCount }}</h3>
                    <p>Experiences This Month</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">🎨</div>
                <div>
                    <h3>{{ $completedExperienceCount }}</h3>
                    <p>Experiences Completed</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">📖</div>
                <div>
                    <h3>{{ $passportStampCount }}</h3>
                    <p>Stamps Collected</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">🏆</div>
                <div>
                    <h3>{{ $achievements->where('is_unlocked', true)->count() }}</h3>
                    <p>Badges Earned</p>
                </div>
            </div>
        </div>
    </section>

    <section class="passport-section container">
        <div class="section-top">
            <div>
                <h2>Digital Cultural Passport</h2>
                <p>Your 8 most recently collected cultural stamps.</p>
            </div>

            <a href="{{ route('engagement.passport') }}" class="outline-btn">
                View Full Passport
            </a>
        </div>

        <div class="passport-showcase">
        <div class="passport-book">
            <img
                src="{{ asset('images/engagement/passport-book.webp') }}"
                class="passport-background"
                alt="Digital Cultural Passport"
            >

            <div class="passport-stamps">
                @forelse($latestPassportStamps as $userStamp)
                    <div class="passport-stamp">
                        <img
                            src="{{ $userStamp->stamp?->stamp_image
                                ? asset($userStamp->stamp->stamp_image)
                                : asset('images/default-stamp.png') }}"
                            alt="{{ $userStamp->stamp?->category ?? 'Passport stamp' }}"
                        >

                        <p>
                            {{ $userStamp->stamp?->category ?? '-' }}
                        </p>

                        <span>
                            {{ $userStamp->collected_date?->format('d M Y') ?? '-' }}
                        </span>
                    </div>
                @empty
                    <div class="empty-state">
                        <p>No passport stamps collected yet.</p>
                    </div>
                @endforelse
            </div>
        </div>

        <aside class="next-stamp-panel">
            @if ($nextStamp)
                <span class="next-stamp-eyebrow">
                    Recommended Stamp to Collect
                </span>

                <div class="next-stamp-image">
                    <img
                        src="{{ $nextStamp->stamp_image
                            ? asset($nextStamp->stamp_image)
                            : asset('images/default-stamp.png') }}"
                        alt="{{ $nextStamp->category }} stamp"
                    >

                    <span class="stamp-lock" aria-hidden="true">🔒</span>
                </div>

                <h3>
                    {{ $nextStamp->category ?? 'Cultural Stamp' }}
                </h3>

                <p>
                    Looking for your next cultural experience?
                    Explore and share a
                    <strong>{{ $nextStamp->category }}</strong>
                    experience to collect this stamp.
                </p>

                <div class="next-stamp-steps">
                    <div>
                        <span>1</span>
                        <p>Discover an experience</p>
                    </div>

                    <div>
                        <span>2</span>
                        <p>Join or complete it</p>
                    </div>

                    <div>
                        <span>3</span>
                        <p>Share your journey</p>
                    </div>
                </div>

                @if ($nextStamp->categoryDetails)
                    <a
                        class="next-stamp-button"
                        href="{{ route('experiences.index', [
                            'type' => $nextStamp->categoryDetails->type_id,
                            'category' => $nextStamp->category_id,
                        ]) }}"
                    >
                        Explore
                        {{ $nextStamp->categoryDetails->category_name }}
                        Experiences
                    </a>
                @else
                    <a
                        class="next-stamp-button"
                        href="{{ route('experiences.index') }}"
                    >
                        Explore Experiences
                    </a>
                @endif
            @else
                <div class="collection-complete">
                    <span aria-hidden="true">🏆</span>

                    <span class="next-stamp-eyebrow">
                        Collection Complete
                    </span>

                    <h3>You collected every stamp!</h3>

                    <p>
                        Your digital cultural passport collection is complete.
                        Continue exploring to unlock more achievement badges.
                    </p>

                    <a
                        class="next-stamp-button"
                        href="{{ route('engagement.achievements') }}"
                    >
                        View Achievements
                    </a>
                </div>
            @endif
        </aside>
        </div>
    </section>

    <section id="achievement" class="achievement-section container">
        <div class="section-top">
            <div>
                <h2>Recently Unlocked Badges</h2>
                <p>Celebrate your latest cultural achievements.</p>
            </div>

            <a
                href="{{ route('engagement.achievements') }}"
                class="outline-btn"
            >
                View All Badges
            </a>
        </div>

        <div class="achievement-grid recent-achievement-grid">
            @forelse ($recentUnlockedBadges as $achievement)
                <article class="achievement-card recent-achievement-card">
                    <img
                        src="{{ asset(
                            $achievement->badge_image
                                ?? 'images/default-badge.png'
                        ) }}"
                        alt="{{ $achievement->badge_name }}"
                    >

                    <h3>{{ $achievement->badge_name }}</h3>

                    <p>{{ $achievement->description }}</p>

                    <span class="recent-badge-date">
                        Unlocked
                        {{ $achievement->unlocked_date
                            ?->format('d M Y') ?? 'recently' }}
                    </span>
                </article>
            @empty
                <div class="empty-state recent-badge-empty">
                    <span aria-hidden="true">🏆</span>

                    <h3>No badges unlocked yet</h3>

                    <p>
                        Complete cultural experiences to earn your
                        first achievement badge.
                    </p>

                    <a
                        href="{{ route('engagement.achievements') }}"
                        class="outline-btn"
                    >
                        View Badge Progress
                    </a>
                </div>
            @endforelse
        </div>
    </section>
    

    <section class="history-section container">
        <div class="section-top">
            <div>
                <h2>Recent Cultural Journey</h2>
                <p>Your 3 most recently completed cultural experiences.</p>
            </div>

            <a href="{{ route('engagement.history') }}" class="outline-btn">
                View History
            </a>
        </div>

        <div class="recent-journey-grid">
            @forelse ($recentExperienceHistory as $history)
                @php
                    $experience = $history->experience;

                    $imagePath = is_string($experience?->image_url)
                        ? ltrim(
                            str_replace('\\', '/', trim($experience->image_url)),
                            '/'
                        )
                        : null;

                    $isExternalImage = filled($imagePath)
                        && (
                            str_starts_with(strtolower($imagePath), 'http://')
                            || str_starts_with(strtolower($imagePath), 'https://')
                        );

                    $isSafeLocalImage = filled($imagePath)
                        && ! $isExternalImage
                        && ! str_contains($imagePath, '../')
                        && is_file(public_path($imagePath));

                    $imageSource = $isExternalImage
                        ? $imagePath
                        : ($isSafeLocalImage ? asset($imagePath) : null);
                @endphp

                <article class="recent-journey-card">
                    <div class="recent-journey-image">
                        @if ($imageSource)
                            <img
                                src="{{ $imageSource }}"
                                alt="{{ $experience?->experiences_name
                                    ?? 'Cultural experience' }}"
                                referrerpolicy="no-referrer"
                            >
                        @else
                            <div
                                class="experience-image-placeholder"
                                role="img"
                                aria-label="Image unavailable"
                            >
                                <span aria-hidden="true">📷</span>
                            </div>
                        @endif

                        <span class="recent-journey-status">
                            Completed
                        </span>
                    </div>

                    <div class="recent-journey-content">
                        <span class="recent-journey-category">
                            {{ $experience?->category?->category_name
                                ?? 'Cultural Experience' }}
                        </span>

                        <h3>
                            {{ $experience?->experiences_name
                                ?? 'Experience' }}
                        </h3>

                        <div class="recent-journey-details">
                            <p>
                                <span aria-hidden="true">📍</span>
                                {{ $experience?->location_name
                                    ?? 'Malaysia' }}
                            </p>

                            <p>
                                <span aria-hidden="true">📅</span>
                                Completed
                                {{ $history->completed_date
                                    ?->format('d M Y') ?? '-' }}
                            </p>
                        </div>
                    </div>
                </article>
            @empty
                <div class="empty-state recent-journey-empty">
                    <span aria-hidden="true">🧭</span>

                    <h3>No completed experiences yet</h3>

                    <p>
                        Explore and share a cultural experience to begin
                        your journey.
                    </p>

                    <a
                        href="{{ route('experiences.index') }}"
                        class="outline-btn"
                    >
                        Discover Experiences
                    </a>
                </div>
            @endforelse
        </div>
    </section>
</div>

@endsection

@push('scripts')
    @vite('resources/js/pages/engagement.js')
@endpush