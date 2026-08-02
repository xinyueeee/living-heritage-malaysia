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
                <span class="button button-primary" aria-disabled="true">Personalize My Recommendations <span aria-hidden="true">&rarr;</span></span>
            </section>
        </div>
    </div>
@endsection
