@extends('layouts.app')

@section('title', 'Festival Calendar')

@section('content')

<section class="section">
    <div class="container">

        <div class="section-heading">
            <div>
                <h1>Festival Calendar</h1>
                <p class="section-description">
                    Explore upcoming cultural festivals and events in Malaysia.
                </p>
            </div>
        </div>


        <div class="calendar-container">

            <!-- Calendar Header -->
            <div class="calendar-header">
                <button onclick="changeMonth(-1)" class="calendar-btn">
                    ←
                </button>

                <h2 id="monthYear"></h2>

                <button onclick="changeMonth(1)" class="calendar-btn">
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


            <!-- Calendar Dates -->
            <div id="calendarDays" class="calendar-days"></div>


            <!-- Event List -->
            <div class="event-box">

                <h3>Events This Month</h3>

                <div id="eventList">
                    Select a date to view events.
                </div>

            </div>

        </div>

    </div>
</section>


<script>

let currentDate = new Date();


const festivals = {

    "2026-08-31": {
        name: "Hari Merdeka Celebration",
        location: "Kuala Lumpur",
        description: "National Day cultural celebration."
    },

    "2026-09-16": {
        name: "Malaysia Day Festival",
        location: "Putrajaya",
        description: "Celebrate Malaysian heritage and unity."
    },

    "2026-10-20": {
        name: "Deepavali Festival",
        location: "Brickfields",
        description: "Indian cultural celebration with lights."
    },

    "2026-12-25": {
        name: "Christmas Celebration",
        location: "Kuala Lumpur",
        description: "Festive cultural event."
    }

};



function loadCalendar(){

    let year = currentDate.getFullYear();
    let month = currentDate.getMonth();


    let firstDay = new Date(year, month, 1);
    let lastDay = new Date(year, month + 1, 0);


    let daysContainer = document.getElementById("calendarDays");

    daysContainer.innerHTML = "";


    document.getElementById("monthYear").innerHTML =
        firstDay.toLocaleString('default',
        {
            month:'long',
            year:'numeric'
        });



    // empty spaces before first day

    for(let i=0;i<firstDay.getDay();i++){

        let empty = document.createElement("div");

        empty.className="calendar-date empty";

        daysContainer.appendChild(empty);

    }



    // dates

    for(let day=1; day<=lastDay.getDate(); day++){

        let dateBox=document.createElement("div");

        dateBox.className="calendar-date";

        dateBox.innerHTML=day;



        let dateKey =
        `${year}-${String(month+1).padStart(2,'0')}-${String(day).padStart(2,'0')}`;



        if(festivals[dateKey]){

            dateBox.classList.add("has-event");

            dateBox.onclick=function(){

                showEvent(dateKey);

            }

        }


        daysContainer.appendChild(dateBox);

    }

}



function showEvent(date){

    let event = festivals[date];


    document.getElementById("eventList").innerHTML = `

        <div class="festival-card">

            <h4>${event.name}</h4>

            <p>📍 ${event.location}</p>

            <p>${event.description}</p>

        </div>

    `;

}



function changeMonth(value){

    currentDate.setMonth(
        currentDate.getMonth()+value
    );

    loadCalendar();

}



loadCalendar();


</script>


@endsection