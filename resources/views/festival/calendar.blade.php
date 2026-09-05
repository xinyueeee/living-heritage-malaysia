@extends('layouts.app')

@section('title', 'Festival Calendar')

@section('content')

<div
    class="festival-banner"
    style="background-image: url('{{ asset('images/festivalAlert/festival-banner.jpg') }}');"
>

    <div class="festival-banner-overlay">

        <div class="festival-banner-content">

            <div class="festival-banner-label">
                FESTIVAL ALERT
            </div>

            <h1>
                Discover Malaysia's Cultural Events
            </h1>

            <p>
                Explore upcoming festivals and cultural events across Malaysia.
            </p>

        </div>

    </div>

</div>

<div class="calendar-container">

    <!-- Personalized Alert -->
    <div class="personalized-alert-box">

    <div class="personalized-alert-content">

        <h3>Personalized Alerts</h3>

        <p>
            Choose your favourite categories and we’ll send
            you an email when a new event is added or when an
            upcoming event is approaching.
        </p>

    </div>

    <a href="{{ route('alerts.create') }}"
       class="personalized-alert-btn">

        Create Personalized 
        Alert

    </a>

</div>
    <!-- My Reminders -->
    

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
    

</div>

<!-- Event Details Dialog -->

<div
    id="eventDetailDialog"
    class="event-detail-dialog-overlay"
    style="display: none;"
>

    <div class="event-detail-dialog">

        <button
            type="button"
            class="event-detail-dialog-close"
            onclick="closeEventDetailDialog()"
        >
            ×
        </button>

        <div class="event-detail-dialog-icon">
            📅
        </div>

        <div id="eventDetailContent">
        </div>

    </div>

</div>


<!-- REMINDER RESULT POPUP -->
<div
    id="reminderDialog"
    class="reminder-dialog-overlay"
    style="display: none;"
>

    <div class="reminder-dialog">

        <div
            id="reminderDialogIcon"
            class="reminder-dialog-icon"
        ></div>

        <h3 id="reminderDialogTitle"></h3>

        <p id="reminderDialogMessage"></p>

        <button
            type="button"
            class="reminder-dialog-button"
            onclick="closeReminderDialog()"
        >
            OK
        </button>

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

    let today =
        new Date();

    let todayString =
        today.getFullYear() +
        '-' +
        String(today.getMonth() + 1).padStart(2, '0') +
        '-' +
        String(today.getDate()).padStart(2, '0');

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
                    onclick="showEvent(${event.id}, '${date}')">

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

function showEvent(id, clickedDate)
{
    let event = events.find(
        e => e.id == id
    );

    if (!event)
    {
        return;
    }

    let today = new Date();

    let todayString =
        today.getFullYear() +
        '-' +
        String(today.getMonth() + 1).padStart(2, '0') +
        '-' +
        String(today.getDate()).padStart(2, '0');

    let minimumDate =
        event.start_date > todayString
            ? event.start_date
            : todayString;

    let defaultDate = clickedDate;

    if (defaultDate < minimumDate)
    {
        defaultDate = minimumDate;
    }

    document.getElementById('eventDetailContent').innerHTML = `
    
        <h2>
            ${event.title}
        </h2>

        <p class="event-detail-date">
            📅 ${event.start_date} - ${event.end_date}
        </p>

        <p class="event-detail-status">
            Status: ${
                event.end_date < todayString
                    ? 'Event Ended'
                    : 'Upcoming'
            }
        </p>

        ${
            event.end_date >= todayString
            ? `
                <div class="event-detail-visit-date">

                    <label for="selectedReminderDate">
                        Select your visit date:
                    </label>

                    <input
                        type="date"
                        id="selectedReminderDate"
                        min="${minimumDate}"
                        max="${event.end_date}"
                        value="${defaultDate}"
                    >

                    <small>
                        Choose the day you plan to attend this activity.
                    </small>

                </div>
            `
            : ''
        }

        <div class="event-detail-actions">

            ${
                clickedDate >= todayString
                ? `
                    <button
                        class="reminder-btn"
                        onclick="setReminder(${event.id})"
                    >
                        🔔 Remind Me
                    </button>
                `
                : ''
            }

            <button
                class="event-detail-learn-btn"
                onclick="learnMore(${event.id})"
            >
                Learn More
            </button>

        </div>

        ${
            clickedDate >= todayString
            ? `<p>You'll receive a reminder 3 days before your visit.</p>`
            : ''
        }

    `;

    document.getElementById(
        'eventDetailDialog'
    ).style.display = 'flex';
}

function closeEventDetailDialog()
{
    document.getElementById('eventDetailDialog').style.display = 'none';
}
// ========================================
// REMIND ME
// ========================================

async function setReminder(id)
{
    window.selectedEventId = id;

    // ========================================
    // USER NOT LOGGED IN
    // ========================================

    if (!isLoggedIn)
    {
        window.location.href =
            "{{ route('festival.login-required') }}";

        return;
    }

    // ========================================
    // GET SELECTED DATE
    // ========================================

    const selectedDateInput =
        document.getElementById('selectedReminderDate');

    if (!selectedDateInput)
    {
        showReminderDialog(
            "error",
            "Date Not Selected",
            "Please select a date for this activity."
        );

        return;
    }

    const selectedDate =
        selectedDateInput.value;

    if (!selectedDate)
    {
        showReminderDialog(
            "error",
            "Date Not Selected",
            "Please select a date for this activity."
        );

        return;
    }

    // ========================================
    // SEND REMINDER
    // ========================================

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

                    "Accept":
                        "application/json"
                },

                body: JSON.stringify(
                {
                    experience_id: id,
                    selected_date: selectedDate
                })
            }
        );


        const data =
            await response.json();

        console.log("Status:", response.status);
        console.log("Response:", data);



        // ========================================
        // SUCCESS
        // ========================================

        if (
            response.ok &&
            data.success
        )



        {
            // Close Event Details popup
            const eventDialog =
                document.getElementById('eventDetailDialog');

            if (eventDialog)
            {
                eventDialog.style.display = 'none';
            }

            // Show success popup
            showReminderDialog(
                "success",
                "Event Added Successfully",
                data.message ||
                "The event has been added to your reminders."
            );

            return;
        }

        // ========================================
        // ALREADY ADDED
        // ========================================

        if (
            data.already_added
        )
        {
            const eventDialog =
                document.getElementById('eventDetailDialog');

            if (eventDialog)
            {
                eventDialog.style.display = 'none';
            }

            showReminderDialog(
                "warning",
                "Reminder Already Set",
                data.message ||
                "A reminder has already been created for this festival date."
            );

            return;
        }

        // ========================================
        // ERROR
        // ========================================

        showReminderDialog(
            "error",
            "Unable to Add Event",
            data.message ||
            "Something went wrong. Please try again."
        );
    }
    catch(error)
    {
        console.error(
            "Reminder error:",
            error
        );

        showReminderDialog(
            "error",
            "Something Went Wrong",
            "Unable to set the reminder. Please try again."
        );
    }
}


// ========================================
// SHOW REMINDER DIALOG
// ========================================

function showReminderDialog(type,title,message)
{
    const dialog =
        document.getElementById(
            'reminderDialog'
        );

    const icon =
        document.getElementById(
            'reminderDialogIcon'
        );

    const titleElement =
        document.getElementById(
            'reminderDialogTitle'
        );

    const messageElement =
        document.getElementById(
            'reminderDialogMessage'
        );


    // Set text

    titleElement.textContent =
        title;

    messageElement.textContent =
        message;


    // Remove previous classes

    dialog.classList.remove(
        'dialog-success',
        'dialog-warning',
        'dialog-error'
    );

    icon.classList.remove(
        'dialog-icon-success',
        'dialog-icon-warning',
        'dialog-icon-error'
    );


    // Set dialog type

    if (type === 'success')
    {
        icon.innerHTML = '✓';

        dialog.classList.add(
            'dialog-success'
        );

        icon.classList.add(
            'dialog-icon-success'
        );
    }

    else if (type === 'warning')
    {
        icon.innerHTML = '!';

        dialog.classList.add(
            'dialog-warning'
        );

        icon.classList.add(
            'dialog-icon-warning'
        );
    }

    else
    {
        icon.innerHTML = '×';

        dialog.classList.add(
            'dialog-error'
        );

        icon.classList.add(
            'dialog-icon-error'
        );
    }


    // Show dialog

    dialog.style.display =
        'flex';

    document.body.style.overflow =
        'hidden';
}


// ========================================
// CLOSE REMINDER DIALOG
// ========================================

function closeReminderDialog()
{
    const dialog =
        document.getElementById('reminderDialog');

    dialog.style.display = 'none';

    document.body.style.overflow = '';

    // Make sure event popup is also closed
    const eventDialog =
        document.getElementById('eventDetailDialog');

    if (eventDialog)
    {
        eventDialog.style.display = 'none';
    }
}




// ========================================
// LEARN MORE
// ========================================
function learnMore(id)
{
    window.location.href = `/experiences/${id}`;
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
