@extends('layouts.app')

@section('title', 'Festival Calendar')

@section('content')

<div class="calendar-container">

    <!-- Calendar Header -->
    <div class="calendar-header">

        <button
            onclick="changeMonth(-1)"
            class="calendar-btn">
            ←
        </button>

        <h2 id="monthYear"></h2>

        <button
            onclick="changeMonth(1)"
            class="calendar-btn">
            →
        </button>

    </div>

    <!-- Week Day -->
    <div class="calendar-weekdays">

        <div>Sun</div>
        <div>Mon</div>
        <div>Tue</div>
        <div>Wed</div>
        <div>Thu</div>
        <div>Fri</div>
        <div>Sat</div>

    </div>

    <!-- Calendar -->
    <div id="calendarDays" class="calendar-days"></div>

    <!-- Event Detail -->
    <div class="event-box">

        <h3>Festival Details</h3>

        <div id="eventList">
            Click an event to view details.
        </div>

    </div>

</div>

@endsection

@push('scripts')

<script>

let currentDate = new Date();

let events = [];

let isLoggedIn = false;

@if(auth()->check())
    isLoggedIn = true;
@endif


// ========================================
// GET DATABASE EVENTS
// ========================================

async function loadEvents()
{
    const response =
        await fetch('/calendar/events');

    events =
        await response.json();

    console.log(
        "Calendar Events:",
        events
    );

    renderCalendar();
}


// ========================================
// RENDER CALENDAR
// ========================================

function renderCalendar()
{
    let year =
        currentDate.getFullYear();

    let month =
        currentDate.getMonth();

    document.getElementById('monthYear')
    .innerHTML =
        currentDate.toLocaleString(
            'default',
            {
                month: 'long',
                year: 'numeric'
            }
        );

    let calendarDays =
        document.getElementById(
            'calendarDays'
        );

    calendarDays.innerHTML = "";

    let firstDay =
        new Date(
            year,
            month,
            1
        ).getDay();

    let totalDays =
        new Date(
            year,
            month + 1,
            0
        ).getDate();


    // Empty boxes before first day

    for(
        let i = 0;
        i < firstDay;
        i++
    )
    {
        calendarDays.innerHTML +=
        `
        <div class="empty"></div>
        `;
    }


    // Create dates

    for(
        let day = 1;
        day <= totalDays;
        day++
    )
    {
        let date =
            `${year}-${String(month + 1).padStart(2,'0')}-${String(day).padStart(2,'0')}`;

        let dayEvents =
            events.filter(event =>
                date >= event.start_date
                &&
                date <= event.end_date
            );

        calendarDays.innerHTML +=
        `
        <div class="calendar-date
        ${dayEvents.length ? 'has-event' : ''}">

            <div class="date-number">
                ${day}
            </div>

            <div class="calendar-event-list">

            ${
                dayEvents.map(event =>
                `
                <div
                    class="calendar-event"
                    onclick="showEvent(${event.id})">

                    ${event.title}

                </div>
                `
                ).join('')
            }

            </div>

        </div>
        `;
    }
}


// ========================================
// CLICK EVENT
// ========================================

function showEvent(id)
{
    let event =
        events.find(
            e => e.id == id
        );

    if(!event)
    {
        return;
    }

    document.getElementById('eventList')
    .innerHTML =
    `
    <div class="event-detail">

        <h3>
            ${event.title}
        </h3>

        <p>
            📅
            ${event.start_date}
            -
            ${event.end_date}
        </p>

        <p>
            Status: Upcoming
        </p>

        <div class="event-actions">

            <!-- REMIND ME BUTTON -->

            <button
                class="reminder-btn"
                onclick="setReminder(${event.id})">

                🔔 Remind Me

            </button>


            <!-- LEARN MORE BUTTON -->

            <button
                class="learn-more-btn"
                onclick="learnMore(${event.id})">

                Learn More

            </button>

        </div>

    </div>
    `;
}


// ========================================
// REMIND ME
// ========================================

async function setReminder(id)
{
    window.selectedEventId = id;

    // User is not logged in
    if (!isLoggedIn)
    {
        window.location.href =
            "{{ route('festival.login-required') }}";

        return;
    }

    // User is logged in
    try
    {
        const response = await fetch(
            "{{ route('calendar.reminder') }}",
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
                    experience_id: id
                })
            }
        );

        const data = await response.json();

        console.log("Status:", response.status);
        console.log("Response:", data);

        if (response.ok && data.success)
        {
            alert(data.message);
        }
        else
        {
            alert(
                "Reminder failed.\n\n" +
                "Status: " + response.status + "\n" +
                "Message: " + (data.message || "Unknown error")
            );
        }
    }
    catch(error)
    {
        console.error("Reminder error:", error);

        alert("Something went wrong.");
    }
}


// ========================================
// LEARN MORE
// ========================================

function learnMore(id)
{
    // We will implement this later
}


// ========================================
// CHANGE MONTH
// ========================================

function changeMonth(value)
{
    currentDate.setMonth(
        currentDate.getMonth() + value
    );

    renderCalendar();
}


// ========================================
// LOAD CALENDAR
// ========================================

loadEvents();

</script>

@endpush