@extends('layouts.app')

@section('title', 'Customize Digital Passport')

@push('styles')
    @vite('resources/css/engagement.css')
@endpush

@section('content')
<div class="engagement-page passport-customize-page">

    <section class="passport-page-header">
        <div class="container">
            <a
                href="{{ route('engagement.passport') }}"
                class="passport-back-link"
            >
                ← Back to Digital Passport
            </a>

            <span class="passport-eyebrow">
                PERSONALIZE YOUR PASSPORT
            </span>

            <h1>Customize Passport</h1>

            <p>
                Choose how your Digital Cultural Passport should
                appear. Your preferences are saved to your account.
            </p>
        </div>
    </section>

    <section class="passport-customize-content">
        <div class="container">
            <form
                method="POST"
                action="{{
                    route(
                        'engagement.passport.customization.update'
                    )
                }}"
                class="passport-customize-form"
            >
                @csrf
                @method('PUT')

                @if ($errors->any())
                    <div
                        class="passport-form-message error"
                        role="alert"
                    >
                        <strong>
                            Please check your selections.
                        </strong>

                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Theme selection --}}
                <fieldset class="passport-setting-card">
                    <legend>Passport Theme</legend>

                    <p>
                        Select the visual style for your passport.
                    </p>

                    <div class="passport-theme-options">
                        <label class="passport-option-card">
                            <input
                                type="radio"
                                name="display_theme"
                                value="heritage"
                                @checked(
                                    old(
                                        'display_theme',
                                        $passport->display_theme
                                    ) === 'heritage'
                                )
                            >

                            <span
                                class="passport-theme-preview
                                    theme-heritage"
                            ></span>

                            <strong>Heritage Classic</strong>

                            <small>
                                Traditional parchment and warm brown.
                            </small>
                        </label>

                        <label class="passport-option-card">
                            <input
                                type="radio"
                                name="display_theme"
                                value="batik"
                                @checked(
                                    old(
                                        'display_theme',
                                        $passport->display_theme
                                    ) === 'batik'
                                )
                            >

                            <span
                                class="passport-theme-preview
                                    theme-batik"
                            ></span>

                            <strong>Batik Red</strong>

                            <small>
                                Deep Malaysian red with batik colours.
                            </small>
                        </label>

                        <label class="passport-option-card">
                            <input
                                type="radio"
                                name="display_theme"
                                value="gold"
                                @checked(
                                    old(
                                        'display_theme',
                                        $passport->display_theme
                                    ) === 'gold'
                                )
                            >

                            <span
                                class="passport-theme-preview
                                    theme-gold"
                            ></span>

                            <strong>Golden Parchment</strong>

                            <small>
                                Warm gold with an elegant finish.
                            </small>
                        </label>
                    </div>
                </fieldset>

                {{-- Layout selection --}}
                <fieldset class="passport-setting-card">
                    <legend>Stamp Layout</legend>

                    <p>
                        Choose how stamps are presented when viewing
                        your passport.
                    </p>

                    <div class="passport-layout-options">
                        <label class="passport-option-card">
                            <input
                                type="radio"
                                name="display_layout"
                                value="book"
                                @checked(
                                    old(
                                        'display_layout',
                                        $passport->display_layout
                                    ) === 'book'
                                )
                            >

                            <span class="layout-preview layout-book">
                                <i></i>
                                <i></i>
                            </span>

                            <strong>Passport Book</strong>

                            <small>
                                Display stamps across book pages.
                            </small>
                        </label>

                        <label class="passport-option-card">
                            <input
                                type="radio"
                                name="display_layout"
                                value="grid"
                                @checked(
                                    old(
                                        'display_layout',
                                        $passport->display_layout
                                    ) === 'grid'
                                )
                            >

                            <span class="layout-preview layout-grid">
                                <i></i>
                                <i></i>
                                <i></i>
                                <i></i>
                            </span>

                            <strong>Gallery Grid</strong>

                            <small>
                                Display full stamp cards in a grid.
                            </small>
                        </label>
                    </div>
                </fieldset>

                {{-- Details preference --}}
                <fieldset class="passport-setting-card">
                    <legend>Stamp Information</legend>

                    <label class="passport-checkbox-setting">
                        <input
                            type="checkbox"
                            name="show_stamp_details"
                            value="1"
                            @checked(
                                old(
                                    'show_stamp_details',
                                    $passport->show_stamp_details
                                )
                            )
                        >

                        <span>
                            <strong>Show stamp details</strong>

                            <small>
                                Display the unlocking experience and
                                collection date.
                            </small>
                        </span>
                    </label>
                </fieldset>

                {{-- Collected stamp arrangement --}}
                <section class="passport-setting-card">
                    <div class="passport-arrangement-heading">
                        <div>
                            <h2>Your Collected Stamps</h2>

                            <p>
                                Drag and drop your stamps to change their order
                                inside the Passport Book.
                            </p>
                        </div>

                        @if ($passportStamps->isNotEmpty())
                            <span class="passport-drag-hint">
                                ↕ Drag to arrange
                            </span>
                        @endif
                    </div>

                    @if ($passportStamps->isEmpty())
                        <div class="passport-customize-empty">
                            No collected stamps are available to arrange yet.
                        </div>
                    @else
                        <div
                            class="passport-customize-stamps"
                            data-stamp-sort-list
                        >
                            @foreach ($passportStamps as $userStamp)
                                <div
                                    class="passport-customize-stamp"
                                    draggable="true"
                                    data-sortable-stamp
                                    data-user-stamp-id="{{
                                        $userStamp->user_stamp_id
                                    }}"
                                >
                                    <input
                                        type="hidden"
                                        name="stamp_order[]"
                                        value="{{ $userStamp->user_stamp_id }}"
                                    >

                                    <span
                                        class="passport-drag-handle"
                                        aria-hidden="true"
                                    >
                                        ⋮⋮
                                    </span>

                                    <img
                                        src="{{ $userStamp->stamp?->stamp_image
                                            ? asset(
                                                $userStamp
                                                    ->stamp
                                                    ->stamp_image
                                            )
                                            : asset(
                                                'images/default-stamp.png'
                                            ) }}"
                                        alt="{{ $userStamp
                                            ->stamp
                                            ?->category
                                            ?? 'Passport stamp' }}"
                                    >

                                    <span class="passport-customize-stamp-name">
                                        {{ $userStamp
                                            ->stamp
                                            ?->category
                                            ?? 'Category Stamp' }}
                                    </span>
                                </div>
                            @endforeach
                        </div>

                        <p class="passport-arrangement-note">
                            The first four stamps appear on page 1, the next
                            four on page 2, and so on. Click Save Changes when
                            you are finished.
                        </p>
                    @endif
                </section>

                <div class="passport-form-actions">
                    <a
                        href="{{ route('engagement.passport') }}"
                        class="passport-cancel-button"
                    >
                        Cancel
                    </a>

                    <button
                        type="submit"
                        class="hero-btn"
                    >
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </section>
</div>
@endsection

@push('scripts')
    @vite('resources/js/pages/passport.js')
@endpush