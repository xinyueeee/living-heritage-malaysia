@extends('layouts.app')

@section('title', 'Engagement & Rewards')

@push('styles')
    @vite('resources/css/engagement.css')
@endpush

@section('content')

<section class="hero-banner">
    <div class="container">
        <h1>Engagement & Rewards</h1>
        <p>Discover your cultural journey, collect passport stamps, unlock achievements, and track your experiences.</p>
    </div>
</section>

<section class="passport-section container">
    <h2>Digital Cultural Passport</h2>
    <p>View your collected cultural experience stamps.</p>

    <div class="passport-container">
        @if($passportStamps->count() > 0)
            @foreach($passportStamps as $stamp)
                <div class="passport-stamp">
                    <img src="{{ $stamp->stamp_image ?? asset('images/default-stamp.png') }}" alt="Passport Stamp">

                    <h3>
                        {{ $stamp->experience->experience_name ?? 'Unknown Experience' }}
                    </h3>

                    <p>
                        {{ $stamp->experience->category->category_name ?? '-' }}
                    </p>

                    <span>
                        {{ $stamp->created_at->format('d M Y') }}
                    </span>
                </div>
            @endforeach
        @else
            <p>No passport stamps collected yet.</p>
        @endif
    </div>
</section>

<section class="achievement-section container">
    <h2>Achievement Badges</h2>
    <p>Complete cultural experiences to unlock achievements.</p>

    <div class="achievement-grid">
        @if($achievements->count() > 0)
            @foreach($achievements as $achievement)
                <div class="achievement-card {{ $achievement->is_unlocked ? 'unlocked' : 'locked' }}">

                    <img src="{{ $achievement->badge->badge_image ?? asset('images/default-badge.png') }}" alt="Badge">

                    <h3>
                        {{ $achievement->badge->badge_name ?? 'Achievement' }}
                    </h3>

                    <p>
                        {{ $achievement->badge->description ?? '-' }}
                    </p>

                    <p>
                        Requirement: {{ $achievement->badge->requirement ?? '-' }}
                    </p>

                    <div class="progress-bar">
                        <div class="progress" style="width: {{ $achievement->progress ?? 0 }}%"></div>
                    </div>

                    <span>{{ $achievement->progress ?? 0 }}%</span>

                </div>
            @endforeach
        @else
            <p>No achievement data available.</p>
        @endif
    </div>
</section>

<section class="history-section container">
    <h2>Experience History</h2>
    <p>Your completed cultural experiences.</p>

    <div class="history-list">
        @if($experienceHistory->count() > 0)
            @foreach($experienceHistory as $history)
                <div class="history-card">

                    <h3>
                        {{ $history->experience->experience_name ?? 'Experience' }}
                    </h3>

                    <p>
                        Category:
                        {{ $history->experience->category->category_name ?? '-' }}
                    </p>

                    <p>
                        Location:
                        {{ $history->experience->location ?? '-' }}
                    </p>

                    <p>
                        Completed:
                        {{ $history->completed_at?->format('d M Y') ?? '-' }}
                    </p>

                </div>
            @endforeach
        @else
            <p>No completed cultural experiences found.</p>
        @endif
    </div>
</section>

@endsection

@push('scripts')
    @vite('resources/js/pages/engagement.js')
@endpush