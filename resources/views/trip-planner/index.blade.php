@extends('layouts.app')

@section('title', 'Trip Planner')

@section('content')

<div
    class="trip-planner-banner"
    style="background-image: url('{{ asset('images/tripPlanner/trip-planner-banner.jpg') }}');"
>

    <div class="trip-planner-banner-overlay">

        <div class="trip-planner-banner-content">

            <h1>
                Plan Your Malaysian Cultural Journey
            </h1>

            <p>
                Discover festivals, cultural experiences and
                unforgettable places across Malaysia.
            </p>

        </div>

    </div>

</div>

<div class="trip-planner-page">

    <div class="trip-planner-header">

        <h1>🗺️ Trip Planner</h1>

        <p>
            Plan your own Malaysian cultural experience.
        </p>

    </div>


    <div class="trip-planner-options">

    {{-- Plan New Trip --}}

    <div class="trip-planner-card">

        <div class="trip-planner-card-icon">
            ✨
        </div>

        <h2>
            Plan New Trip
        </h2>

        <p>
            Choose a date and discover festivals,
            cultural experiences and events.
        </p>

        <a
            href="{{ route('trip.planner.create') }}"
            class="trip-planner-button"
        >
            Start Planning →
        </a>

    </div>


    {{-- My Trips --}}

    <div class="trip-planner-card">

        <div class="trip-planner-card-icon">
            📋
        </div>

        <h2>
            My Trips
        </h2>

        <p>
            View and manage your saved trips
            and itineraries.
        </p>

        <a
            href="{{ route('trip.planner.my-trips') }}"
            class="trip-planner-button"
        >
            View My Trips →
        </a>

    </div>


    {{-- Discover Near Me --}}

    <div class="trip-planner-card">

        <div class="trip-planner-card-icon">
            📍
        </div>

        <h2>
            Discover Near Me
        </h2>

        <p>
            Find cultural experiences
            near your current location.
        </p>

        <a
            href="{{ route('trip.planner.nearby') }}"
            class="trip-planner-button"
        >
            Explore Near Me →
        </a>

    </div>

</div>

</div>

@endsection