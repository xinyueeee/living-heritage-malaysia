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
                <p class="hero-text">Completed cultural experience earns you a beautiful digital passport stamp.</p>
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

            <a href="#" class="outline-btn">
                View Full Passport
            </a>
        </div>


        <div class="passport-book">
            <img src="{{ asset('images/engagement/passport-book.png') }}"
            class="passport-background"
            alt="Digital Cultural Passport">

            <div class="passport-stamps">
                @foreach($passportStamps as $stamp)

                    <div class="passport-stamp">
                        <img src="{{ $stamp->stamp_image ?? asset('images/default-stamp.png') }}">
                        <p>
                            {{ $stamp->experience->experience_name ?? '-' }}
                        </p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="achievement-section container">
        <h2>Achievement Badges</h2>
        <div class="achievement-title">
            <h3>Unlocked</h3>
        </div>

        <div class="achievement-grid">
            @foreach($achievements->where('is_unlocked', true) as $achievement)
                <div class="achievement-card unlocked">
                    <img src="{{ $achievement->badge->badge_image ?? asset('images/default-badge.png') }}" alt="Badge">

                    <h3>{{ $achievement->badge->badge_name ?? 'Achievement' }}</h3>

                    <p>{{ $achievement->badge->description ?? '-' }}</p>

                    <p>Requirement: {{ $achievement->badge->requirement ?? '-' }}</p>

                    <span class="badge-status">
                        Unlocked
                    </span>

                </div>
            @endforeach
        </div>
    
        <div class="achievement-title locked-title">
            <h3>Locked</h3>
        </div>

        <div class="achievement-grid">
            @foreach($achievements->where('is_unlocked', false) as $achievement)
                <div class="achievement-card locked">

                    <img src="{{ $achievement->badge->badge_image ?? asset('images/default-badge.png') }}" alt="Badge">

                    <h3>{{ $achievement->badge->badge_name ?? 'Achievement' }}</h3>

                    <p>{{ $achievement->badge->description ?? '-' }}</p>

                    <p>Requirement: {{ $achievement->badge->requirement ?? '-' }}</p>

                    <span class="badge-status">
                        🔒 Locked
                    </span>

                </div>
            @endforeach
        </div>

        
    </section>

    <section class="history-section container">
        <div class="section-top">
            <div>
                <h2>Recent Cultural Journey</h2>
                <p>Your latest completed experiences.</p>
            </div>
            <a href="#" class="outline-btn">View History</a>
        </div>

        @if($experienceHistory->count()>0)
        @foreach($experienceHistory->take(1) as $history)
        <div class="recent-card">
            <div>
                <h3>{{ $history->experience->experience_name ?? 'Experience' }}</h3>
                <p>📍 {{ $history->experience->location ?? '-' }}</p>
                <p>🎨 {{ $history->experience->category->category_name ?? '-' }}</p>
                <p>📅 {{ $history->completed_at?->format('d M Y') ?? '-' }}</p>
            </div>
        </div>

        @endforeach
        @endif
    </section>
</div>

@endsection

@push('scripts')
    @vite('resources/js/pages/engagement.js')
@endpush