@extends('layouts.app')

@section('title', 'Experience History')

@push('styles')
    @vite('resources/css/engagement.css')
@endpush

@section('content')
<div class="engagement-page">

    {{-- Page header --}}
    <section class="history-page-header">
        <div class="container">
            <a
                href="{{ route('engagement.index') }}"
                class="back-link"
            >
                ← Back to Engagement & Rewards
            </a>

            <h1>Experience History</h1>

            <p>
                Revisit your completed cultural experiences and
                celebrate every step of your journey.
            </p>
        </div>
    </section>

    <section class="history-content container">

        {{-- Search and category filters --}}
        <form
            method="GET"
            action="{{ route('engagement.history') }}"
            class="history-filter-form"
        >
            <div class="history-search">
                <label for="search">
                    Search Experiences
                </label>

                <input
                    type="text"
                    id="search"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Search experience or location"
                >
            </div>

            <div class="history-filter">
                <label for="category">
                    Category
                </label>

                <select
                    id="category"
                    name="category"
                >
                    <option value="">
                        All Categories
                    </option>

                    @foreach ($categories as $category)
                        <option
                            value="{{ $category->category_id }}"
                            @selected(
                                request('category')
                                == $category->category_id
                            )
                        >
                            {{ $category->category_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="history-filter-actions">
                <button
                    type="submit"
                    class="filter-btn"
                >
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

        {{-- Result summary --}}
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

        {{-- Chronological experience timeline --}}
        <div class="history-timeline">
            @forelse ($experienceHistory as $history)
                @php
                    $experience = $history->experience;

                    $categoryStamp = $categoryStamps->get(
                        $experience?->category_id
                    );

                    /*
                    * Prepare both external URLs and local image paths.
                    */
                    $imagePath = is_string($experience?->image_url)
                        ? ltrim(
                            str_replace(
                                '\\',
                                '/',
                                trim($experience->image_url)
                            ),
                            '/'
                        )
                        : null;

                    $isExternalImage = filled($imagePath)
                        && (
                            str_starts_with(
                                strtolower($imagePath),
                                'http://'
                            )
                            || str_starts_with(
                                strtolower($imagePath),
                                'https://'
                            )
                        );

                    $isSafeLocalImage = filled($imagePath)
                        && ! $isExternalImage
                        && ! str_contains($imagePath, '../')
                        && is_file(public_path($imagePath));

                    $imageSource = $isExternalImage
                        ? $imagePath
                        : (
                            $isSafeLocalImage
                                ? asset($imagePath)
                                : asset(
                                    'images/default-experience.png'
                                )
                        );
                @endphp

                <article class="history-timeline-item">

                    {{-- Completion date --}}
                    <time
                        class="history-timeline-date"
                        datetime="{{ $history
                            ->completed_date
                            ?->toDateString() }}"
                    >
                        <strong>
                            {{ $history
                                ->completed_date
                                ?->format('d')
                                ?? '--' }}
                        </strong>

                        <span>
                            {{ strtoupper(
                                $history
                                    ->completed_date
                                    ?->format('M')
                                ?? '---'
                            ) }}
                        </span>

                        <small>
                            {{ $history
                                ->completed_date
                                ?->format('Y')
                                ?? '----' }}
                        </small>
                    </time>

                    {{-- Category stamp marker --}}
                    <div class="history-timeline-marker">
                        @if ($categoryStamp?->stamp_image)
                            <img
                                src="{{ asset(
                                    $categoryStamp->stamp_image
                                ) }}"
                                alt="{{ $categoryStamp->category }}
                                    category stamp"
                            >
                        @else
                            <span aria-hidden="true">
                                ✦
                            </span>
                        @endif
                    </div>

                    {{-- Experience information --}}
                    <div class="history-timeline-card">

                        {{-- Experience image --}}
                        <div class="history-timeline-image">
                            <img
                                src="{{ $imageSource }}"
                                alt="{{ $experience
                                    ?->experiences_name
                                    ?? 'Cultural experience' }}"
                                @if ($isExternalImage)
                                    referrerpolicy="no-referrer"
                                @endif
                                onerror="
                                    this.onerror = null;
                                    this.src = '{{ asset(
                                        'images/default-experience.png'
                                    ) }}';
                                "
                            >

                            <span>
                                {{ $experience
                                    ?->category
                                    ?->category_name
                                    ?? 'Uncategorised' }}
                            </span>
                        </div>

                        {{-- Name and description --}}
                        <div class="history-timeline-main">
                            <h3>
                                {{ $experience
                                    ?->experiences_name
                                    ?? 'Experience unavailable' }}
                            </h3>

                            <p>
                                {{ $experience
                                    ?->short_description
                                    ?? $experience?->description
                                    ?? 'Explore this completed cultural experience.' }}
                            </p>
                        </div>

                        {{-- Location, date and action --}}
                        <div class="history-timeline-side">
                            <div class="history-timeline-meta">
                                <p>
                                    <span aria-hidden="true">
                                        ⌖
                                    </span>

                                    {{ $experience
                                        ?->location_name
                                        ?? 'Location unavailable' }}
                                </p>

                                <p>
                                    <span aria-hidden="true">
                                        ▣
                                    </span>

                                    {{ $history
                                        ->completed_date
                                        ?->format('d M Y')
                                        ?? 'Date unavailable' }}
                                </p>
                            </div>

                            <div class="history-timeline-action">
                                @if ($experience)
                                    <a
                                        href="{{ route(
                                            'experiences.show',
                                            $experience
                                        ) }}"
                                    >
                                        View Details

                                        <span aria-hidden="true">
                                            →
                                        </span>
                                    </a>
                                @else
                                    <span
                                        class="history-link-unavailable"
                                    >
                                        Unavailable
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </article>
            @empty
                <div class="history-empty-state">
                    <div class="history-empty-icon">
                        🧭
                    </div>

                    <h2>
                        No completed experiences found
                    </h2>

                    <p>
                        Complete a cultural experience to begin
                        building your cultural journey history.
                    </p>

                    <a
                        href="{{ route('experiences.index') }}"
                        class="hero-btn"
                    >
                        Explore Experiences
                    </a>
                </div>
            @endforelse
        </div>

        {{-- Custom pagination --}}
        @if ($experienceHistory->total() > 0)
            <div class="history-pagination-footer">
                <p class="history-pagination-summary">
                    Showing

                    <strong>
                        {{ $experienceHistory->firstItem() }}
                    </strong>

                    to

                    <strong>
                        {{ $experienceHistory->lastItem() }}
                    </strong>

                    of

                    <strong>
                        {{ $experienceHistory->total() }}
                    </strong>

                    results
                </p>

                @if ($experienceHistory->hasPages())
                    <nav
                        class="history-page-navigation"
                        aria-label="Experience history pagination"
                    >
                        {{-- Previous page --}}
                        @if ($experienceHistory->onFirstPage())
                            <span
                                class="
                                    history-page-control
                                    disabled
                                "
                                aria-disabled="true"
                                aria-label="Previous page"
                            >
                                &lsaquo;
                            </span>
                        @else
                            <a
                                href="{{ $experienceHistory
                                    ->previousPageUrl() }}"
                                class="history-page-control"
                                rel="prev"
                                aria-label="Previous page"
                            >
                                &lsaquo;
                            </a>
                        @endif

                        {{-- Page number buttons --}}
                        @foreach (
                            $experienceHistory->getUrlRange(
                                1,
                                $experienceHistory->lastPage()
                            ) as $page => $url
                        )
                            @if (
                                $page
                                === $experienceHistory
                                    ->currentPage()
                            )
                                <span
                                    class="
                                        history-page-number
                                        active
                                    "
                                    aria-current="page"
                                >
                                    {{ $page }}
                                </span>
                            @else
                                <a
                                    href="{{ $url }}"
                                    class="history-page-number"
                                    aria-label="Go to page {{ $page }}"
                                >
                                    {{ $page }}
                                </a>
                            @endif
                        @endforeach

                        {{-- Next page --}}
                        @if ($experienceHistory->hasMorePages())
                            <a
                                href="{{ $experienceHistory
                                    ->nextPageUrl() }}"
                                class="history-page-control"
                                rel="next"
                                aria-label="Next page"
                            >
                                &rsaquo;
                            </a>
                        @else
                            <span
                                class="
                                    history-page-control
                                    disabled
                                "
                                aria-disabled="true"
                                aria-label="Next page"
                            >
                                &rsaquo;
                            </span>
                        @endif
                    </nav>
                @endif

                <div
                    class="history-pagination-balance"
                    aria-hidden="true"
                ></div>
            </div>
        @endif
    </section>
</div>
@endsection

@push('scripts')
    @vite('resources/js/pages/engagement.js')
@endpush