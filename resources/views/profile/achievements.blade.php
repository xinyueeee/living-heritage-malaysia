@extends('layouts.app')

@section('title', 'Achievements & Stats - Living Heritage Malaysia')

@section('content')
    <section class="profile-hero">
        <div class="container profile-hero-content">
            <h1>Achievements &amp; Stats</h1>
            <p>Track your exploration progress and celebrate your cultural journey.</p>
        </div>
    </section>

    <div class="container profile-layout">
        @include('profile.partials.sidebar', ['active' => 'achievements'])

        <div>
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
                        <p class="profile-stat-value">{{ $stats['stamps_collected'] }}</p>
                        <p class="profile-stat-label">Stamps Collected</p>
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

            <div class="profile-card">
                <div class="profile-card-header-row">
                    <div>
                        <h3>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/></svg>
                            Digital Cultural Passport
                        </h3>
                        <p>Collect stamps from every cultural experience.</p>
                    </div>
                    <a href="{{ route('engagement.passport') }}" class="profile-edit-btn">View Full Passport</a>
                </div>

                <div class="profile-passport-widget">
                    <div class="profile-passport-preview">
                        <img src="{{ asset('images/engagement/passport-book.png') }}" alt="">
                        <div class="profile-passport-preview-text">
                            @if ($stats['stamps_collected'] > 0)
                                <strong>{{ $stats['stamps_collected'] }} {{ \Illuminate\Support\Str::plural('stamp', $stats['stamps_collected']) }} collected</strong>
                                <span>Keep exploring to fill your passport.</span>
                            @else
                                <strong>No passport stamps yet</strong>
                                <span>Start exploring and collect your first stamp!</span>
                            @endif
                        </div>
                    </div>

                    <div class="profile-passport-progress">
                        <p>Your Progress</p>

                        <div class="profile-progress-ring" style="--pct: {{ $stats['completion_percentage'] }}">
                            <div class="profile-progress-ring-inner">
                                <strong>{{ $stats['completion_percentage'] }}%</strong>
                                <span>Passport Completion</span>
                            </div>
                        </div>

                        <div class="profile-progress-stats">
                            <div class="profile-progress-stat">
                                <span class="profile-progress-stat-label">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M2 12h20"/></svg>
                                    Total Stamps
                                </span>
                                <b>{{ $stats['stamps_collected'] }} / {{ $stats['total_stamps'] }}</b>
                            </div>
                            <div class="profile-progress-stat">
                                <span class="profile-progress-stat-label">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                                    Experiences Completed
                                </span>
                                <b>{{ $stats['experiences_completed'] }} / {{ $stats['total_experiences'] }}</b>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="profile-card">
                <div class="profile-card-header-row">
                    <div>
                        <h3>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 21h8"/><path d="M12 17v4"/><path d="M7 4h10v5a5 5 0 0 1-10 0z"/></svg>
                            Achievement Badges
                        </h3>
                        <p>Badges you have earned.</p>
                    </div>
                    <a href="{{ route('engagement.achievements') }}" class="profile-edit-btn">View All Badges</a>
                </div>

                @if ($stats['badges_earned'] === 0)
                    <p class="profile-empty">No badges collected yet — keep exploring to earn your first one!</p>
                @else
                    <div class="profile-badge-grid">
                        @foreach ($badges as $badge)
                            <div class="profile-badge-card {{ $badge->is_unlocked ? 'is-unlocked' : 'is-locked' }}">
                                <img src="{{ asset($badge->badge_image ?? 'images/default-badge.png') }}" alt="{{ $badge->badge_name }}">
                                <h4>{{ $badge->badge_name }}</h4>
                                <p>{{ $badge->requirement }}</p>

                                @if ($badge->is_unlocked)
                                    <span class="profile-badge-date">
                                        {{ \Illuminate\Support\Carbon::parse($badge->unlocked_date)->format('j M Y') }}
                                    </span>
                                @else
                                    <div class="profile-badge-progress-bar">
                                        <span style="width: {{ $badge->progress_percentage }}%"></span>
                                    </div>
                                    <span class="profile-badge-progress-text">
                                        {{ $badge->current_progress }} / {{ $badge->target_count }}
                                    </span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="profile-feedback-banner">
                <div>
                    <h3>Keep exploring and unlocking more achievements!</h3>
                    <p>Every experience brings you closer to becoming a true heritage explorer.</p>
                </div>
                <a href="{{ route('experiences.index') }}" class="button button-primary">Explore More Experiences →</a>
            </div>
        </div>
    </div>
@endsection
