@extends('layouts.app')

@section('title', 'Living Heritage Malaysia')

@section('content')
    <section class="hero">
        <div class="hero-overlay"></div>
        <div class="container hero-content">
            <p class="eyebrow">Discover Malaysia, authentically</p>
            <h1>Experience the stories that make Malaysia come alive.</h1>
            <p class="hero-text">Find cultural workshops, local flavours, heritage places and traditions waiting to be shared.</p>

            <form class="search-bar" action="{{ route('experiences.index') }}" method="get">
                <label class="sr-only" for="experience-search">Search experiences</label>
                <input id="experience-search" name="search" type="search" placeholder="What would you like to experience?" autocomplete="off">
                <button type="submit">Search</button>
            </form>

            <a class="button button-light" href="{{ route('experiences.index') }}">Explore experiences</a>
        </div>
    </section>

    <section class="featured-section section" id="experiences">
        <div class="container">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">Start exploring</p>
                    <h2>Featured cultural experiences</h2>
                </div>
            </div>

            @if ($experiences->isEmpty())
                <div class="no-data">
                    <p>No cultural experiences are available at the moment.</p>
                </div>
            @else
                <div class="experience-grid">
                    @foreach ($experiences as $experience)
                        @include('components.experience-card', ['experience' => $experience])
                    @endforeach
                </div>

                <div class="section-action">
                    <a class="button button-primary" href="{{ route('experiences.index') }}">View All Experiences</a>
                </div>
            @endif
        </div>
    </section>
@endsection
