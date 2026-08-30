@extends('layouts.app')

@section('title', 'Discover Near Me')

@section('content')

<div class="nearby-page">

    <div class="nearby-header">

        <a
            href="{{ route('trip.planner.index') }}"
            class="nearby-back-button"
        >
            ← Back to Trip Planner
        </a>

        <h1>📍 Discover Near Me</h1>

        <p>
            Find cultural experiences near your current location.
        </p>

    </div>


    <div class="nearby-location-card">

        <div class="nearby-location-icon">
            📍
        </div>

        <h2>
            Find Cultural Experiences Near You
        </h2>

        <p>
            First, allow location access to find your current location.
            Then choose how far you want to search.
        </p>

        <div class="nearby-radius-filter">

        <label for="nearbyRadius">
            Search within:
        </label>

        <select
            id="nearbyRadius"
            class="nearby-radius-select"
        >
            <option value="10">10 km</option>
            <option value="15">15 km</option>
            <option value="20">20 km</option>
            <option value="30">30 km</option>
            <option value="50">50 km</option>
        </select>

    </div>
        <button
            type="button"
            class="nearby-location-button"
            onclick="getNearbyExperiences()"
        >
            📍 Find Experiences Near Me
        </button>

        <p
            id="nearbyStatus"
            class="nearby-status"
        ></p>

        {{-- Search Radius --}}
        

</div>


    <div
        id="nearbyResults"
        class="nearby-results"
        style="display: none;"
    >

        <h2>
            📍 Cultural Experiences Near You
        </h2>

        <p id="nearbyDescription"></p>

        <div
            id="nearbyExperienceGrid"
            class="nearby-experience-grid"
        >
        </div>

    </div>

</div>

@push('scripts')

<script>

function getNearbyExperiences()
{
    const status = document.getElementById('nearbyStatus');

    status.textContent = 'Getting your current location...';

    if (!navigator.geolocation)
    {
        status.textContent =
            'Location services are not supported by your browser.';

        return;
    }

    navigator.geolocation.getCurrentPosition(
        function(position)
        {
            const latitude = position.coords.latitude;
            const longitude = position.coords.longitude;

            window.nearbyLatitude = latitude;
            window.nearbyLongitude = longitude;

            status.textContent =
                'Location found. Searching nearby experiences...';

            findNearbyExperiences(
                latitude,
                longitude
            );
            },

        function(error)
        {
            if (error.code === 1)
            {
                status.textContent =
                    'Location permission was denied. Please allow location access.';
            }
            else
            {
                status.textContent =
                    'Unable to get your location. Please try again.';
            }
        }
    );
}


async function findNearbyExperiences(
    latitude,
    longitude
)
{
    try
    {
        const radius =
            document.getElementById('nearbyRadius').value;

        const response = await fetch(
            `/trip-planner/nearby?latitude=${latitude}&longitude=${longitude}&radius=${radius}`,
            {
                headers:
                {
                    'Accept': 'application/json'
                }
            }
        );

        const data = await response.json();

        if (!data.success)
        {
            document.getElementById('nearbyStatus').textContent =
                'Unable to find nearby experiences.';

            return;
        }

        displayNearbyExperiences(
            data.experiences
        );
    }
    catch(error)
    {
        console.error(error);

        document.getElementById('nearbyStatus').textContent =
            'Something went wrong. Please try again.';
    }
}


function displayNearbyExperiences(experiences)
{
    const results =
        document.getElementById('nearbyResults');

    const grid =
        document.getElementById('nearbyExperienceGrid');

    const description =
        document.getElementById('nearbyDescription');

    grid.innerHTML = '';

    if (experiences.length === 0)
    {
        description.textContent =
            'No cultural experiences were found nearby.';

        results.style.display = 'block';

        return;
    }

    description.textContent =
        'Here are the closest cultural experiences to your current location.';

    experiences.forEach(function(experience)
    {
        
        grid.innerHTML += `
            <div class="trip-event-card">

                ${experience.image_url ? `
                    <div class="trip-event-image">
                        <img
                            src="${experience.image_url}"
                            alt="${experience.experiences_name}"
                            class="trip-event-image-img"
                        >
                    </div>
                ` : ''}

                <div class="trip-event-card-content">

                    <span class="trip-event-type">
                        Cultural Experience
                    </span>

                    <h3>
                        ${experience.experiences_name}
                    </h3>

                    <p>
                        📍 ${experience.location_name ?? 'Location unavailable'}
                    </p>

                    <p>
                        📏 ${experience.distance_km} km away
                    </p>

                    <p>
                        ${experience.short_description ?? ''}
                    </p>

                    <div class="nearby-card-actions">

                    <button
                            type="button"
                            class="trip-add-button"
                            onclick="openNearbyTripPopup(
                                '${experience.experiences_id}',
                                '${experience.experiences_name.replace(/'/g, "\\'")}',
                                '${experience.nearby_area}'
                            )"
                        >
                            + Add to My Trip
                        </button>

                        <button
                            type="button"
                            class="trip-learn-button"
                            onclick="learnNearbyMore('${experience.experiences_id}')"
                        >
                            Learn More →
                        </button>

                        

                    </div>

                </div>

            </div>
        `;
    });

    results.style.display = 'block';
}


function learnNearbyMore(id)
{
    window.location.href =
        `/experiences/${id}`;
}

async function openNearbyTripPopup(
    experienceId,
    experienceName,
    experienceArea
)
{
    const experienceInput =
        document.getElementById(
            'nearbyExperienceId'
        );

    const areaInput =
        document.getElementById(
            'nearbyExperienceArea'
        );

    const selectedExperience =
        document.getElementById(
            'nearbySelectedExperience'
        );

    const tripSelect =
        document.getElementById(
            'nearbyTripId'
        );

    const message =
        document.getElementById(
            'nearbyTripMessage'
        );


    // Store selected experience
    experienceInput.value = experienceId;

    areaInput.value = experienceArea;


    selectedExperience.textContent =
        'Add "' +
        experienceName +
        '" to a ' +
        experienceArea +
        ' trip.';


    // Show loading state
    tripSelect.innerHTML = '';

    const loadingOption =
        document.createElement('option');

    loadingOption.value = '';

    loadingOption.textContent =
        'Loading your trips...';

    loadingOption.disabled = true;

    loadingOption.selected = true;

    tripSelect.appendChild(
        loadingOption
    );


    message.textContent = '';

    message.classList.remove(
        'success'
    );


    // Open popup immediately
    document.getElementById(
        'nearbyTripPopup'
    ).style.display = 'flex';


    try
    {
        /*
        |--------------------------------------------------------------------------
        | Get latest trips from database
        |--------------------------------------------------------------------------
        */

        const response = await fetch(
            "{{ route('trip.planner.nearby.trips') }}",
            {
                method: 'GET',

                headers:
                {
                    'Accept':
                        'application/json'
                },

                cache: 'no-store'
            }
        );


        const data =
            await response.json();


        if (!data.success)
        {
            throw new Error(
                'Unable to load trips.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Clear dropdown
        |--------------------------------------------------------------------------
        */

        tripSelect.innerHTML = '';


        /*
        |--------------------------------------------------------------------------
        | Default option
        |--------------------------------------------------------------------------
        */

        const defaultOption =
            document.createElement('option');

        defaultOption.value = '';

        defaultOption.textContent =
            '-- Select a trip --';

        defaultOption.selected = true;

        tripSelect.appendChild(
            defaultOption
        );


        /*
        |--------------------------------------------------------------------------
        | Filter trips
        |--------------------------------------------------------------------------
        */

        const matchingTrips =
            data.trips.filter(function(trip)
            {
                /*
                |--------------------------------------------------------------------------
                | 1. Same area
                |--------------------------------------------------------------------------
                */

                const sameArea =
                    trip.area &&
                    trip.area.toLowerCase() ===
                    experienceArea.toLowerCase();


                /*
                |--------------------------------------------------------------------------
                | 2. Experience not already added
                |--------------------------------------------------------------------------
                */

                const alreadyContainsExperience =
                    trip.experience_ids
                        .map(String)
                        .includes(
                            String(experienceId)
                        );


                return (
                    sameArea &&
                    !alreadyContainsExperience
                );
            });


        /*
        |--------------------------------------------------------------------------
        | Add available trips
        |--------------------------------------------------------------------------
        */

        matchingTrips.forEach(function(trip)
        {
            const option =
                document.createElement('option');

            option.value =
                trip.id;

            option.textContent =
                trip.trip_name +
                ' — ' +
                trip.area +
                ' — ' +
                trip.trip_date;

            tripSelect.appendChild(
                option
            );
        });


        /*
        |--------------------------------------------------------------------------
        | Create New Trip
        |--------------------------------------------------------------------------
        */

        const createNewOption =
            document.createElement('option');

        createNewOption.value =
            'create_new';

        createNewOption.textContent =
            '+ Create New Trip';

        tripSelect.appendChild(
            createNewOption
        );


        /*
        |--------------------------------------------------------------------------
        | No available trip
        |--------------------------------------------------------------------------
        */

        if (matchingTrips.length === 0)
        {
            const noTripOption =
                document.createElement('option');

            noTripOption.value = '';

            noTripOption.textContent =
                'No available ' +
                experienceArea +
                ' trips';

            noTripOption.disabled = true;

            tripSelect.insertBefore(
                noTripOption,
                createNewOption
            );
        }

    }
    catch(error)
    {
        console.error(
            'Load nearby trips error:',
            error
        );


        tripSelect.innerHTML = '';


        const errorOption =
            document.createElement('option');

        errorOption.value = '';

        errorOption.textContent =
            'Unable to load trips';

        errorOption.disabled = true;

        errorOption.selected = true;

        tripSelect.appendChild(
            errorOption
        );
    }
}

 

function handleNearbyTripSelection(select)
{
    if (select.value !== 'create_new') {
        return;
    }

    const experienceId =
        document.getElementById(
            'nearbyExperienceId'
        ).value;

    const experienceArea =
        document.getElementById(
            'nearbyExperienceArea'
        ).value;

    const createUrl =
        "{{ route('trip.planner.create') }}" +
        "?experience_id=" +
        encodeURIComponent(experienceId) +
        "&area=" +
        encodeURIComponent(experienceArea);

    window.location.href = createUrl;
}


function closeNearbyTripPopup()
{
    document.getElementById(
        'nearbyTripPopup'
    ).style.display = 'none';
}


async function addNearbyExperience(event)
{
    event.preventDefault();

    const experienceId =
        document.getElementById(
            'nearbyExperienceId'
        ).value;

    const tripId =
        document.getElementById(
            'nearbyTripId'
        ).value;

    const message =
        document.getElementById(
            'nearbyTripMessage'
        );

    if (!tripId)
    {
        message.textContent =
            'Please select a trip first.';

        return;
    }

    try
    {
        const response = await fetch(
            "{{ route('trip.planner.add') }}",
            {
                method: 'POST',

                headers:
                {
                    'Content-Type':
                        'application/json',

                    'Accept':
                        'application/json',

                    'X-CSRF-TOKEN':
                        document.querySelector(
                            'meta[name="csrf-token"]'
                        ).getAttribute('content')
                },

                body: JSON.stringify({
                    experience_id: experienceId,
                    trip_id: tripId,
                    item_type: 'cultural'
                })
            }
        );

        const data =
            await response.json();

        if (data.success)
        {
            message.textContent =
                data.message ||
                'Experience has been added to your trip.';

            message.classList.add(
                'success'
            );

            setTimeout(
                function()
                {
                    closeNearbyTripPopup();
                },
                1200
            );

            return;
        }

        message.textContent =
            data.message ||
            'Unable to add this experience.';

    }
    catch(error)
    {
        console.error(error);

        message.textContent =
            'Something went wrong. Please try again.';
    }
}

function searchNearbyByRadius()
{
    if (
        !window.nearbyLatitude ||
        !window.nearbyLongitude
    )
    {
        document.getElementById('nearbyStatus').textContent =
            'Please click "Use My Current Location" first.';

        return;
    }

    const radius =
        document.getElementById('nearbyRadius').value;

    document.getElementById('nearbyStatus').textContent =
        'Searching for experiences within ' +
        radius +
        ' km...';

    findNearbyExperiences(
        window.nearbyLatitude,
        window.nearbyLongitude
    );
}



</script>

@endpush

<div
    id="nearbyTripPopup"
    class="nearby-trip-popup-overlay"
    style="display: none;"
>

    <div class="nearby-trip-popup">

        <button
            type="button"
            class="nearby-trip-popup-close"
            onclick="closeNearbyTripPopup()"
        >
            ×
        </button>

        <div class="nearby-trip-popup-icon">
            🧳
        </div>

        <h2>
            Add to My Trip
        </h2>

        <p id="nearbySelectedExperience">
            Select an existing trip to add this experience.
        </p>

        <form
            id="nearbyAddTripForm"
            onsubmit="addNearbyExperience(event)"
        >

            <input
                type="hidden"
                id="nearbyExperienceId"
            >


            <input
                type="hidden"
                id="nearbyExperienceArea"
            >

            <label for="nearbyTripId">
                Select an existing trip:
            </label>

            <select
                id="nearbyTripId"
                class="nearby-trip-popup-select"
                required
                onchange="handleNearbyTripSelection(this)"
            >
                <option value="">
                    -- Select a trip --
                </option>

                @foreach($trips as $trip)

                    <option
                        value="{{ $trip->id }}"
                        data-trip-name="{{ $trip->trip_name }}"
                        data-area="{{ $trip->area }}"
                        data-trip-date="{{ $trip->trip_date->format('d F Y') }}"
                    >
                        {{ $trip->trip_name }}
                        — {{ $trip->area }}
                        — {{ $trip->trip_date->format('d F Y') }}
                    </option>

                @endforeach

                <option value="create_new">
                    + Create New Trip
                </option>

            </select>     

            <div class="nearby-trip-popup-actions">

                <button
                    type="button"
                    class="nearby-trip-cancel-button"
                    onclick="closeNearbyTripPopup()"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="nearby-trip-confirm-button"
                >
                    Add to Trip
                </button>

            </div>

        </form>

        <p
            id="nearbyTripMessage"
            class="nearby-trip-message"
        ></p>

    </div>

</div>

@endsection