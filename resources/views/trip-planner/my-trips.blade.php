@extends('layouts.app')

@section('title', 'My Trips')

@section('content')

<div class="my-trips-page">

    <div class="my-trips-header">

        <a
            href="{{ route('trip.planner.index') }}"
            class="my-trips-back-button"
        >
            ← Back to Trip Planner
        </a>

        <h1>📋 My Trips</h1>

        <p>
            View and manage your saved Malaysian cultural trips.
        </p>

    </div>


    @if($trips->isEmpty())

        <div class="my-trips-empty">

            <div class="my-trips-empty-icon">
                🗺️
            </div>

            <h2>
                No Trips Yet
            </h2>

            <p>
                You have not planned any trips yet.
                Start planning your Malaysian cultural experience.
            </p>

            <a
                href="{{ route('trip.planner.create') }}"
                class="trip-planner-button"
            >
                Plan New Trip →
            </a>

        </div>

    @else

        <div class="my-trips-filter">

        <label for="tripSort">
            Sort By Trip Date
        </label>

        <select
            id="tripSort"
            onchange="sortTrips(this.value)"
        >
            <option
                value="latest"
                {{ request('sort', 'latest') === 'latest' ? 'selected' : '' }}
            >
                Trip Date (Newest → Oldest)
            </option>

            <option
                value="oldest"
                {{ request('sort') === 'oldest' ? 'selected' : '' }}
            >
                Trip Date (Oldest → Newest)
            </option>
        </select>

    </div>

        <div class="my-trips-list">

            @foreach($trips as $trip)

                <div class="my-trip-card">

                    <h2>
                         {{ $trip->trip_name }}
                    </h2>

                    <div class="my-trip-date">
                        📅
                        {{ $trip->trip_date->format('d F Y') }}
                    </div>
                    <div class="my-trip-area">
                        📍
                        {{ $trip->area }}
                    </div>


                    @if($trip->items->isEmpty())

                        <p>
                            No experiences added yet.
                        </p>

                    @else

                        <ul class="my-trip-items">

                            @foreach($trip->items as $item)

                                <li class="my-trip-item">

                                    <span class="my-trip-item-title">
                                        {{ $item->experience->experiences_name }}
                                    </span>

                                    <span class="my-trip-item-type">
                                        {{ ucfirst($item->item_type) }}
                                    </span>

                                </li>

                            @endforeach

                        </ul>

                    @endif
                    <div class="my-trip-actions">
                        <a
                            href="{{ route('trip.planner.events', [
                                'trip_id' => $trip->id
                            ]) }}"
                            class="my-trip-view-button"
                        >
                            View Trip
                        </a>

                        <form
                            action="{{ route('trip.planner.destroy', $trip->id) }}"
                            method="POST"
                            class="delete-trip-form"
                        >
                            @csrf
                            @method('DELETE')

                            <button
                                type="button"
                                class="my-trip-delete-button"
                                onclick="openDeleteTripPopup(this)"
                            >
                                Delete Trip
                            </button>
                        </form>
                    </div>
                </div>

            @endforeach

        </div>

    @endif

</div>
{{-- Delete Trip Confirmation Popup --}}
<div
    id="deleteTripPopup"
    class="trip-popup-overlay"
    style="display: none;"
>
    <div class="trip-popup">

        <div class="trip-popup-icon trip-popup-warning">
            ⚠
        </div>

        <h2>
            Delete Trip?
        </h2>

        <p>
            Are you sure you want to delete this trip?
            This action cannot be undone.
        </p>

        <div class="delete-trip-popup-actions">

            <button
                type="button"
                class="delete-trip-cancel-button"
                onclick="closeDeleteTripPopup()"
            >
                Cancel
            </button>

            <button
                type="button"
                class="delete-trip-confirm-button"
                onclick="confirmDeleteTrip()"
            >
                Delete Trip
            </button>

        </div>

    </div>
</div>


<script>
function sortTrips(value)
{
    const url = new URL(window.location.href);

    url.searchParams.set('sort', value);

    window.location.href = url.toString();
}

let tripToDeleteForm = null;

function openDeleteTripPopup(button)
{
    tripToDeleteForm = button.closest('.delete-trip-form');

    document.getElementById('deleteTripPopup').style.display = 'flex';
}

function closeDeleteTripPopup()
{
    document.getElementById('deleteTripPopup').style.display = 'none';

    tripToDeleteForm = null;
}

function confirmDeleteTrip()
{
    if (tripToDeleteForm)
    {
        tripToDeleteForm.submit();
    }
}
</script>

@endsection