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
                <p class="hero-text">Every completed cultural experience earns you a beautiful digital passport stamp.</p>
                <a href="#achievement" class="hero-btn">View Achievements</a>
            </div>
        </div>
    </section>

    <section class="progress-section container">
        <div class="progress-header">
            <h2>Your Cultural Journey</h2>
            <p>Track your exploration progress across Malaysia.</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">🗺️</div>
                <div>
                    <h3>0</h3>
                    <p>States Visited</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">🎨</div>
                <div>
                    <h3>{{ $experienceHistory->count() }}</h3>
                    <p>Experiences Completed</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">📖</div>
                <div>
                    <h3>{{ $passportStamps->count() }}</h3>
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
                <p>Collect stamps from every cultural experience.</p>
            </div>

            <a href="{{ route('engagement.passport') }}" class="outline-btn">
                View Full Passport
            </a>
        </div>

        <div class="passport-book">
            <img
                src="{{ asset('images/engagement/passport-book.png') }}"
                class="passport-background"
                alt="Digital Cultural Passport"
            >

            <div class="passport-stamps">
                @forelse($passportStamps as $userStamp)
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
    </section>

    <section id="achievement" class="achievement-section container">
        <div class="section-top">
            <div>
                <h2>Achievement Badges</h2>
                <p>Track your progress and unlock rewards.</p>
            </div>
            <a href="{{ route('engagement.achievements') }}" class="outline-btn">
                View All Badges
            </a>
        </div>

        <div class="achievement-grid">
            @forelse($achievements->take(3) as $achievement)
                <div class="achievement-card {{ $achievement->is_unlocked ? 'unlocked' : 'locked' }}">
                    <img src="{{ asset($achievement->badge_image ?? 'images/default-badge.png') }}"
                        alt="{{ $achievement->badge_name }}">
                    <h3>{{ $achievement->badge_name }}</h3>
                    <p>{{ $achievement->requirement }}</p>
                    <div class="progress-bar">
                        <div class="progress" style="width: {{ $achievement->progress_percentage }}%"></div>
                    </div>
                    <span class="progress-text">
                        {{ $achievement->current_progress }} / {{ $achievement->target_count }}
                    </span>
                </div>
            @empty
                <div class="empty-state">
                    <p>No achievement badges available.</p>
                </div>
            @endforelse
        </div>
    </section>

    <section class="history-section container">
        <div class="section-top">
            <div>
                <h2>Recent Cultural Journey</h2>
                <p>Your latest completed experiences.</p>
            </div>

            <a href="{{ route('engagement.history') }}" class="outline-btn">
                View History
            </a>
        </div>

        @forelse($experienceHistory->take(1) as $history)
            <div class="recent-card">
                <div>
                    <h3>
                        {{ $history->experience?->experiences_name ?? 'Experience' }}
                    </h3>

                    <p>
                        📍 {{ $history->experience?->location_name ?? '-' }}
                    </p>

                    <p>🎨 {{ $history->experience?->category?->category_name ?? '-' }}</p>

                    <p>📅 {{ $history->completed_date?->format('d M Y') ?? '-' }}</p>
                </div>
            </div>
        @empty
            <div class="empty-state">
                <p>No completed cultural experiences yet.</p>
            </div>
        @endforelse
    </section>
</div>

@endsection

@push('scripts')
    @vite('resources/js/pages/engagement.js')
@endpush