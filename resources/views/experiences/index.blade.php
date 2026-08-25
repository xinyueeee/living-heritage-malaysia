@extends('layouts.app')

@section('title', 'Discover | Living Heritage Malaysia')

@section('content')
    @php
        $selectedType = $types->firstWhere('type_id', (int) request('type'));
        $selectedCategory = $categories->firstWhere('category_id', (int) request('category'));
        $totalExperienceCount = $types->sum('experiences_count');
    @endphp

    <div class="discovery-page">
        <section class="discovery-hero" style="--discovery-hero-image: url('{{ asset('images/discovery/discover-hero.png') }}')">
            <div class="container discovery-hero-content">
                <p class="eyebrow">Discover. Experience. Preserve.</p>
                <h1>Discover Malaysia's<br>Living Heritage</h1>
                <p>Find authentic cultural experiences and festivals that connect you to our rich heritage.</p>
            </div>
        </section>

        <div class="container discovery-search-wrap">
            <form class="discovery-search" action="{{ route('experiences.index') }}" method="get">
                    @if (request()->filled('type'))
                        <input type="hidden" name="type" value="{{ request('type') }}">
                    @endif
                    @if ($selectedCategory)
                        <input type="hidden" name="category" value="{{ $selectedCategory->category_id }}">
                    @endif
                    @if (request()->filled('sort'))
                        <input type="hidden" name="sort" value="{{ request('sort') }}">
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

                    <button type="submit">Search</button>
            </form>
        </div>

        <div class="container discovery-content">
            <nav class="experience-type-tabs" aria-label="Experience type">
                <a
                    @class(['active' => $selectedType === null])
                    href="{{ route('experiences.index', request()->except(['page', 'type', 'category'])) }}"
                    @if ($selectedType === null) aria-current="page" @endif
                >
                    <span class="type-tab-icon" aria-hidden="true">◎</span>
                    <span>
                        <strong>All</strong>
                        <small>{{ $totalExperienceCount }} available</small>
                    </span>
                </a>

                @foreach ($types as $type)
                    <a
                        @class(['active' => $selectedType?->type_id === $type->type_id])
                        href="{{ route('experiences.index', array_merge(request()->except(['page', 'type', 'category']), ['type' => $type->type_id])) }}"
                        @if ($selectedType?->type_id === $type->type_id) aria-current="page" @endif
                    >
                        <span class="type-tab-icon" aria-hidden="true">{{ $type->type_name === 'Festival' ? '✣' : '◆' }}</span>
                        <span>
                            <strong>{{ $type->type_name === 'Cultural Experience' ? 'Cultural Experiences' : $type->type_name . 's' }}</strong>
                            <small>{{ $type->experiences_count }} available</small>
                        </span>
                    </a>
                @endforeach
            </nav>

            <section class="category-hierarchy-filter" aria-labelledby="category-filter-heading">
                <div>
                    <p class="filter-step">Step 2</p>
                    <h2 id="category-filter-heading">Choose a category</h2>
                    <p id="category-filter-help">
                        @if ($selectedType)
                            Showing categories for {{ $selectedType->type_name === 'Cultural Experience' ? 'Cultural Experiences' : 'Festivals' }} only.
                        @else
                            Select an experience type first to see its categories.
                        @endif
                    </p>
                </div>

                <form class="category-filter-form" action="{{ route('experiences.index') }}" method="get">
                    @foreach (['search', 'location', 'sort', 'type'] as $parameter)
                        @if (request()->filled($parameter))
                            <input type="hidden" name="{{ $parameter }}" value="{{ request($parameter) }}">
                        @endif
                    @endforeach

                    <label class="sr-only" for="category">Experience category</label>
                    <select
                        id="category"
                        name="category"
                        aria-describedby="category-filter-help"
                        onchange="this.form.submit()"
                        @disabled($selectedType === null)
                    >
                        <option value="">
                            {{ $selectedType ? 'All ' . ($selectedType->type_name === 'Festival' ? 'festival' : 'cultural experience') . ' categories' : 'Select an experience type first' }}
                        </option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->category_id }}" @selected($selectedCategory?->category_id === $category->category_id)>
                                {{ $category->category_name }}
                            </option>
                        @endforeach
                    </select>

                    <noscript>
                        <button type="submit" @disabled($selectedType === null)>Apply category</button>
                    </noscript>
                </form>
            </section>

            <div class="listing-toolbar">
                <div class="active-filters">
                    <span class="filter-label">Filters</span>
                    @if ($selectedType)
                        <span class="filter-chip">{{ $selectedType->type_name === 'Cultural Experience' ? 'Cultural Experiences' : 'Festivals' }}</span>
                    @endif
                    @if (request()->filled('search'))
                        <span class="filter-chip">“{{ request('search') }}”</span>
                    @endif
                    @if (request()->filled('location'))
                        <span class="filter-chip">{{ request('location') }}</span>
                    @endif
                    @if ($selectedCategory)
                        <span class="filter-chip">{{ $selectedCategory->category_name }}</span>
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

            <section class="map-preview-section" aria-labelledby="map-preview-heading">
                <div class="map-preview-content">
                    <p class="eyebrow">Discover by Location</p>
                    <h2 id="map-preview-heading">Explore Nearby on Interactive Map</h2>
                    <p>Discover cultural experiences and festivals across Malaysia, or use your location to find what is nearby.</p>
                    <a class="button button-primary" href="{{ route('experiences.map', request()->query()) }}">
                        Open Interactive Map <span aria-hidden="true">&rarr;</span>
                    </a>
                </div>
                <div
                    id="experience-map-preview"
                    class="map-preview-visual"
                    role="region"
                    aria-label="Preview map of experiences matching the current filters"
                ></div>
            </section>

            @push('scripts')
                <x-experience-map-data :experiences="$mapExperiences" />
            @endpush

            <aside class="trending-entry" aria-labelledby="trending-entry-heading">
                <div>
                    <p class="eyebrow">Popular This Week</p>
                    <h2 id="trending-entry-heading">See what heritage explorers are viewing</h2>
                </div>
                <a class="trending-entry-link" href="{{ route('experiences.trending') }}">
                    View Trending Experiences <span aria-hidden="true">&rarr;</span>
                </a>
            </aside>

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
