@extends('layouts.app')

@section('title', 'My Reminders')

@section('content')

<div class="reminder-page">
    <div class="reminder-back-section">

        <a href="{{ route('festival.calendar') }}"
           class="reminder-back-btn">

            ← Back to Festival Calendar

        </a>

    </div>


    <div class="reminder-page-header">

        <h2>🔔 My Reminders</h2>

        <p>
            Reminder Activity Summary
        </p>

        <span>
            View the festivals you have chosen to be reminded about.
        </span>

    </div>


    <!-- Reminder Days -->

    <div class="reminder-list">

        @if($reminderDays->isEmpty())

            <!-- Empty State -->

            <div class="empty-reminder">

                <div class="empty-icon">
                    🔔
                </div>

                <h3>
                    No Reminders Yet
                </h3>

                <p>
                    Activities that you select "Remind Me"
                    for will appear here.
                </p>

                <a href="{{ route('festival.calendar') }}">
                    Explore Events
                </a>

            </div>

        @else

            @foreach($reminderDays as $date => $experiences)

                <!-- One Container Per Day -->

                <div class="reminder-day-card">

                    <!-- Date Header -->

                    <div class="reminder-day-header">

                        <div>

                            <h3>
                                📅
                                {{ \Carbon\Carbon::parse($date)->format('d F Y') }}
                            </h3>

                            <span>
                                {{ $experiences->count() }}
                                {{ $experiences->count() == 1 ? 'activity' : 'activities' }}
                            </span>

                        </div>

                    </div>


                    <!-- Activities -->

                    <div class="reminder-day-activities">

                        @foreach($experiences as $experience)

                            <div class="reminder-activity">

                                <div class="reminder-activity-icon">
                                    🎉
                                </div>

                                <div class="reminder-activity-content">

                                    <h4>
                                        {{ $experience->experiences_name }}
                                    </h4>

                                    <p>
                                        📍
                                        {{ $experience->location_name ?? 'Location not available' }}
                                    </p>

                                    <p>
                                        📅
                                        {{ \Carbon\Carbon::parse(
                                            $experience->start_date
                                        )->format('d M Y') }}

                                        @if($experience->end_date)

                                            -
                                            {{ \Carbon\Carbon::parse(
                                                $experience->end_date
                                            )->format('d M Y') }}

                                        @endif

                                    </p>

                                    <span class="reminder-status">
                                        🔔 Reminder Set
                                    </span>

                                </div>

                            </div>

                        @endforeach

                    </div>


                    <!-- AI Trip Planner -->

                    <div class="day-planner-section">

                        <div>

                            <h4>
                                ✨ Plan Your Day
                            </h4>

                            <p>
                                Let AI arrange your activities
                                and suggest suitable cultural
                                experiences for this day.
                            </p>

                        </div>

                        <button 
                            type="button" 
                            class="ai-trip-planner-btn" 
                            onclick="planThisDay('{{ $date }}')"
                        >
                            ✨ Plan This Day →
                        </button>

                    </div>

                </div>
                

            @endforeach
            <!-- Trip Planner Modal -->

<div id="tripPlannerModal" class="trip-planner-modal">

    <div class="trip-planner-modal-content">

        <button
            type="button"
            class="trip-planner-close"
            onclick="closeTripPlanner()">

            ×

        </button>


        <div class="trip-planner-modal-header">

            <div class="trip-planner-icon">
                ✨
            </div>

            <div>

                <h2>
                    Your Day Plan
                </h2>

                <p>
                    Suggested activities for
                    <strong id="tripPlannerDate"></strong>
                </p>

            </div>

        </div>


        <!-- Starting Activity -->

        <div
            id="tripPlannerStartingActivity"
            class="trip-planner-start">
        </div>
        <!-- My Trip Plan -->

        <div class="trip-planner-section">

            <h3>
                📅 My Trip Plan
            </h3>

            <p class="trip-planner-description">
                Your selected festivals and added experiences
                for this day.
            </p>

            <div
                id="tripPlannerItinerary"
                class="trip-planner-itinerary"
            >
            </div>

        </div>


        <!-- Nearby Suggestions -->

        <div class="trip-planner-section">

            <h3>
                🎨 Recommended Cultural Experiences
            </h3>

            <p class="trip-planner-description">
                Cultural experiences that are conveniently
                located near your planned festivals.
            </p>

            <div
                id="tripPlannerSuggestions"
                class="trip-planner-suggestions">
            </div>

        </div>


        <div class="trip-planner-modal-footer">

            <button
                type="button"
                class="trip-planner-close-btn"
                onclick="closeTripPlanner()">

                Close

            </button>

        </div>

    </div>

</div>

        @endif

    </div>

</div>

@endsection


@push('scripts')

<script>

    let tripPlanActivities = [];
    let selectedFestivalActivities = [];
    let recommendedCulturalExperiences = [];

async function planThisDay(date)
{
    console.log("Plan button clicked:", date);
    try
    {
        const response = await fetch(
            "{{ route('trip.planner.plan') }}",
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
                    date: date
                    
                })
            }
        );

        const data = await response.json();
        tripPlanActivities = [];

        data.selected_festivals.forEach(
        function(festival)
        {
            tripPlanActivities.push({
                id: festival.id,

                name: festival.name,

                location: festival.location,

                duration: festival.duration,

                latitude: festival.latitude,

                longitude: festival.longitude,

                type: 'festival'
            });
        }
    );


    /*
    |--------------------------------------------------------------------------
    | Add previously saved cultural experiences
    |--------------------------------------------------------------------------
    */

    if (data.saved_trip_items)
    {
        data.saved_trip_items.forEach(
            function(item)
            {
                tripPlanActivities.push({
                    id: item.id,

                    name: item.name,

                    location: item.location,

                    duration: item.duration,

                    latitude: item.latitude,

                    longitude: item.longitude,

                    type: 'cultural'
                });
            }
        );
    }

        console.log(
            "Trip Planner Response:",
            data
        );

        if (data.success)
{
    /*
    |--------------------------------------------------------------------------
    | Selected Festivals
    |--------------------------------------------------------------------------
    */

    let selectedFestivalsHtml = "";

    selectedFestivalActivities =
        data.selected_festivals || [];

    recommendedCulturalExperiences =
        data.recommended_cultural_experiences || [];

    data.selected_festivals.forEach(function(festival)
    {
        selectedFestivalsHtml += `
            <div class="trip-selected-festival">

                <h4>
                    🎉 ${festival.name}
                </h4>

                <p>
                    📍 ${festival.location}
                </p>

            </div>
        `;
    });


    /*
    |--------------------------------------------------------------------------
    | Recommended Cultural Experiences
    |--------------------------------------------------------------------------
    */

    let culturalExperiencesHtml = "";


    if (
        data.recommended_cultural_experiences.length === 0
    )
    {
        culturalExperiencesHtml = `
            <div class="trip-no-suggestion">

                <p>
                    No suitable cultural experiences
                    were found near your planned festivals.
                </p>

            </div>
        `;
    }
    else
    {
        data.recommended_cultural_experiences
            .forEach(function(experience)
            {
                let distancesHtml = "";

                experience.distances.forEach(
                    function(distance)
                    {
                        distancesHtml += `
                            <span class="trip-distance">

                                📏
                                ${distance.distance_km} km
                                from ${distance.festival_name}

                            </span>
                        `;
                    }
                );


                culturalExperiencesHtml += `
                <div
                    class="trip-cultural-suggestion"
                    id="recommendation-${experience.id}"
                >

                    <div class="trip-cultural-info">

                        <h4>
                            🎨 ${experience.name}
                        </h4>

                        <p>
                            📍 ${experience.location}
                        </p>

                        <div class="trip-distance-list">

                            ${distancesHtml}

                        </div>

                    </div>

                    <button
                        type="button"
                        class="trip-add-btn"
                        onclick="addToTrip(${experience.id})"
                    >
                        + Add to Trip
                    </button>

                </div>
            `;
            });
    }


    /*
    |--------------------------------------------------------------------------
    | Put data into modal
    |--------------------------------------------------------------------------
    */

    document.getElementById(
        'tripPlannerDate'
    ).textContent = data.date;


    document.getElementById(
        'tripPlannerStartingActivity'
    ).innerHTML = `

        <h4>
            📅 Your Selected Festivals
        </h4>

        <div class="trip-selected-festivals">

            ${selectedFestivalsHtml}

        </div>

    `;


    document.getElementById(
        'tripPlannerSuggestions'
    ).innerHTML = `

        ${culturalExperiencesHtml}

    `;


    /*
    |--------------------------------------------------------------------------
    | Show modal
    |--------------------------------------------------------------------------
    */
    renderTripPlan();
    document.getElementById(
        'tripPlannerModal'
    ).style.display = 'flex';
}
        else
        {
            alert(
                data.message ||
                "Unable to create trip plan."
            );
        }
    }
    catch(error)
    {
        console.error(
            "Trip Planner Error:",
            error
        );

        alert(
            "Something went wrong."
        );
        
    }
}

function closeTripPlanner()
{
    document.getElementById('tripPlannerModal').style.display = 'none';
}

async function addToTrip(experienceId)
{
    console.log("ADD TO TRIP CLICKED:", experienceId);
    /*
    |--------------------------------------------------------------------------
    | Find the selected Cultural Experience
    |--------------------------------------------------------------------------
    */

    const experience =
        recommendedCulturalExperiences.find(
            function(item)
            {
                return Number(item.id) === Number(experienceId);
            }
        );


    if (!experience)
    {
        console.error(
            "Cultural Experience not found:",
            experienceId
        );

        return;
    }


    /*
    |--------------------------------------------------------------------------
    | Prevent duplicate
    |--------------------------------------------------------------------------
    */

    const alreadyAdded =
    tripPlanActivities.some(
        function(activity)
        {
            return Number(activity.id) === Number(experienceId);
        }
    );


    console.log(
        "Already Added:",
        alreadyAdded
    );


    if (alreadyAdded)
    {
        console.log(
            "Experience is already in trip."
        );

        return;
    }


    /*
    |--------------------------------------------------------------------------
    | SAVE TO DATABASE
    |--------------------------------------------------------------------------
    */

    try
    {
        const response = await fetch(
            "{{ route('trip.planner.add') }}",
            {
                method: "POST",

                headers:
                {
                    "Content-Type":
                        "application/json",

                    "X-CSRF-TOKEN":
                        document.querySelector(
                            'meta[name="csrf-token"]'
                        ).getAttribute('content'),

                    "Accept":
                        "application/json"
                },

                body: JSON.stringify({
                    date:
                        document.getElementById(
                            'tripPlannerDate'
                        ).textContent,

                    experience_id:
                        experienceId
                })
            }
        );


        const data =
            await response.json();


        console.log(
            "Add Trip Response:",
            data
        );


        if (!data.success)
        {
            alert(
                data.message ||
                "Unable to add experience to trip."
            );

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | Add to temporary JavaScript trip plan
        |--------------------------------------------------------------------------
        */

        tripPlanActivities.push({
            id: experience.id,

            name: experience.name,

            location: experience.location,

            duration: experience.duration,

            latitude: experience.latitude,

            longitude: experience.longitude,

            distances: experience.distances,
            type: 'cultural'
        });


        /*
        |--------------------------------------------------------------------------
        | Change recommendation button
        |--------------------------------------------------------------------------
        */

        const recommendation =
            document.getElementById(
                'recommendation-' + experienceId
            );


        if (recommendation)
        {
            const button =
                recommendation.querySelector(
                    '.trip-add-btn'
                );


            if (button)
            {
                button.textContent =
                    '✓ Added to Trip';

                button.disabled = true;

                button.classList.add(
                    'trip-add-btn-added'
                );
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Rebuild trip plan
        |--------------------------------------------------------------------------
        */

        renderTripPlan();


        alert(
            "Experience added to your trip!"
        );
    }
    catch(error)
    {
        console.error(
            "Add Trip Error:",
            error
        );

        alert(
            "Something went wrong while adding the experience."
        );
    }
}

function renderTripPlan()
{
    const itinerary =
        document.getElementById(
            'tripPlannerItinerary'
        );

    if (!itinerary)
    {
        return;
    }


    let activities =
    buildSmartTripOrder();

    /*
    |--------------------------------------------------------------------------
    | Empty state
    |--------------------------------------------------------------------------
    */

    if (activities.length === 0)
    {
        itinerary.innerHTML = `
            <div class="trip-empty-itinerary">
                No activities in your trip plan yet.
            </div>
        `;

        return;
    }


    /*
    |--------------------------------------------------------------------------
    | Generate schedule
    |--------------------------------------------------------------------------
    */
    let html = "";
    let currentMinutes = 9 * 60;


    activities.forEach(
        function(activity, index)
        {
            const duration =
                getEstimatedDuration(
                    activity,
                    activity.type
                );


            /*
            |--------------------------------------------------------------
            | Activity
            |--------------------------------------------------------------
            */

            html += createScheduledActivity(
                activity,
                duration,
                currentMinutes
            );


            currentMinutes += duration;


            /*
            |--------------------------------------------------------------
            | Travel / buffer between activities
            |--------------------------------------------------------------
            */

            if (index < activities.length - 1)
            {
                const distanceKm =
                    getTravelDistance(
                        activities[index],
                        activities[index + 1]
                    );

                const travelMinutes =
                    estimateTravelTime(
                        distanceKm
                    );

                html += `
                    <div class="trip-travel-block">

                        🚗

                        <span>
                            Estimated travel:
                            ${distanceKm.toFixed(1)} km
                            ·
                            ${travelMinutes} min
                        </span>

                    </div>
                `;
                currentMinutes += travelMinutes;
            }
        }
    );


    itinerary.innerHTML = html;
}

function createScheduledActivity(
    activity,
    duration,
    startMinutes
)
{
    const icon =
        activity.type === 'festival'
            ? '🎉'
            : '🎨';


    const status =
        activity.type === 'festival'
            ? '🔒 Selected Festival'
            : '✨ Added to Trip';


    let removeButton = "";


    if (activity.type === 'cultural')
    {
        removeButton = `
            <button
                type="button"
                class="trip-remove-btn"
                onclick="removeFromTrip(${activity.id})"
            >
                Remove
            </button>
        `;
    }


    const startTime =
        formatTime(startMinutes);


    const endTime =
        formatTime(
            startMinutes + duration
        );


    return `
        <div
            class="trip-itinerary-item"
            id="trip-item-${activity.id}"
        >

            <div class="trip-itinerary-time">

                ${startTime}

                <span>
                    -
                </span>

                ${endTime}

            </div>


            <div class="trip-itinerary-content">

                <h4>
                    ${icon} ${activity.name}
                </h4>

                <p>
                    📍 ${activity.location}
                </p>

                <p>
                    ⏱️ Estimated visit:
                    ${formatDuration(duration)}
                </p>

                <span class="trip-itinerary-status">
                    ${status}
                </span>

            </div>

            ${removeButton}

        </div>
    `;
}

function formatTime(totalMinutes)
{
    let hours =
        Math.floor(totalMinutes / 60);

    let minutes =
        totalMinutes % 60;


    const period =
        hours >= 12
            ? 'PM'
            : 'AM';


    if (hours > 12)
    {
        hours -= 12;
    }


    if (hours === 0)
    {
        hours = 12;
    }


    return (
        String(hours).padStart(2, '0') +
        ':' +
        String(minutes).padStart(2, '0') +
        ' ' +
        period
    );
}

function buildSmartTripOrder()
{
    let orderedActivities = [];


    /*
    |--------------------------------------------------------------------------
    | Group added cultural experiences by their nearest festival
    |--------------------------------------------------------------------------
    */

    let culturalByFestival = {};


    selectedFestivalActivities.forEach(
        function(festival)
        {
            culturalByFestival[festival.id] = [];
        }
    );


    tripPlanActivities.forEach(
        function(cultural)
        {
            let nearestFestivalId = null;
            let nearestDistance = Infinity;


            /*
            |--------------------------------------------------------------------------
            | Find the festival closest to this cultural experience
            |--------------------------------------------------------------------------
            */

            selectedFestivalActivities.forEach(
                function(festival)
                {
                    const distance =
                        getDistanceToFestival(
                            cultural,
                            festival.id
                        );


                    if (
                        distance <
                        nearestDistance
                    )
                    {
                        nearestDistance =
                            distance;

                        nearestFestivalId =
                            festival.id;
                    }
                }
            );


            /*
            |--------------------------------------------------------------------------
            | Assign cultural experience to nearest festival
            |--------------------------------------------------------------------------
            */

            if (
                nearestFestivalId !== null
            )
            {
                culturalByFestival[
                    nearestFestivalId
                ].push({
                    ...cultural,

                    assignedFestivalId:
                        nearestFestivalId,

                    assignedDistance:
                        nearestDistance
                });
            }
        }
    );


    /*
    |--------------------------------------------------------------------------
    | Sort cultural experiences inside each festival group
    |--------------------------------------------------------------------------
    */

    selectedFestivalActivities.forEach(
        function(festival)
        {
            culturalByFestival[
                festival.id
            ].sort(
                function(a, b)
                {
                    return (
                        a.assignedDistance -
                        b.assignedDistance
                    );
                }
            );
        }
    );


    /*
    |--------------------------------------------------------------------------
    | Build final itinerary
    |--------------------------------------------------------------------------
    */

    selectedFestivalActivities.forEach(
        function(festival)
        {
            /*
            |--------------------------------------------------------------
            | Add Festival
            |--------------------------------------------------------------
            */

            orderedActivities.push({
                ...festival,

                type: 'festival'
            });


            /*
            |--------------------------------------------------------------
            | Add Cultural Experiences near this Festival
            |--------------------------------------------------------------
            */

            culturalByFestival[
                festival.id
            ].forEach(
                function(cultural)
                {
                    orderedActivities.push({
                        ...cultural,

                        type: 'cultural'
                    });
                }
            );
        }
    );


    return orderedActivities;
}

function getDistanceToFestival(
    cultural,
    festivalId
)
{
    if (!cultural.distances)
    {
        return Infinity;
    }


    const match =
        cultural.distances.find(
            function(distance)
            {
                return (
                    distance.festival_id ===
                    festivalId
                );
            }
        );


    if (!match)
    {
        return Infinity;
    }


    return Number(
        match.distance_km
    );
}

function estimateTravelTime(distanceKm)
{
    const averageSpeedKmPerHour = 30;


    /*
    |--------------------------------------------------------------------------
    | Convert distance into hours
    |--------------------------------------------------------------------------
    */

    const hours =
        distanceKm /
        averageSpeedKmPerHour;


    /*Convert hours into minutes*/

    let minutes =
        Math.round(hours * 60);


    /*
    |--------------------------------------------------------------------------
    | Add safety buffer for traffic / parking / walking
    |--------------------------------------------------------------------------
    */

    minutes += 15;


    /*
    |--------------------------------------------------------------------------
    | Minimum travel time
    |--------------------------------------------------------------------------
    */

    if (minutes < 15)
    {
        minutes = 15;
    }


    return minutes;
}

function getTravelDistance(
    currentActivity,
    nextActivity
)
{
    /*
    |--------------------------------------------------------------------------
    | Cultural → Festival
    |--------------------------------------------------------------------------
    */

    if (
        currentActivity.type === 'cultural' &&
        nextActivity.type === 'festival'
    )
    {
        return getDistanceToFestival(
            currentActivity,
            nextActivity.id
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Festival → Cultural
    |--------------------------------------------------------------------------
    */

    if (
        currentActivity.type === 'festival' &&
        nextActivity.type === 'cultural'
    )
    {
        return getDistanceToFestival(
            nextActivity,
            currentActivity.id
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Festival → Festival
    |--------------------------------------------------------------------------
    */

    if (
        currentActivity.type === 'festival' &&
        nextActivity.type === 'festival'
    )
    {
        return getDistanceBetweenActivities(
            currentActivity,
            nextActivity
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Cultural → Cultural
    |--------------------------------------------------------------------------
    */

    if (
        currentActivity.type === 'cultural' &&
        nextActivity.type === 'cultural'
    )
    {
        return getDistanceBetweenActivities(
            currentActivity,
            nextActivity
        );
    }


    return 0;
}

function getEstimatedDuration(activity, type)
{
    /*
    |--------------------------------------------------------------------------
    | Use database duration if available
    |--------------------------------------------------------------------------
    */

    if (
        activity.duration !== null &&
        activity.duration !== undefined &&
        activity.duration !== ''
    )
    {
        return Number(activity.duration);
    }


    /*
    |--------------------------------------------------------------------------
    | Expert-system fallback rules
    |--------------------------------------------------------------------------
    */

    if (type === 'festival')
    {
        return 120;
    }


    if (type === 'cultural')
    {
        return 60;
    }


    return 60;
}

function createItineraryItem(
    activity,
    type,
    duration,
    removable = false
)
{
    const icon =
        type === 'festival'
            ? '🎉'
            : '🎨';

    const status =
        type === 'festival'
            ? '🔒 Selected Festival'
            : '✨ Added to Trip';


    let removeButton = "";

    if (removable)
    {
        removeButton = `
            <button
                type="button"
                class="trip-remove-btn"
                onclick="removeFromTrip(${activity.id})"
            >
                Remove
            </button>
        `;
    }


    return `
        <div
            class="trip-itinerary-item"
            id="trip-item-${activity.id}"
        >

            <div class="trip-itinerary-content">

                <h4>
                    ${icon} ${activity.name}
                </h4>

                <p>
                    📍 ${activity.location}
                </p>

                <p>
                    ⏱️ Estimated visit:
                    ${formatDuration(duration)}
                </p>

                <span class="trip-itinerary-status">
                    ${status}
                </span>

            </div>

            ${removeButton}

        </div>
    `;
}

function formatDuration(minutes)
{
    const hours =
        Math.floor(minutes / 60);

    const remainingMinutes =
        minutes % 60;


    if (hours > 0 && remainingMinutes > 0)
    {
        return (
            hours +
            ' hr ' +
            remainingMinutes +
            ' min'
        );
    }


    if (hours > 0)
    {
        return (
            hours +
            (hours === 1
                ? ' hr'
                : ' hrs')
        );
    }


    return minutes + ' min';
}

function generateTripSchedule()
{
    let schedule = [];

    let currentMinutes = 9 * 60; // 9:00 AM

    /*
    |--------------------------------------------------------------------------
    | Selected Festivals
    |--------------------------------------------------------------------------
    */

    selectedFestivalActivities.forEach(
        function(festival)
        {
            schedule.push({
                type: 'festival',
                id: festival.id,
                name: festival.name,
                location: festival.location,
                startMinutes: currentMinutes,
                duration: 90
            });

            currentMinutes += 90;

            // Estimated buffer/travel time
            currentMinutes += 30;
        }
    );


    /*
    |--------------------------------------------------------------------------
    | Added Cultural Experiences
    |--------------------------------------------------------------------------
    */

    tripPlanActivities.forEach(
        function(activity)
        {
            schedule.push({
                type: 'cultural',
                id: activity.id,
                name: activity.name,
                location: activity.location,
                startMinutes: currentMinutes,
                duration: 60
            });

            currentMinutes += 60;

            // Estimated buffer/travel time
            currentMinutes += 30;
        }
    );


    return schedule;
}

function removeFromTrip(experienceId)
{
    /*
    |--------------------------------------------------------------------------
    | Remove from temporary trip plan
    |--------------------------------------------------------------------------
    */

    tripPlanActivities =
        tripPlanActivities.filter(
            function(activity)
            {
                return Number(activity.id) === Number(experienceId);
            }
        );


    /*
    |--------------------------------------------------------------------------
    | Reset recommendation button
    |--------------------------------------------------------------------------
    */

    const recommendation =
        document.getElementById(
            'recommendation-' + experienceId
        );


    if (recommendation)
    {
        const button =
            recommendation.querySelector(
                '.trip-add-btn'
            );


        if (button)
        {
            button.textContent =
                '+ Add to Trip';

            button.disabled = false;

            button.classList.remove(
                'trip-add-btn-added'
            );
        }
    }

    renderTripPlan();
}

function getEstimatedDuration(activity, type)
{
    /*
    |--------------------------------------------------------------------------
    | Use database duration when available
    |--------------------------------------------------------------------------
    */

    if (
        activity.duration !== null &&
        activity.duration !== undefined &&
        activity.duration !== ''
    )
    {
        return Number(activity.duration);
    }


    /*
    |--------------------------------------------------------------------------
    | Fallback expert-system rules
    |--------------------------------------------------------------------------
    */

    if (type === 'festival')
    {
        return 120;
    }


    if (type === 'cultural')
    {
        return 60;
    }

    return 60;
}

function getDistanceBetweenActivities(
    activityA,
    activityB
)
{
    if (
        !activityA.latitude ||
        !activityA.longitude ||
        !activityB.latitude ||
        !activityB.longitude
    )
    {
        return 0;
    }


    const lat1 =
        degToRad(
            Number(activityA.latitude)
        );

    const lon1 =
        degToRad(
            Number(activityA.longitude)
        );

    const lat2 =
        degToRad(
            Number(activityB.latitude)
        );

    const lon2 =
        degToRad(
            Number(activityB.longitude)
        );


    const latDifference =
        lat2 - lat1;

    const lonDifference =
        lon2 - lon1;


    const a =
        Math.sin(latDifference / 2) ** 2
        +
        Math.cos(lat1)
        *
        Math.cos(lat2)
        *
        Math.sin(lonDifference / 2) ** 2;


    const c =
        2 *
        Math.asin(
            Math.sqrt(a)
        );


    return 6371 * c;
}

function degToRad(degrees)
{
    return degrees * Math.PI / 180;
}

</script>

@endpush