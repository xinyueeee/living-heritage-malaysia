@extends('layouts.app')

@section('title', 'Discover | Living Heritage Malaysia')

@section('content')
    <div class="discovery-page">
        <section class="discovery-hero">
            <div class="container discovery-hero-content">
                <p class="eyebrow">Discover. Experience. Preserve.</p>
                <h1>Discover Malaysia's<br>Living Heritage</h1>
                <p>Find authentic cultural experiences and festivals that connect you to our rich heritage.</p>

                <form class="discovery-search" action="{{ route('experiences.index') }}" method="get">
                    @if (request()->filled('type'))
                        <input type="hidden" name="type" value="{{ request('type') }}">
                    @endif

                    <div class="discovery-search-field discovery-search-keyword">
                        <label class="sr-only" for="search">Search</label>
                        <span aria-hidden="true">&#128269;</span>
                        <input id="search" name="search" type="search" value="{{ request('search') }}" placeholder="Search cultural experiences, locations, festivals...">
                    </div>

                    <div class="discovery-search-field">
                        <label class="sr-only" for="location">Location</label>
                        <span aria-hidden="true">&#128205;</span>
                        <input id="location" name="location" type="search" value="{{ request('location') }}" placeholder="All locations">
                    </div>

                    <div class="discovery-search-field">
                        <label class="sr-only" for="category">Category</label>
                        <select id="category" name="category">
                            <option value="">All categories</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->category_id }}" @selected((string) request('category') === (string) $category->category_id)>
                                    {{ $category->category_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit">Search</button>
                </form>
            </div>
        </section>

        <div class="container discovery-content">
            <nav class="experience-type-tabs" aria-label="Experience type">
                @foreach ($types as $type)
                    <a
                        @class(['active' => (string) request('type') === (string) $type->type_id])
                        href="{{ route('experiences.index', array_merge(request()->except(['page', 'type']), ['type' => $type->type_id])) }}"
                    >
                        <span class="type-tab-icon" aria-hidden="true">{{ $type->type_name === 'Festival' ? '✣' : '◆' }}</span>
                        <span>
                            <strong>{{ $type->type_name }}</strong>
                            <small>{{ $type->experiences_count }} available</small>
                        </span>
                    </a>
                @endforeach
            </nav>

            <div class="listing-toolbar">
                <div class="active-filters">
                    <span class="filter-label">Filters</span>
                    @if (request()->filled('search'))
                        <span class="filter-chip">“{{ request('search') }}”</span>
                    @endif
                    @if (request()->filled('location'))
                        <span class="filter-chip">{{ request('location') }}</span>
                    @endif
                    @if (request()->filled('category'))
                        <span class="filter-chip">{{ $categories->firstWhere('category_id', (int) request('category'))?->category_name }}</span>
                    @endif
                    @if (request()->hasAny(['search', 'location', 'category', 'type', 'sort']))
                        <a class="clear-filter" href="{{ route('experiences.index') }}">Clear all</a>
                    @endif
                </div>

                <form class="sort-form" action="{{ route('experiences.index') }}" method="get">
                    @foreach (request()->except(['sort', 'page']) as $key => $value)
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endforeach
                    <label for="sort">Sort by:</label>
                    <select id="sort" name="sort" onchange="this.form.submit()">
                        <option value="newest" @selected(request('sort', 'newest') === 'newest')>Newest</option>
                        <option value="oldest" @selected(request('sort') === 'oldest')>Oldest</option>
                    </select>
                </form>
            </div>

            @if ($errors->any())
                <div class="form-error" role="alert">Please check your search and filter values.</div>
            @endif

            @php
                $mapMarkers = $mapExperiences->map(function ($experience) {
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

                    return [
                        'name' => $experience->experiences_name,
                        'latitude' => (float) $experience->latitude,
                        'longitude' => (float) $experience->longitude,
                        'startDate' => $experience->start_date?->format('d M Y'),
                        'endDate' => $experience->end_date?->format('d M Y'),
                        'location' => $experience->location_name,
                        'shortDescription' => $experience->short_description,
                        'imageUrl' => $imageSource,
                        'externalImage' => $isExternalImage,
                        'detailsUrl' => route('experiences.show', $experience),
                    ];
                })->values();
            @endphp

            <section class="experience-map-section" aria-labelledby="experience-map-heading">
                <div class="experience-map-heading">
                    <div>
                        <p class="eyebrow">Across Malaysia</p>
                        <h2 id="experience-map-heading">Explore on Map</h2>
                    </div>
                    <p>{{ $mapMarkers->count() }} {{ Str::plural('mapped experience', $mapMarkers->count()) }}</p>
                </div>
                <div
                    id="experience-map"
                    class="experience-map"
                    role="region"
                    aria-label="Map of cultural experiences matching the current filters"
                ></div>
                <noscript>
                    <p class="experience-map-fallback">Enable JavaScript to view the map. The Experience List remains available below.</p>
                </noscript>
            </section>

            @push('scripts')
                <script>
                    window.livingHeritageExperienceMarkers = {{ Illuminate\Support\Js::from($mapMarkers) }};
                </script>
            @endpush

            <div class="results-heading">
                <div>
                    <p class="eyebrow">Explore Malaysia</p>
                    <h2>Cultural Experiences &amp; Festivals</h2>
                </div>
                <p class="result-count">{{ $experiences->total() }} {{ Str::plural('result', $experiences->total()) }} found</p>
            </div>

            @if ($experiences->isEmpty())
                <div class="no-data">
                    <span aria-hidden="true">&#128269;</span>
                    <h3>No matching experiences found</h3>
                    <p>Try changing or clearing your search filters.</p>
                </div>
            @else
                <div class="experience-grid discovery-experience-grid">
                    @foreach ($experiences as $experience)
                        @include('components.experience-card', ['experience' => $experience])
                    @endforeach
                </div>

                {{ $experiences->onEachSide(1)->links('components.pagination') }}
            @endif

            <section class="recommendation-callout">
                <span class="callout-icon" aria-hidden="true">&#128101;</span>
                <div>
                    <h2>Can't find what you're looking for?</h2>
                    <p>Tell us your interests and we'll recommend cultural experiences for you.</p>
                </div>
                <a class="button button-primary" href="{{ route('recommendations.index') }}">Personalize My Recommendations <span aria-hidden="true">&rarr;</span></a>
            </section>
        </div>
    </div>
@endsection
