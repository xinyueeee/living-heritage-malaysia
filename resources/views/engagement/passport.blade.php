@extends('layouts.app')

@section('title', 'Digital Cultural Passport')

@push('styles')
    @vite('resources/css/engagement.css')
@endpush

@section('content')
<div
    class="
        engagement-page
        passport-page
        passport-theme-{{ $passport->display_theme }}
        passport-layout-{{ $passport->display_layout }}
        {{ $passport->show_stamp_details
            ? 'show-stamp-details'
            : 'hide-stamp-details' }}
    "
    data-passport-page
>
    @if ($newStamps->isNotEmpty())
        <div
            class="reward-unlock-overlay"
            role="dialog"
            aria-modal="true"
            aria-labelledby="newStampTitle"
        >
            <div class="reward-unlock-modal">
                <span class="reward-unlock-eyebrow">
                    New reward
                </span>

                <h2 id="newStampTitle">
                    {{ $newStamps->count() === 1
                        ? 'New Stamp Collected!'
                        : 'New Stamps Collected!' }}
                </h2>

                <p>
                    Your cultural journey has earned you
                    {{ $newStamps->count() }}
                    new
                    {{ Str::plural('stamp', $newStamps->count()) }}.
                </p>

                <div class="reward-unlock-list">
                    @foreach ($newStamps as $userStamp)
                        <div class="reward-unlock-item">
                            @if ($userStamp->stamp?->stamp_image)
                                <img
                                    src="{{ asset(
                                        $userStamp->stamp->stamp_image
                                    ) }}"
                                    alt="{{ $userStamp->stamp->category }} stamp"
                                >
                            @endif

                            <div>
                                <strong>
                                    {{ $userStamp->stamp?->category
                                        ?? 'Category Stamp' }}
                                </strong>

                                <span>
                                    Collected
                                    {{ $userStamp->collected_date
                                        ?->format('d M Y') }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>

                <form
                    method="POST"
                    action="{{ route(
                        'engagement.passport.notifications.read'
                    ) }}"
                >
                    @csrf
                    @method('PATCH')

                    <button
                        type="submit"
                        class="reward-unlock-button"
                    >
                        Add to My Passport
                    </button>
                </form>
            </div>
        </div>
    @endif

    @if (session('success'))
        <div class="container">
            <div
                class="passport-success-message"
                role="status"
            >
                {{ session('success') }}
            </div>
        </div>
    @endif

    {{-- Page header --}}
    <section class="passport-page-header">
        <div class="container">
            <a
                href="{{ route('engagement.index') }}"
                class="passport-back-link"
            >
                &larr; Back to Engagement &amp; Rewards
            </a>

            <div class="passport-heading-row">
                <div>
                    <span class="passport-eyebrow">
                        MY CULTURAL JOURNEY
                    </span>

                    <h1>Digital Cultural Passport</h1>

                    <p>
                        Collect unique category stamps and arrange
                        them inside your personal cultural passport.
                    </p>
                </div>

                <div class="passport-total">
                    <strong>
                        {{ $collectedCount }} / {{ $totalCount }}
                    </strong>

                    <span>stamps collected</span>
                </div>
            </div>

            <div class="passport-collection-progress">
                <div class="passport-progress-label">
                    <span>Collection progress</span>
                    <strong>{{ $collectionPercentage }}%</strong>
                </div>

                <div class="progress-bar">
                    <div
                        class="progress"
                        style="width: {{ $collectionPercentage }}%"
                    ></div>
                </div>
            </div>
        </div>
    </section>

    {{-- Passport book --}}
    <section class="passport-book-section">
        <div class="container">
            <div class="passport-section-heading">
                <div>
                    <span class="passport-eyebrow">
                        MY PASSPORT
                    </span>

                    <h2>Passport Book</h2>

                    <p>
                        Browse the category stamps you have collected.
                    </p>
                </div>

                <div class="passport-heading-actions">
                    <button
                        type="button"
                        class="outline-btn"
                        data-download-journey-card
                    >
                        Download Journey Card
                    </button>
                    <a
                        href="{{ route(
                            'engagement.passport.customize'
                        ) }}"
                        class="outline-btn"
                    >
                        Customize Passport
                    </a>
                </div>
            </div>

            @if ($passportStamps->isEmpty())
                <div class="passport-book-empty">
                    <img
                        src="{{ asset(
                            'images/engagement/passport-book.webp'
                        ) }}"
                        alt="Empty Digital Cultural Passport"
                    >

                    <div class="passport-book-empty-message">
                        <h3>Your passport is empty</h3>

                        <p>
                            Complete and share a cultural experience
                            to collect your first category stamp.
                        </p>

                        <a
                            href="{{ route('experiences.index') }}"
                            class="hero-btn"
                        >
                            Explore Experiences
                        </a>
                    </div>
                </div>

            @else
                @php
                    /*
                    * Every flipbook page contains four stamps.
                    * Two consecutive pages form one open book spread.
                    */
                    $passportPages = $passportStamps
                        ->chunk(4)
                        ->values();

                    /*
                    * Ensure the book has an even number of pages so the
                    * final spread always has a left and right page.
                    */
                    if ($passportPages->count() % 2 !== 0) {
                        $passportPages->push(collect());
                    }
                @endphp

                <div
                    class="passport-viewer"
                    data-passport-viewer
                >
                    <button
                        type="button"
                        class="passport-page-button previous"
                        data-passport-previous
                        aria-label="Previous passport page"
                        disabled
                    >
                        &lsaquo;
                    </button>

                    <div
                        class="passport-flipbook"
                        data-passport-flipbook
                    >
                        @foreach ($passportPages as $pageIndex => $pageStamps)
                            <article
                                class="
                                    passport-flip-page
                                    {{ $pageIndex % 2 === 0
                                        ? 'passport-flip-page-left'
                                        : 'passport-flip-page-right' }}
                                "
                                data-density="soft"
                            >
                                <span
                                    class="passport-page-background"
                                    style="background-image: url('{{ asset(
                                        $pageIndex % 2 === 0
                                            ? 'images/engagement/passport-page-left.png'
                                            : 'images/engagement/passport-page-right.png'
                                    ) }}')"
                                    aria-hidden="true"
                                ></span>

                                <div class="passport-flip-page-content">
                                    <div class="passport-book-stamp-grid">
                                        @foreach ($pageStamps as $userStamp)
                                            @include(
                                                'engagement.partials.passport-book-stamp',
                                                ['userStamp' => $userStamp]
                                            )
                                        @endforeach
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <button
                        type="button"
                        class="passport-page-button next"
                        data-passport-next
                        aria-label="Next passport page"
                        @if ($passportPages->count() <= 2)
                            disabled
                        @endif
                    >
                        &rsaquo;
                    </button>
                </div>

                <p
                    class="passport-page-indicator"
                    data-passport-page-indicator
                >
                    Pages 1&ndash;2 of {{ $passportPages->count() }}
                </p>

                <p class="passport-drag-hint">
                    Drag a page corner or use the arrow buttons to browse.
                </p>
            @endif
        </div>
    </section>

    {{-- All available stamps --}}
    <section class="passport-collection-section">
        <div class="container">
            <div class="passport-section-heading">
                <div>
                    <span class="passport-eyebrow">
                        STAMP COLLECTION
                    </span>

                    <h2>Available Category Stamps</h2>

                    <p>
                        Collected stamps appear in full colour.
                        Continue exploring to unlock the others.
                    </p>
                </div>
            </div>

            {{-- Collection filters --}}
            <div class="passport-filter-buttons">
                <button
                    type="button"
                    class="active"
                    data-stamp-filter="all"
                >
                    All
                </button>

                <button
                    type="button"
                    data-stamp-filter="collected"
                >
                    Collected
                </button>

                <button
                    type="button"
                    data-stamp-filter="locked"
                >
                    Locked
                </button>
            </div>

            <div class="passport-collection-grid">
                @foreach ($allStamps as $stamp)
                    @php
                        $collected = $collectedStamps
                            ->get($stamp->stamp_id);

                        $isCollected = $collected !== null;

                        $experience = $collected
                            ?->completedExperience
                            ?->experience;
                    @endphp

                    <article
                        class="
                            passport-collection-card
                            {{ $isCollected
                                ? 'collected'
                                : 'locked' }}
                        "
                        data-stamp-status="{{
                            $isCollected
                                ? 'collected'
                                : 'locked'
                        }}"
                    >
                        <div class="passport-collection-image">
                            <img
                                src="{{ $stamp->stamp_image
                                    ? asset($stamp->stamp_image)
                                    : asset(
                                        'images/default-stamp.png'
                                    ) }}"
                                alt="{{
                                    $stamp->categoryDetails
                                        ?->category_name
                                    ?? $stamp->category
                                    ?? 'Category stamp'
                                }}"
                                loading="lazy"
                            >

                            @if (! $isCollected)
                                <span
                                    class="passport-lock-icon"
                                    aria-hidden="true"
                                >
                                    &#128274;
                                </span>
                            @endif
                        </div>

                        <div class="passport-collection-content">
                            <span class="stamp-status">
                                @if ($isCollected)
                                    &#10003; Collected
                                @else
                                    Locked
                                @endif
                            </span>

                            <h3>
                                {{
                                    $stamp->categoryDetails
                                        ?->category_name
                                    ?? $stamp->category
                                    ?? 'Cultural Category'
                                }}
                            </h3>

                            @if ($isCollected)
                                <div class="passport-optional-details">
                                    @if ($experience)
                                        <p>
                                            First unlocked through:

                                            <strong>
                                                {{
                                                    $experience
                                                        ->experiences_name
                                                }}
                                            </strong>
                                        </p>
                                    @endif

                                    <time
                                        datetime="{{ $collected
                                            ->collected_date
                                            ?->toDateString() }}"
                                    >
                                        Collected on
                                        {{ $collected
                                            ->collected_date
                                            ?->format('d M Y')
                                            ?? 'Unknown date' }}
                                    </time>
                                </div>
                            @else
                                <p>
                                    Complete and share an experience
                                    in this category to unlock it.
                                </p>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>
</div>

{{-- Downloadable cultural journey card --}}
<div class="journey-card-export-wrapper">
    <article
        class="journey-card-export"
        data-journey-card
    >
        <div class="journey-card-decoration"></div>

        <header class="journey-card-header">
            <div>
                <span>LIVING HERITAGE MALAYSIA</span>
                <h2>My Cultural Journey</h2>
            </div>

            <span class="journey-card-year">
                {{ now()->format('Y') }}
            </span>
        </header>

        <div class="journey-card-profile">
            <p>Cultural Explorer</p>

            <h3>
                {{ Auth::user()->user_name ?? 'Heritage Explorer' }}
            </h3>

            <div class="journey-card-progress">
                <strong>{{ $collectedCount }}</strong>
                <span>of {{ $totalCount }} stamps collected</span>
            </div>
        </div>

        <div class="journey-card-stamps">
            @forelse ($passportStamps as $userStamp)
                <div class="journey-card-stamp">
                    <img
                        src="{{ $userStamp->stamp?->stamp_image
                            ? asset($userStamp->stamp->stamp_image)
                            : asset('images/default-stamp.png') }}"
                        alt="{{ $userStamp->stamp?->category
                            ?? 'Cultural stamp' }}"
                    >

                    <span>
                        {{ $userStamp->stamp?->category
                            ?? 'Cultural Stamp' }}
                    </span>
                </div>
            @empty
                <p class="journey-card-empty">
                    Begin exploring Malaysia to collect your
                    first cultural stamp.
                </p>
            @endforelse
        </div>

        <footer class="journey-card-footer">
            <span>
                {{ $collectionPercentage }}% collection completed
            </span>

            <strong>#LivingHeritageMalaysia</strong>
        </footer>
    </article>
</div>
@endsection

@push('scripts')
    @vite('resources/js/pages/passport.js')
@endpush
