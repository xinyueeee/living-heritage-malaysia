@extends('layouts.app')
@section('title', 'Festival Calendar')
@section('content')

<div class="calendar-container">
    <!-- Calendar Header -->
    <div class="calendar-header">
        <button onclick="changeMonth(-1)" class="calendar-btn"> ← </button>
        <h2 id="monthYear"></h2>
        <button onclick="changeMonth(1)" class="calendar-btn"> →
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
        <div id="eventList"> Click an event to view details. </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let currentDate = new Date();
let events = [];

// Get database events

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
            month:'long',
            year:'numeric'
        }
    );

    let calendarDays =
    document.getElementById(
        'calendarDays'
    );

    calendarDays.innerHTML="";

    let firstDay =
    new Date(
        year,
        month,
        1
    ).getDay();


    let totalDays =
    new Date(
        year,
        month+1,
        0
    ).getDate();

    // empty box before first day

    for(let i=0;i<firstDay;i++)
    {
        calendarDays.innerHTML +=
        `
        <div class="empty"></div>
        `;
    }
    // create dates

    for(let day=1; day<=totalDays; day++)
    {
        let date =
        `${year}-${String(month+1).padStart(2,'0')}-${String(day).padStart(2,'0')}`;

        let dayEvents =
        events.filter(event =>

            date >= event.start_date
            &&
            date <= event.end_date

        );

        calendarDays.innerHTML +=

        `

        <div class="calendar-date
        ${dayEvents.length ? 'has-event':''}">

            <div class="date-number">
                ${day}
            </div>

            <div class="calendar-event-list">
            ${
                dayEvents.map(event =>
                `
                <div class="calendar-event"
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

// Click event

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
        <h3> ${event.title}</h3>

        <p>
        📅 
        ${event.start_date}
        -
        ${event.end_date}
        </p>

        <p> Status: Upcoming </p>

        <button onclick="learnMore(${event.id})"> Learn More </button>

    </div>
    `;
}


function learnMore(id)
{
    
}


function changeMonth(value)
{
    currentDate.setMonth(
        currentDate.getMonth()+value
    );
    renderCalendar();
}
loadEvents();

</script>


@endpush