@extends('layouts.app')

@section('title', 'Notifications')

@section('content')

<div class="notification-page">

    <div class="notification-container">

        <div class="notification-header">

            <div class="notification-icon">
                🔔
            </div>

            <div>
                <h1>Notifications</h1>

                <p>
                    Your upcoming activities and reminders
                </p>
            </div>

        </div>

        @if($notifications->isEmpty())

            <div class="notification-empty">

                <div class="notification-empty-icon">
                    🔔
                </div>

                <h2>No Notifications</h2>

                <p>
                    You have no upcoming activities
                    or reminders.
                </p>

                <a
                    href="{{ route('festival.calendar') }}"
                    class="button button-primary"
                >
                    Explore Festivals
                </a>

            </div>

        @else

            <div class="notification-list">

                @foreach($notifications as $notification)

                    <div class="notification-card">

                        <div class="notification-card-icon">
                            🔔
                        </div>

                        <div class="notification-card-content">

                            <h3>
                                {{ $notification->message }}
                            </h3>

                            <p>
                                Reminder for your
                                selected festival.
                            </p>

                            <small>
                                Scheduled:
                                {{ $notification->scheduled_at->format('d M Y, h:i A') }}
                            </small>

                        </div>

                    </div>

                @endforeach

            </div>

        @endif

    </div>

</div>

@endsection
