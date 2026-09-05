@extends('layouts.app')

@section('title', 'Trip Events')

@section('content')

@php
    $isPastTrip = $tripPlan->trip_date->toDateString() < today()->toDateString();
@endphp

<div class="trip-create-page">

    <div class="my-trips-header">

        <a
            href="{{ route('trip.planner.index') }}"
            class="trip-back-button"
        >
            ← Back to My Trips
        </a>

        @if($isPastTrip)
            <h1>📖 Trip History</h1>
        @else
            <h1>🎉 Plan Your Trip</h1>
        @endif

        <p>
            Discover festivals and cultural experiences
            for your trip.
        </p>

        <div class="trip-summary">

    <div class="trip-summary-header">
        <h2>🧳 Your Trip</h2>
        <p>A quick overview of your planned experiences.</p>
    </div>

    <div class="trip-summary-details">

        <div class="trip-summary-info">
            <span>📋</span>
            <div>
                <strong>Trip Name</strong>
                <p>{{ $tripPlan->trip_name }}</p>
            </div>
        </div>

        <div class="trip-summary-info">
            <span>📅</span>
            <div>
                <strong>Date</strong>
                <p>{{ $tripPlan->trip_date->format('d F Y') }}</p>
            </div>
        </div>

        <div class="trip-summary-info">
            <span>📍</span>
            <div>
                <strong>Area</strong>
                <p>{{ $tripPlan->area }}</p>
            </div>
        </div>

    </div>

    <div class="trip-summary-experiences">

    <h3>
        Experiences & Festivals Added 
        <span>{{ count($addedExperienceIds) }}</span>
    </h3>

    @if($tripPlan->items->isNotEmpty())

        <ul class="trip-summary-experience-list">

            @foreach($tripPlan->items as $item)

                @if($item->experience)

                    <li>
                        ✓ {{ $item->experience->experiences_name }}
                    </li>

                @endif

            @endforeach

        </ul>

    @else

        <p class="trip-summary-empty">
            No experiences added yet. Start exploring below!
        </p>

    @endif

</div>
</div>
        

    </div>
@if($isPastTrip)
    <div class="trip-past-notice">
        <strong>⚪ This trip has already passed.</strong>
        <p>
            Past trips are read-only. You can view your previous experiences,
            but you cannot add or remove experiences.
        </p>
    </div>
@else

<div class="trip-events-section">

    <div class="trip-section-heading">
        <h2>🎊 Festivals in {{ $area }}</h2>

        <p>
            Festivals happening on
            <strong>{{ $area }}</strong>
            on
            <strong>{{ \Carbon\Carbon::parse($date)->format('d F Y') }}</strong>.
        </p>
    </div>

    @if($festivals->isEmpty())

        <div class="my-trips-empty">

            <div class="my-trips-empty-icon">
                😔
            </div>

            <h2>
                No Festivals Found
            </h2>

            <p>
                There are no festivals available
                on your selected date.
            </p>

        </div>

    @else

        <div class="trip-event-grid">

            @foreach($festivals as $festival)

                <div class="trip-event-card">

                    {{-- Festival Image --}}

                    @if($festival->image_url)

                        <div class="trip-event-image">

                            <img
                                src="{{ $festival->image_url }}"
                                alt="{{ $festival->experiences_name }}"
                                class="trip-event-image-img"
                                referrerpolicy="no-referrer"
                            >

                        </div>

                    @endif


                    {{-- Festival Content --}}

                    <div class="trip-event-card-content">

                        <span class="trip-event-type">
                            Festival
                        </span>

                        <h3>
                            {{ $festival->experiences_name }}
                        </h3>


                        @if($festival->location_name)

                            <p>
                                📍 {{ $festival->location_name }}
                            </p>

                        @endif


                        @if($festival->short_description)

                            <p>
                                {{ $festival->short_description }}
                            </p>

                        @elseif($festival->description)

                            <p>
                                {{ $festival->description }}
                            </p>

                        @endif


                        @if($festival->start_date && $festival->end_date)

                            <p>
                                📅
                                {{ \Carbon\Carbon::parse($festival->start_date)->format('d M Y') }}
                                -
                                {{ \Carbon\Carbon::parse($festival->end_date)->format('d M Y') }}
                            </p>

                        @endif

{{-- Buttons --}}

@if(in_array($festival->experiences_id, $addedExperienceIds))

    <div class="trip-actions">

        @if(!$isPastTrip)
            <button
                type="button"
                class="trip-remove-button"
                onclick="removeFromTrip(
                    '{{ $festival->experiences_id }}',
                    '{{ $tripPlan->id }}'
                )"
            >
                Remove
            </button>
        @endif

        <button
            type="button"
            class="trip-learn-button"
            onclick="learnMore('{{ $festival->experiences_id }}')"
        >
            Learn More →
        </button>

    </div>

@else

    <div class="trip-actions">

        @if(!$isPastTrip)
            <button
                type="button"
                class="trip-add-button"
                onclick="addToTrip(
                    '{{ $festival->experiences_id }}',
                    'festival',
                    '{{ $tripPlan->id }}'
                )"
            >
                + Add to My Trip
            </button>
        @endif

        <button
            type="button"
            class="trip-learn-button"
            onclick="learnMore('{{ $festival->experiences_id }}')"
        >
            Learn More →
        </button>

    </div>

@endif
                        

                    </div>

                </div>

            @endforeach

        </div>

    @endif

</div>


{{-- Cultural Experiences --}}

<div class="trip-events-section">

    <div class="trip-section-heading">
        <h2>
            🎨 Cultural Experiences Near {{ $area }}
        </h2>

        <p>
            Cultural experiences available in or near
            <strong>{{ $area }}</strong>.
        </p>
    </div>


    @if($culturalExperiences->isEmpty())

        <div class="my-trips-empty">

            <div class="my-trips-empty-icon">
                😔
            </div>

            <h3>
                No Cultural Experiences Found Near {{ $area }}
            </h3>

            <p>
                We couldn't find any cultural experiences in or near
                <strong>{{ $area }}</strong> for your trip.
            </p>

            <p class="trip-empty-hint">
                Try selecting another area to discover more cultural experiences.
            </p>

        </div>

    @else

        <div class="trip-event-grid">

            @foreach($culturalExperiences as $experience)

                <div class="trip-event-card">

                    {{-- Cultural Experience Image --}}

                    @if($experience->image_url)

                        <div class="trip-event-image">

                            <img
                                src="{{ $experience->image_url }}"
                                alt="{{ $experience->experiences_name }}"
                                class="trip-event-image-img"
                                referrerpolicy="no-referrer"
                            >

                        </div>

                    @endif


                    {{-- Cultural Experience Content --}}

                    <div class="trip-event-card-content">

                        <span class="trip-event-type">
                            Cultural Experience
                        </span>


                        <h3>
                            {{ $experience->experiences_name }}
                        </h3>


                        @if($experience->location_name)

                            <p>
                                📍 {{ $experience->location_name }}
                            </p>

                        @endif


                        @if($experience->short_description)

                            <p>
                                {{ $experience->short_description }}
                            </p>

                        @elseif($experience->description)

                            <p>
                                {{ $experience->description }}
                            </p>

                        @endif


                        {{-- Buttons --}}

@if(in_array($experience->experiences_id, $addedExperienceIds))

    <div class="trip-actions">

        @if(!$isPastTrip)
            <button
                type="button"
                class="trip-add-button trip-added-button"
                disabled
            >
                ✓ Added to My Trip
            </button>

            <button
                type="button"
                class="trip-remove-button"
                onclick="removeFromTrip(
                    '{{ $experience->experiences_id }}',
                    '{{ $tripPlan->id }}'
                )"
            >
                Remove
            </button>
        @endif

        <button
            type="button"
            class="trip-learn-button"
            onclick="learnMore('{{ $experience->experiences_id }}')"
        >
            Learn More →
        </button>

    </div>

@else

    @if(!$isPastTrip)

        <div class="trip-actions">

            <button
                type="button"
                class="trip-add-button"
                onclick="addToTrip(
                    '{{ $experience->experiences_id }}',
                    'cultural',
                    '{{ $tripPlan->id }}'
                )"
            >
                + Add to My Trip
            </button>

            <button
                type="button"
                class="trip-learn-button"
                onclick="learnMore('{{ $experience->experiences_id }}')"
            >
                Learn More →
            </button>

        </div>

    @else

        <div class="trip-actions">

            <button
                type="button"
                class="trip-learn-button"
                onclick="learnMore('{{ $experience->experiences_id }}')"
            >
                Learn More →
            </button>

        </div>

    @endif

@endif

                    </div>

                </div>

            @endforeach

        </div>

    @endif

</div>
    
                               


</div>
@endif
{{-- Trip Planner Popup --}}

<div
    id="tripPopup"
    class="trip-popup-overlay"
    style="display: none;"
>

    <div class="trip-popup">

        <div
            id="tripPopupIcon"
            class="trip-popup-icon"
        >
        </div>

        <h2 id="tripPopupTitle">
            Event Added
        </h2>

        <p id="tripPopupMessage">
            Event has been added to your trip.
        </p>

        <button
            type="button"
            class="trip-popup-button"
            onclick="closeTripPopup()"
        >
            OK
        </button>

    </div>

</div>

@push('scripts')

<script>

async function addToTrip(experienceId, itemType, tripId)
{
    console.log('Adding to trip:', {
        experienceId: experienceId,
        itemType: itemType,
        tripId: tripId
    });

    try
    {
        const response = await fetch(
            "{{ route('trip.planner.add') }}",
            {
                method: "POST",

                headers:
                {
                    "Content-Type": "application/json",

                    "X-CSRF-TOKEN":
                        document.querySelector(
                            'meta[name="csrf-token"]'
                        ).getAttribute('content'),

                    "Accept": "application/json"
                },

                body: JSON.stringify({
                    trip_id: tripId,
                    experience_id: experienceId,
                    item_type: itemType
                })
            }
        );

        console.log('Response status:', response.status);

        const data = await response.json();

        console.log('Response data:', data);

        if (data.success)
        {
            showTripPopup(
                "success",
                "Event Added",
                data.message || "Event has been added to your trip."
            );

            return;
        }

        alert(
            data.message ||
            "Unable to add this experience."
        );
    }
    catch (error)
    {
        console.error("Add to trip error:", error);

        alert(
            "Something went wrong. Please check the browser console."
        );
    }
}

async function removeFromTrip(experienceId, tripId)
{
    console.log('Removing from trip:', {
        experienceId: experienceId,
        tripId: tripId
    });

    try
    {
        const response = await fetch(
            "{{ route('trip.planner.remove') }}",
            {
                method: "DELETE",

                headers:
                {
                    "Content-Type": "application/json",

                    "X-CSRF-TOKEN":
                        document.querySelector(
                            'meta[name="csrf-token"]'
                        ).getAttribute('content'),

                    "Accept": "application/json"
                },

                body: JSON.stringify({
                    trip_id: tripId,
                    experience_id: experienceId
                })
            }
        );

        console.log('Remove response status:', response.status);

        const data = await response.json();

        console.log('Remove response:', data);

        if (data.success)
        {
            showTripPopup(
                "success",
                "Event Removed",
                data.message || "Event has been removed from your trip."
            );

            return;
        }

        showTripPopup(
            "error",
            "Unable to Remove Event",
            data.message || "Something went wrong."
        );
    }
    catch(error)
    {
        console.error(
            "Remove from trip error:",
            error
        );

        showTripPopup(
            "error",
            "Something Went Wrong",
            "Unable to remove this event."
        );
    }
}


function learnMore(id)
{
    window.location.href = `/experiences/${id}`;
}



function showTripPopup(type, title, message)
{
    const popup = document.getElementById(
        "tripPopup"
    );

    const icon = document.getElementById(
        "tripPopupIcon"
    );

    const popupTitle = document.getElementById(
        "tripPopupTitle"
    );

    const popupMessage = document.getElementById(
        "tripPopupMessage"
    );


    popupTitle.textContent = title;

    popupMessage.textContent = message;


    if (type === "success")
    {
        icon.textContent = "✓";

        icon.className =
            "trip-popup-icon trip-popup-success";
    }
    else if (type === "warning")
    {
        icon.textContent = "⚠";

        icon.className =
            "trip-popup-icon trip-popup-warning";
    }
    else
    {
        icon.textContent = "✕";

        icon.className =
            "trip-popup-icon trip-popup-error";
    }


    popup.style.display = "flex";
}


function closeTripPopup()
{
    const popup = document.getElementById(
        "tripPopup"
    );

    popup.style.display = "none";


    // Refresh after successful action
    location.reload();
}

</script>

@endpush
@endsection