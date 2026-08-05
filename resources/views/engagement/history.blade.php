@extends('layouts.app')

@section('title', 'Experience History')

@push('styles')
    @vite('resources/css/engagement.css')
@endpush

@section('content')

<div class="engagement-page">

    <section class="history-page-header">
        <div class="container">
            <a href="{{ route('engagement.index') }}" class="back-link">
                ← Back to Engagement & Rewards
            </a>

            <h1>Experience History</h1>
            <p>View all your completed cultural experiences.</p>
        </div>
    </section>

    <section class="history-content container">

        <form
            method="GET"
            action="{{ route('engagement.history') }}"
            class="history-filter-form"
        >
            <div class="history-search">
                <label for="search">Search Experiences</label>

                <input
                    type="text"
                    id="search"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Search experience or location"
                >
            </div>

            <div class="history-filter">
                <label for="category">Category</label>

                <select id="category" name="category">
                    <option value="">All Categories</option>

                    @foreach($categories as $category)
                        <option
                            value="{{ $category->category_id }}"
                            @selected(request('category') == $category->category_id)
                        >
                            {{ $category->category_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="history-filter-actions">
                <button type="submit" class="filter-btn">
                    Apply Filters
                </button>

                <a
                    href="{{ route('engagement.history') }}"
                    class="clear-filter-btn"
                >
                    Clear
                </a>
            </div>
        </form>

        <div class="history-result-header">
            <div>
                <h2>Your Completed Experiences</h2>

                <p>
                    {{ $experienceHistory->total() }}
                    {{ \Illuminate\Support\Str::plural(
                        'experience',
                        $experienceHistory->total()
                    ) }}
                    completed
                </p>
            </div>
        </div>

        <div class="history-grid">
            @forelse($experienceHistory as $history)

                <article class="history-card">

                    <div class="history-card-image">
                        <img
                            src="{{ $history->experience?->image_url
                                ? asset($history->experience->image_url)
                                : asset('images/default-experience.png') }}"
                            alt="{{ $history->experience?->experiences_name
                                ?? 'Cultural experience' }}"
                        >

                        <span class="history-category-badge">
                            {{ $history->experience?->category?->category_name
                                ?? 'Uncategorised' }}
                        </span>
                    </div>

                    <div class="history-card-content">
                        <h3>
                            {{ $history->experience?->experiences_name
                                ?? 'Experience' }}
                        </h3>

                        <div class="history-details">
                            <p>
                                <span class="history-detail-icon">📍</span>

                                <span>
                                    {{ $history->experience?->location_name
                                        ?? 'Location unavailable' }}
                                </span>
                            </p>

                            <p>
                                <span class="history-detail-icon">🎨</span>

                                <span>
                                    {{ $history->experience?->category?->category_name
                                        ?? 'Uncategorised' }}
                                </span>
                            </p>

                            <p>
                                <span class="history-detail-icon">📅</span>

                                <span>
                                    Completed on
                                    {{ $history->completed_date?->format('d M Y')
                                        ?? '-' }}
                                </span>
                            </p>
                        </div>

                        <a href="#" class="history-details-link">
                            View Experience
                            <span>→</span>
                        </a>
                    </div>

                </article>

            @empty

                <div class="history-empty-state">
                    <div class="history-empty-icon">🧭</div>

                    <h2>No completed experiences found</h2>

                    <p>
                        Complete a cultural experience to begin building
                        your cultural journey history.
                    </p>
                </div>

            @endforelse
        </div>

        @if($experienceHistory->hasPages())
            <div class="history-pagination">
                {{ $experienceHistory->links() }}
            </div>
        @endif

    </section>

</div>

@endsection

@push('scripts')
    @vite('resources/js/pages/engagement.js')
@endpush