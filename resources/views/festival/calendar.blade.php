@extends('layouts.app')

@section('title', 'Festival Calendar')

@section('content')

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
    <div class="reminder-summary-box">

        <div>
            <h3>🔔 My Reminders</h3>

            <p>
                View the festivals and cultural experiences
                you have chosen to be reminded about.
            </p>
        </div>

        <a href="{{ route('festival.reminder') }}"
        class="reminder-summary-btn">

            View My Reminders →

        </a>

    </div>

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

<!-- Reminder Dialog -->

<div
    id="reminderDialog"
    class="reminder-dialog-overlay"
    style="display: none;"
>

    <div class="reminder-dialog">

        <button
            type="button"
            class="reminder-dialog-close"
            onclick="closeReminderDialog()">

            ×

        </button>


        <div
            id="reminderDialogIcon"
            class="reminder-dialog-icon">
        </div>


        <h3 id="reminderDialogTitle">
        </h3>


        <p id="reminderDialogMessage">
        </p>


        <button
            type="button"
            class="reminder-dialog-button"
            onclick="closeReminderDialog()">

            OK

        </button>

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


// ========================================
// CLICK EVENT
// ========================================

// ========================================
// CLICK EVENT
// ========================================

function showEvent(id, clickedDate)
{
    let event =
        events.find(
            e => e.id == id
        );

    if(!event)
    {
        return;
    }


    /*
    |--------------------------------------------------------------------------
    | Get today's date
    |--------------------------------------------------------------------------
    */

    let today =
        new Date();

    let todayString =
        today.getFullYear() +
        '-' +
        String(today.getMonth() + 1).padStart(2, '0') +
        '-' +
        String(today.getDate()).padStart(2, '0');


    /*
    |--------------------------------------------------------------------------
    | The user cannot select a date before today
    |
    | If festival starts after today,
    | use festival start date.
    |--------------------------------------------------------------------------
    */

    let minimumDate =
        event.start_date > todayString
            ? event.start_date
            : todayString;

    let defaultDate =
        clickedDate;

    if (defaultDate < minimumDate)
    {
        defaultDate =
            minimumDate;
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


        <!-- SELECT VISIT DATE -->

        <div class="reminder-date-selection">

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


        <div class="event-actions">

            <button
                class="reminder-btn"
                onclick="setReminder(${event.id})">

                🔔 Remind Me

            </button>


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
        document.getElementById(
            'selectedReminderDate'
        );


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


    // ========================================
    // VALIDATE DATE
    // ========================================

    if (!selectedDate)
    {
        showReminderDialog(
            "error",
            "Date Not Selected",
            "Please select a date for this activity."
        );

        return;
    }


    try
    {
        const response = await fetch(
            "{{ route('calendar.reminder') }}",
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

                    experience_id:
                        id,

                    // NEW
                    selected_date:
                        selectedDate
                })
            }
        );


<<<<<<< Updated upstream
        if (response.ok && data.success)
=======
        const data =
            await response.json();


        console.log(
            "Status:",
            response.status
        );

        console.log(
            "Response:",
            data
        );


        // ========================================
        // SUCCESS
        // ========================================

        if (
            response.ok &&
            data.success
        )
>>>>>>> Stashed changes
        {
            showReminderDialog(
                "success",
                "Event Added Successfully",
                data.message
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
            showReminderDialog(
                "warning",
                "Event Already Added",
                data.message
            );

            return;
        }


        // ========================================
        // OTHER ERROR
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

function showReminderDialog(
    type,
    title,
    message
)
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
        document.getElementById(
            'reminderDialog'
        );

    dialog.style.display =
        'none';

    document.body.style.overflow =
        '';
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
