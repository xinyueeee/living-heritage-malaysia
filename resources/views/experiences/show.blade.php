@extends('layouts.app')

@section('title', $experience->experiences_name . ' | Living Heritage Malaysia')

@php
    $imagePath = is_string($experience->image_url)
        ? ltrim(str_replace('\\', '/', trim($experience->image_url)), '/')
        : null;
    $isExternalImage = filled($imagePath)
        && (str_starts_with(strtolower($imagePath), 'http://')
            || str_starts_with(strtolower($imagePath), 'https://'));
    $isSafeRelativePath = filled($imagePath)
        && !str_contains($imagePath, '../')
        && !$isExternalImage;
    $imageSource = $isExternalImage
        ? $imagePath
        : ($isSafeRelativePath && is_file(public_path($imagePath)) ? asset($imagePath) : null);
@endphp

@section('content')
    <div class="experience-details-page">
        <div class="container experience-details-container">
            <a class="experience-back-link" href="{{ route('experiences.index') }}">&larr; Back to Experiences</a>

            <article class="experience-details-card">
                <div class="experience-details-media">
                    <div class="experience-details-placeholder" role="img" aria-label="Image unavailable for {{ $experience->experiences_name }}">
                        <span aria-hidden="true">&#128247;</span>
                        <p>Image unavailable</p>
                    </div>
                    @if ($imageSource)
                        <img
                            src="{{ $imageSource }}"
                            alt="{{ $experience->experiences_name }}"
                            @if ($isExternalImage) referrerpolicy="no-referrer" @endif
                            onerror="this.style.display='none';"
                        >
                    @endif
                </div>

                <div class="experience-details-content">
                    @if ($experience->category?->category_name || $experience->type?->type_name)
                        <p class="experience-details-eyebrow">
                            {{ $experience->category?->category_name ?? $experience->type?->type_name }}
                        </p>
                    @endif

                    <div class="experience-details-heading">
                        <h1>{{ $experience->experiences_name }}</h1>
                        @auth
                            <form method="POST" action="{{ $isSaved ? route('experiences.saved.destroy', $experience) : route('experiences.saved.store', $experience) }}">
                                @csrf
                                @if ($isSaved)
                                    @method('DELETE')
                                @endif
                                <button type="submit" class="experience-save-button {{ $isSaved ? 'is-saved' : '' }}">
                                    <span aria-hidden="true">{{ $isSaved ? '♥' : '♡' }}</span>
                                    {{ $isSaved ? 'Saved' : 'Save Experience' }}
                                </button>
                            </form>
                        @else
                            <a class="experience-save-button" href="{{ route('login') }}"><span aria-hidden="true">♡</span> Save Experience</a>
                        @endauth
                    </div>

                    <dl class="experience-details-meta">
                        @if ($experience->start_date)
                            <div>
                                <dt>Date</dt>
                                <dd>
                                    {{ $experience->start_date->format('d F Y') }}
                                    @if ($experience->end_date && !$experience->end_date->isSameDay($experience->start_date))
                                        &ndash; {{ $experience->end_date->format('d F Y') }}
                                    @endif
                                </dd>
                            </div>
                        @endif
                        @if (filled($experience->location_name))
                            <div><dt>Location</dt><dd>{{ $experience->location_name }}</dd></div>
                        @endif
                        @if ($experience->type?->type_name)
                            <div><dt>Experience type</dt><dd>{{ $experience->type->type_name }}</dd></div>
                        @endif
                        @if ($experience->category?->category_name)
                            <div><dt>Category</dt><dd>{{ $experience->category->category_name }}</dd></div>
                        @endif
                        @if (!is_null($experience->price))
                            <div><dt>Price</dt><dd>RM {{ number_format((float) $experience->price, 2) }}</dd></div>
                        @endif
                        @if (filled($experience->duration))
                            <div><dt>Duration</dt><dd>{{ $experience->duration }}</dd></div>
                        @endif
                        @if (filled($experience->operating_hours))
                            <div><dt>Operating hours</dt><dd>{{ $experience->operating_hours }}</dd></div>
                        @endif
                        @if (filled($experience->contact_number))
                            <div><dt>Contact</dt><dd>{{ $experience->contact_number }}</dd></div>
                        @endif
                    </dl>

                    <section class="experience-description" aria-labelledby="experience-description-heading">
                        <h2 id="experience-description-heading">About this experience</h2>
                        <p>{{ $experience->description }}</p>
                    </section>
                </div>
            </article>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .experience-details-page { padding: 48px 0 80px; background: var(--cream); }
        .experience-details-container { max-width: 980px; }
        .experience-back-link { display: inline-block; margin-bottom: 20px; color: var(--primary); font-weight: 700; }
        .experience-back-link:hover { text-decoration: underline; }
        .experience-details-card { overflow: hidden; border: 1px solid var(--border); border-radius: 12px; background: var(--surface); box-shadow: var(--shadow); }
        .experience-details-media { position: relative; height: clamp(280px, 48vw, 500px); }
        .experience-details-media img,
        .experience-details-placeholder { display: block; width: 100%; height: clamp(280px, 48vw, 500px); }
        .experience-details-media img { position: absolute; inset: 0; z-index: 1; object-fit: cover; }
        .experience-details-placeholder { display: grid; place-content: center; background: linear-gradient(145deg, #eee1d3, #dfc6ae 55%, #cba27f); color: var(--primary); text-align: center; }
        .experience-details-placeholder span { font-size: 2.5rem; }
        .experience-details-placeholder p { margin: 5px 0 0; font-weight: 700; }
        .experience-details-content { padding: clamp(24px, 5vw, 48px); }
        .experience-details-eyebrow { margin: 0 0 8px; color: var(--primary); font-size: .8rem; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; }
        .experience-details-content h1 { margin: 0; font-family: Georgia, 'Times New Roman', serif; font-size: clamp(2rem, 5vw, 3.3rem); line-height: 1.15; }
        .experience-details-heading { display: flex; align-items: flex-start; justify-content: space-between; gap: 24px; }
        .experience-details-heading form { flex-shrink: 0; }
        .experience-save-button { display: inline-flex; align-items: center; gap: 8px; padding: 11px 16px; border: 1px solid var(--primary); border-radius: 7px; background: #fff; color: var(--primary); font: inherit; font-weight: 700; cursor: pointer; white-space: nowrap; }
        .experience-save-button:hover,
        .experience-save-button.is-saved { background: var(--primary); color: #fff; }
        .experience-details-meta { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px 28px; margin: 30px 0; padding: 22px 0; border-top: 1px solid var(--border); border-bottom: 1px solid var(--border); }
        .experience-details-meta div { min-width: 0; }
        .experience-details-meta dt { color: var(--muted); font-size: .75rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; }
        .experience-details-meta dd { margin: 3px 0 0; overflow-wrap: anywhere; font-weight: 600; }
        .experience-description h2 { margin: 0 0 12px; font-family: Georgia, 'Times New Roman', serif; font-size: 1.55rem; }
        .experience-description p { margin: 0; color: var(--muted); line-height: 1.8; white-space: pre-line; }
        @media (max-width: 640px) {
            .experience-details-page { padding-top: 28px; }
            .experience-details-meta { grid-template-columns: 1fr; }
            .experience-details-heading { align-items: stretch; flex-direction: column; }
            .experience-save-button { justify-content: center; }
        }
    </style>
@endpush
