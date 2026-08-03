@extends('layouts.app')

@section('title', 'Achievement Badges')

@push('styles')
    @vite('resources/css/engagement.css')
@endpush

@section('content')
<div class="engagement-page">
    <section class="achievement-page-header">
        <div class="container">
            <a href="{{ route('engagement.index') }}" class="back-link">← Back to Engagement & Rewards</a>
            <h1>Achievement Badges</h1>
            <p>Explore every badge and select one to view its requirement and your progress.</p>
        </div>
    </section>

    <section class="achievement-section container">
        <div class="achievement-grid">
            @forelse($achievements as $achievement)
                <button type="button"
                        class="achievement-card {{ $achievement->is_unlocked ? 'unlocked' : 'locked' }}"
                        data-badge-card
                        data-name="{{ $achievement->badge_name }}"
                        data-description="{{ $achievement->description ?? '-' }}"
                        data-requirement="{{ $achievement->requirement ?? '-' }}"
                        data-image="{{ asset($achievement->badge_image ?? 'images/default-badge.png') }}"
                        data-progress="{{ $achievement->current_progress ?? 0 }}"
                        data-target="{{ $achievement->target_count ?? 1 }}"
                        data-percentage="{{ $achievement->progress_percentage ?? 0 }}"
                        data-unlocked="{{ $achievement->is_unlocked ? 'true' : 'false' }}"
                        data-unlocked-date="{{ $achievement->unlocked_date?->format('d M Y') ?? '' }}">
                    <img src="{{ asset($achievement->badge_image ?? 'images/default-badge.png') }}"
                         alt="{{ $achievement->badge_name }}">
                    <h3>{{ $achievement->badge_name }}</h3>
                    <span class="badge-status">
                        {{ $achievement->is_unlocked ? 'Unlocked' : 'Locked' }}
                    </span>
                    <span class="view-details">View Details</span>
                </button>
            @empty
                <div class="empty-state">
                    <h3>No badges available</h3>
                    <p>The achievement badges have not been added yet.</p>
                </div>
            @endforelse
        </div>
    </section>

    <div class="badge-modal" id="badgeModal" hidden>
        <div class="badge-modal-backdrop" data-close-badge-modal></div>
        <div class="badge-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="badgeModalTitle">
            <button type="button" class="badge-modal-close" data-close-badge-modal aria-label="Close badge details">×</button>
            <img id="badgeModalImage" class="badge-modal-image" src="" alt="">
            <span id="badgeModalStatus" class="badge-modal-status"></span>
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
                    <div class="progress" id="badgeModalProgressBar"></div>
                </div>
            </div>

            <p id="badgeModalUnlockedDate" class="badge-modal-unlocked-date" hidden></p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    @vite('resources/js/pages/engagement.js')
@endpush