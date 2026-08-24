@extends('layouts.app')

@section('title', 'Trending Experiences | Living Heritage Malaysia')

@section('content')
    <div class="discovery-page trending-page">
        <section class="trending-hero">
            <div class="container">
                <p class="eyebrow">Popular This Week</p>
                <h1>Trending Experiences</h1>
                <p>Explore the current and upcoming cultural experiences receiving the most views over the last 7 days.</p>
                <p class="trending-method-note">Based on meaningful Experience views from the last 7 days.</p>
            </div>
        </section>

        <section class="container trending-content" aria-labelledby="trending-list-heading">
            <div class="trending-heading">
                <div>
                    <p class="eyebrow">Trending Now</p>
                    <h2 id="trending-list-heading">What people are exploring</h2>
                </div>
                <a href="{{ route('experiences.index') }}">Browse all experiences <span aria-hidden="true">&rarr;</span></a>
            </div>

            @if ($trendingExperiences->isEmpty())
                <div class="no-data trending-empty">
                    <span aria-hidden="true">&#128200;</span>
                    <h3>No trending experiences yet</h3>
                    <p>Views from the last 7 days will appear here when current or upcoming experiences begin trending.</p>
                    <a class="button button-primary" href="{{ route('experiences.index') }}">Discover Experiences</a>
                </div>
            @else
                <ol class="trending-grid" aria-label="Trending experiences ranked by views in the last 7 days">
                    @foreach ($trendingExperiences as $experience)
                        @php($viewCount = (int) $experience->meaningful_view_count)
                        <li class="trending-card-wrap">
                            <span class="trending-rank" aria-label="Rank {{ $loop->iteration }}">#{{ $loop->iteration }}</span>
                            @include('components.experience-card', [
                                'experience' => $experience,
                                'hideFavourite' => true,
                            ])
                            <p class="trending-view-count">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path d="M13.5 2.5c.6 4-2.4 5.2-2.4 8.1 0 1.2.7 2.1 1.7 2.6-.1-2.1 1.2-3.2 2.5-4.1 1.9 1.7 3.2 3.8 3.2 6.3A6.5 6.5 0 0 1 5.5 15c0-3.8 2.1-7.3 5.8-10.4.1 2.1.8 3.2 2.2 4.1" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                {{ $viewCount }} {{ Str::plural('view', $viewCount) }} in the last 7 days
                            </p>
                        </li>
                    @endforeach
                </ol>
            @endif
        </section>
    </div>
@endsection
