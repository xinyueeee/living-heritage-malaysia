@extends('layouts.app')

@section('title', 'Trip Planner')

@section('content')

<div class="trip-planner-page">

    <div class="trip-planner-header">
        <h1>🗺️ Trip Planner</h1>

        <p>
            Plan your own Malaysian cultural experience.
        </p>
    </div>


    <div class="trip-planner-options">

        <!-- Plan New Trip -->
        <div class="trip-planner-card">

            <div class="trip-planner-card-icon">
                ✨
            </div>

            <h2>Plan New Trip</h2>

            <p>
                Choose a date and discover festivals,
                cultural experiences and events.
            </p>

            <a
                href="{{ route('trip.planner.create') }}"
                class="trip-planner-btn"
            >
                Start Planning →
            </a>

        </div>


        <!-- My Trips -->
        <div class="trip-planner-card">

            <div class="trip-planner-card-icon">
                📋
            </div>

            <h2>My Trips</h2>

            <p>
                View and manage your saved trips
                and itineraries.
            </p>

            <a
                href="{{ route('trip.planner.my-trips') }}"
                class="trip-planner-btn"
            >
                View My Trips →
            </a>

        </div>

    </div>

</div>

@endsection