@extends('layouts.app')

@section('title', 'Interactive Map | Living Heritage Malaysia')

@section('content')
    <div class="interactive-map-page">
        <header class="interactive-map-hero">
            <div class="container interactive-map-hero-inner">
                <div>
                    <a class="map-back-link" href="{{ route('experiences.index', request()->query()) }}">&larr; Interactive Map</a>
                    <h1>Explore cultural experiences around you</h1>
                    <p>Browse heritage places, festivals and activities across Malaysia.</p>
                </div>
                <a class="map-list-view" href="{{ route('experiences.index', request()->query()) }}">List View</a>
            </div>
        </header>

        <div class="container interactive-map-content">
            <div class="interactive-map-toolbar">
                <div>
                    <strong>{{ $mapExperiences->count() }}</strong>
                    <span>{{ Str::plural('mapped experience', $mapExperiences->count()) }} matching current filters</span>
                </div>
                <div class="experience-map-controls">
                    <button id="use-my-location" class="map-control-button map-location-button" type="button">
                        <span aria-hidden="true">&#9678;</span> Use My Location
                    </button>
                    <button id="view-all-experiences" class="map-control-button" type="button" hidden>View All Experiences</button>
                </div>
            </div>
            <p id="experience-location-status" class="experience-location-status" role="status" aria-live="polite"></p>

            <div class="interactive-map-shell">
                <aside class="map-legend" aria-labelledby="map-legend-heading">
                    <div class="map-state-control">
                        <label for="map-state-select">Explore by state</label>
                        <select id="map-state-select" disabled>
                            <option value="">All Malaysia</option>
                        </select>
                        <button id="clear-map-state" class="map-state-clear" type="button" hidden>Show All Malaysia</button>
                        <p id="map-state-source">State boundaries: Department of Statistics Malaysia.</p>
                    </div>
                    <h2 id="map-legend-heading">Map Categories</h2>
                    <p>Show or hide marker groups.</p>
                    <div id="map-category-filters" class="map-category-filters"></div>
                    <div class="map-user-legend"><span class="user-legend-symbol">&#9678;</span> Your Location</div>
                </aside>
                <div
                    id="experience-map"
                    class="experience-map experience-map-full"
                    role="region"
                    aria-label="Interactive map of cultural experiences matching the current filters"
                ></div>
            </div>

            <section id="state-experiences" class="state-experiences" aria-labelledby="state-experiences-heading" hidden>
                <div class="state-experiences-heading">
                    <div>
                        <p class="eyebrow">Exploring Malaysia</p>
                        <h2 id="state-experiences-heading">All Malaysia</h2>
                    </div>
                    <button class="map-control-button" type="button" data-clear-map-state hidden>Show All Malaysia</button>
                </div>
                <p id="state-experiences-summary" class="state-experiences-summary" role="status" aria-live="polite"></p>
                <div id="state-experiences-list" class="state-experiences-list"></div>
            </section>

            <noscript>
                <p class="experience-map-fallback">Enable JavaScript to use the interactive map. You can still return to the Experience List.</p>
            </noscript>

            <section id="nearby-experiences" class="nearby-experiences nearby-experiences-page" aria-labelledby="nearby-experiences-heading" hidden>
                <div class="nearby-experiences-heading">
                    <div>
                        <p class="eyebrow">Around You</p>
                        <h2 id="nearby-experiences-heading">Nearby Cultural Experiences</h2>
                    </div>
                    <p id="nearby-experiences-summary">Sorted by nearest</p>
                </div>
                <div id="nearby-experiences-list" class="nearby-experiences-list"></div>
            </section>
        </div>
    </div>

    @push('scripts')
        <x-experience-map-data :experiences="$mapExperiences" />
    @endpush
@endsection
