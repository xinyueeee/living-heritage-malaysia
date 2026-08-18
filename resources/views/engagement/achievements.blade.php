@extends('layouts.app')

@section('title', 'Achievement Badges')

@push('styles')
    @vite('resources/css/engagement.css')
@endpush

@section('content')
<div class="engagement-page">
    @if ($newAchievements->isNotEmpty())
        <div
            class="reward-unlock-overlay"
            role="dialog"
            aria-modal="true"
            aria-labelledby="newAchievementTitle"
        >
            <div class="reward-unlock-modal">
                <span class="reward-unlock-eyebrow">
                    Achievement unlocked
                </span>

                <h2 id="newAchievementTitle">
                    {{ $newAchievements->count() === 1
                        ? 'You Earned a New Badge!'
                        : 'You Earned New Badges!' }}
                </h2>

                <p>
                    Congratulations! Your cultural journey has reached
                    a new milestone.
                </p>

                <div class="reward-unlock-list">
                    @foreach ($newAchievements as $userAchievement)
                        <div class="reward-unlock-item">
                            @if ($userAchievement->badge?->badge_image)
                                <img
                                    src="{{ asset(
                                        $userAchievement
                                            ->badge
                                            ->badge_image
                                    ) }}"
                                    alt="{{ $userAchievement
                                        ->badge
                                        ->badge_name }} badge"
                                >
                            @endif

                            <div>
                                <strong>
                                    {{ $userAchievement
                                        ->badge
                                        ?->badge_name
                                        ?? 'Achievement Badge' }}
                                </strong>

                                <span>
                                    {{ $userAchievement
                                        ->badge
                                        ?->description }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>

                <form
                    method="POST"
                    action="{{ route(
                        'engagement.achievements.notifications.read'
                    ) }}"
                >
                    @csrf
                    @method('PATCH')

                    <button
                        type="submit"
                        class="reward-unlock-button"
                    >
                        View My Achievements
                    </button>
                </form>
            </div>
        </div>
    @endif

    @php
        $unlockedAchievements = $achievements
            ->where('is_unlocked', true);

        $lockedAchievements = $achievements
            ->where('is_unlocked', false);
    @endphp

    <section class="achievement-page-header">
        <div class="container">
            <a
                href="{{ route('engagement.index') }}"
                class="back-link"
            >
                ← Back to Engagement & Rewards
            </a>

            <h1>Achievement Badges</h1>

            <p>
                Explore your unlocked achievements and track your
                progress towards the remaining badges.
            </p>

            <div class="achievement-summary">
                <strong>
                    {{ $unlockedAchievements->count() }}
                    /
                    {{ $achievements->count() }}
                </strong>

                <span>badges unlocked</span>
            </div>
        </div>
    </section>

    <main class="achievement-list">

        {{-- Unlocked badges --}}
        <section class="achievement-group container">
            <div class="achievement-group-heading">
                <div>
                    <span class="achievement-group-eyebrow">
                        YOUR COLLECTION
                    </span>

                    <h2>Unlocked Badges</h2>

                    <p>
                        Badges you have earned throughout your
                        cultural journey.
                    </p>
                </div>

                <span class="achievement-count unlocked-count">
                    {{ $unlockedAchievements->count() }}
                    unlocked
                </span>
            </div>

            @if ($unlockedAchievements->isEmpty())
                <div class="achievement-group-empty">
                    <div
                        class="achievement-empty-icon"
                        aria-hidden="true"
                    >
                        ◇
                    </div>

                    <h3>No badges unlocked yet</h3>

                    <p>
                        Complete and share cultural experiences to
                        unlock your first achievement badge.
                    </p>

                    <a
                        href="{{ route('experiences.index') }}"
                        class="hero-btn"
                    >
                        Explore Cultural Experiences
                    </a>
                </div>
            @else
                <div class="achievement-grid">
                    @foreach ($unlockedAchievements as $achievement)
                        @include(
                            'engagement.partials.achievement-card',
                            ['achievement' => $achievement]
                        )
                    @endforeach
                </div>
            @endif
        </section>

        {{-- Locked badges --}}
        <section class="achievement-group locked-achievement-group">
            <div class="container">
                <div class="achievement-group-heading">
                    <div>
                        <span class="achievement-group-eyebrow">
                            KEEP EXPLORING
                        </span>

                        <h2>Locked Badges</h2>

                        <p>
                            Select a badge to see its requirement and
                            your current progress.
                        </p>
                    </div>

                    <span class="achievement-count locked-count">
                        {{ $lockedAchievements->count() }}
                        locked
                    </span>
                </div>

                @if ($lockedAchievements->isEmpty())
                    <div class="achievement-group-empty">
                        <div
                            class="achievement-empty-icon"
                            aria-hidden="true"
                        >
                            ★
                        </div>

                        <h3>Every badge has been unlocked!</h3>

                        <p>
                            Congratulations—you have completed the
                            entire achievement collection.
                        </p>
                    </div>
                @else
                    <div class="achievement-grid">
                        @foreach ($lockedAchievements as $achievement)
                            @include(
                                'engagement.partials.achievement-card',
                                ['achievement' => $achievement]
                            )
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

    </main>

    {{-- Shared badge details modal --}}
    <div class="badge-modal" id="badgeModal" hidden>
        <div
            class="badge-modal-backdrop"
            data-close-badge-modal
        ></div>

        <div
            class="badge-modal-dialog"
            role="dialog"
            aria-modal="true"
            aria-labelledby="badgeModalTitle"
        >
            <button
                type="button"
                class="badge-modal-close"
                data-close-badge-modal
                aria-label="Close badge details"
            >
                ×
            </button>

            <img
                id="badgeModalImage"
                class="badge-modal-image"
                src=""
                alt=""
            >

            <span
                id="badgeModalStatus"
                class="badge-modal-status"
            ></span>

            <h2 id="badgeModalTitle"></h2>

            <p id="badgeModalDescription"></p>

            <div class="badge-modal-requirement">
                <strong>Requirement</strong>
                <p id="badgeModalRequirement"></p>
            </div>

            <div class="badge-modal-progress">
                <div class="badge-modal-progress-row">
                    <strong>Your Progress</strong>
                    <span id="badgeModalProgressText"></span>
                </div>

                <div class="progress-bar">
                    <div
                        class="progress"
                        id="badgeModalProgressBar"
                    ></div>
                </div>
            </div>

            <p
                id="badgeModalUnlockedDate"
                class="badge-modal-unlocked-date"
                hidden
            ></p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    @vite('resources/js/pages/engagement.js')
@endpush