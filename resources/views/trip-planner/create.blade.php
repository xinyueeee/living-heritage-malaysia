@extends('layouts.app')

@section('title', 'Plan New Trip')

@section('content')

<div class="trip-create-page">

    <div class="trip-create-header">

        <a
            href="{{ route('trip.planner.index') }}"
            class="trip-back-button"
        >
            ← Back to Trip Planner
        </a>

        <h1>✨ Plan a New Trip</h1>

        <p>
            Choose when and where you want to explore Malaysia.
        </p>

    </div>

    <div class="trip-date-section">

    <div class="trip-date-icon">
        📅
    </div>

    <h2>
        Plan Your Trip
    </h2>

    <p>
        Choose when and where you want to explore Malaysia.
    </p>

    <form
        method="GET"
        action="{{ route('trip.planner.events') }}"
    >

        {{-- Trip Name --}}
        <div class="trip-form-group">

            <label for="trip_name">
                Trip Name
            </label>

            <input
                type="text"
                id="trip_name"
                name="trip_name"
                class="trip-date-input"
                placeholder="e.g. Selangor Cultural Adventure"
                maxlength="100"
                required
            >

        </div>


        {{-- Trip Date --}}
        <div class="trip-form-group">

            <label for="trip_date">
                Trip Date
            </label>

            <input
                type="date"
                id="trip_date"
                name="trip_date"
                class="trip-date-input"
                min="{{ now()->format('Y-m-d') }}"
                required
            >

        </div>


        {{-- Area --}}
        <div class="trip-form-group">

            <label for="area">
                Which area are you exploring?
            </label>

            @if(!empty($experienceArea))

                {{-- Area is determined by the selected experience --}}
                <input
                    type="text"
                    class="trip-date-input"
                    value="{{ $experienceArea }}"
                    readonly
                >

                <input
                    type="hidden"
                    name="area"
                    value="{{ $experienceArea }}"
                >

                <small>
                    The area is automatically selected based on the experience.
                </small>

            @else

                {{-- Normal new trip --}}
                <select
                    id="area"
                    name="area"
                    class="trip-date-input"
                    required
                >

                    <option value="">
                        Select an area
                    </option>

                    <option value="Kuala Lumpur">
                        Kuala Lumpur
                    </option>

                    <option value="Penang">
                        Penang
                    </option>

                    <option value="Selangor">
                        Selangor
                    </option>

                    <option value="Johor">
                        Johor
                    </option>

                    <option value="Perak">
                        Perak
                    </option>

                    <option value="Melaka">
                        Melaka
                    </option>

                    <option value="Negeri Sembilan">
                        Negeri Sembilan
                    </option>

                    <option value="Pahang">
                        Pahang
                    </option>

                    <option value="Terengganu">
                        Terengganu
                    </option>

                    <option value="Kelantan">
                        Kelantan
                    </option>

                    <option value="Kedah">
                        Kedah
                    </option>

                    <option value="Perlis">
                        Perlis
                    </option>

                    <option value="Sabah">
                        Sabah
                    </option>

                    <option value="Sarawak">
                        Sarawak
                    </option>

                    <option value="Putrajaya">
                        Putrajaya
                    </option>

                    <option value="Labuan">
                        Labuan
                    </option>

                </select>

            @endif

        </div>
        

        <input
            type="hidden"
            name="experience_id"
            value="{{ $experienceId ?? '' }}"
        >

        <input
            type="hidden"
            name="experience_area"
            value="{{ $experienceArea ?? '' }}"
        >


        {{-- Continue --}}
        <button
            type="submit"
            class="trip-save-button"
        >
            Continue →
        </button>

    </form>

</div>


   

</div>

@endsection