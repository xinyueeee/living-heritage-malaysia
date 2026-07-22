@extends('layouts.app')

@section('title', 'Cultural Experiences | Living Heritage Malaysia')

@section('content')
    <section class="section experiences-page">
        <div class="container">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">Discover</p>
                    <h1>Cultural Experiences</h1>
                    <p class="section-description">Browse cultural experiences across Malaysia.</p>
                </div>
            </div>

            <form class="experience-filters" action="{{ route('experiences.index') }}" method="get">
                <div class="filter-field filter-field-search">
                    <label for="search">Search</label>
                    <input id="search" name="search" type="search" value="{{ request('search') }}" placeholder="Search experiences or cultural keywords">
                </div>

                <div class="filter-field">
                    <label for="location">Location</label>
                    <input id="location" name="location" type="search" value="{{ request('location') }}" placeholder="State, city, or location">
                </div>

                <div class="filter-field">
                    <label for="category">Category</label>
                    <select id="category" name="category">
                        <option value="">All categories</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category }}" @selected(request('category') === $category)>{{ $category }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="filter-field">
                    <label for="sort">Sort</label>
                    <select id="sort" name="sort">
                        <option value="newest" @selected(request('sort', 'newest') === 'newest')>Newest first</option>
                        <option value="oldest" @selected(request('sort') === 'oldest')>Oldest first</option>
                    </select>
                </div>

                <div class="filter-actions">
                    <button class="button button-primary" type="submit">Apply</button>
                    <a class="clear-filter" href="{{ route('experiences.index') }}">Clear</a>
                </div>
            </form>

            @if ($errors->any())
                <div class="form-error" role="alert">Please check your search and filter values.</div>
            @endif

            <p class="result-count">{{ $experiences->total() }} {{ Str::plural('experience', $experiences->total()) }} found</p>

            @if ($experiences->isEmpty())
                <div class="no-data">
                    <p>No cultural experiences match your search. Try changing or clearing the filters.</p>
                </div>
            @else
                <div class="experience-grid">
                    @foreach ($experiences as $experience)
                        @include('components.experience-card', ['experience' => $experience])
                    @endforeach
                </div>

                {{ $experiences->onEachSide(1)->links('components.pagination') }}
            @endif
        </div>
    </section>
@endsection
