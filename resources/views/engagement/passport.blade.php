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

                <a
                    href="{{ route(
                        'engagement.passport.customize'
                    ) }}"
                    class="outline-btn"
                >
                    Customize Passport
                </a>
            </div>

            @if ($passportStamps->isEmpty())
                <div class="passport-book-empty">
                    <img
                        src="{{ asset(
                            'images/engagement/passport-book.png'
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
                <div class="passport-viewer">
                    <button
                        type="button"
                        class="passport-page-button previous"
                        data-passport-previous
                        aria-label="Previous passport page"
                    >
                        &lsaquo;
                    </button>

                    <div
                        class="passport-book-display"
                        data-passport-book
                    >
                        <img
                            src="{{ asset(
                                'images/engagement/passport-book.png'
                            ) }}"
                            class="passport-book-background"
                            alt="Digital Cultural Passport"
                        >

                        {{-- Left passport page --}}
                        <div
                            class="
                                passport-book-page
                                passport-book-page-left
                            "
                            data-book-page="1"
                        >
                            <span class="passport-book-page-title">
                                Cultural Collection
                            </span>

                            <div class="passport-book-stamp-grid">
                                @foreach (
                                    $passportStamps->take(4)
                                    as $userStamp
                                )
                                    <button
                                        type="button"
                                        class="passport-book-stamp"
                                        data-user-stamp-id="{{
                                            $userStamp->user_stamp_id
                                        }}"
                                        title="{{
                                            $userStamp->stamp
                                                ?->category
                                        }}"
                                    >
                                        <img
                                            src="{{ $userStamp
                                                ->stamp
                                                ?->stamp_image
                                                    ? asset(
                                                        $userStamp
                                                            ->stamp
                                                            ->stamp_image
                                                    )
                                                    : asset(
                                                        'images/default-stamp.png'
                                                    ) }}"
                                            alt="{{
                                                $userStamp
                                                    ->stamp
                                                    ?->category
                                                ?? 'Passport stamp'
                                            }}"
                                        >

                                        <span>
                                            {{
                                                $userStamp
                                                    ->stamp
                                                    ?->category
                                                ?? 'Category'
                                            }}
                                        </span>

                                        <small class="passport-book-stamp-details">
                                            @if (
                                                $userStamp
                                                    ->completedExperience
                                                    ?->experience
                                            )
                                                <strong>
                                                    {{
                                                        $userStamp
                                                            ->completedExperience
                                                            ->experience
                                                            ->experiences_name
                                                    }}
                                                </strong>
                                            @endif

                                            <time>
                                                {{
                                                    $userStamp
                                                        ->collected_date
                                                        ?->format('d M Y')
                                                    ?? 'Unknown date'
                                                }}
                                            </time>
                                        </small>
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        {{-- Right passport page --}}
                        <div
                            class="
                                passport-book-page
                                passport-book-page-right
                            "
                            data-book-page="2"
                        >
                            <span class="passport-book-page-title">
                                Cultural Collection
                            </span>

                            <div class="passport-book-stamp-grid">
                                @foreach (
                                    $passportStamps->slice(4, 4)
                                    as $userStamp
                                )
                                    <button
                                        type="button"
                                        class="passport-book-stamp"
                                        data-user-stamp-id="{{
                                            $userStamp->user_stamp_id
                                        }}"
                                        title="{{
                                            $userStamp->stamp
                                                ?->category
                                        }}"
                                    >
                                        <img
                                            src="{{ $userStamp
                                                ->stamp
                                                ?->stamp_image
                                                    ? asset(
                                                        $userStamp
                                                            ->stamp
                                                            ->stamp_image
                                                    )
                                                    : asset(
                                                        'images/default-stamp.png'
                                                    ) }}"
                                            alt="{{
                                                $userStamp
                                                    ->stamp
                                                    ?->category
                                                ?? 'Passport stamp'
                                            }}"
                                        >

                                        <span>
                                            {{
                                                $userStamp
                                                    ->stamp
                                                    ?->category
                                                ?? 'Category'
                                            }}
                                        </span>

                                        <small class="passport-book-stamp-details">
                                            @if (
                                                $userStamp
                                                    ->completedExperience
                                                    ?->experience
                                            )
                                                <strong>
                                                    {{
                                                        $userStamp
                                                            ->completedExperience
                                                            ->experience
                                                            ->experiences_name
                                                    }}
                                                </strong>
                                            @endif

                                            <time>
                                                {{
                                                    $userStamp
                                                        ->collected_date
                                                        ?->format('d M Y')
                                                    ?? 'Unknown date'
                                                }}
                                            </time>
                                        </small>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <button
                        type="button"
                        class="passport-page-button next"
                        data-passport-next
                        aria-label="Next passport page"
                    >
                        &rsaquo;
                    </button>
                </div>

                <p class="passport-page-indicator">
                    Pages 1&ndash;2
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
@endsection

@push('scripts')
    @vite('resources/js/pages/passport.js')
@endpush