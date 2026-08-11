@extends('layouts.app')

@section('title', 'Set a Festival Reminder')

@section('content')

<section class="auth-page">
    <div class="container auth-wrap">

        <span class="auth-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24"
                 fill="none"
                 stroke="currentColor"
                 stroke-width="2"
                 stroke-linecap="round"
                 stroke-linejoin="round">

                <rect x="5" y="11" width="14" height="10" rx="2"/>
                <path d="M8 11V7a4 4 0 0 1 8 0v4"/>

            </svg>
        </span>

        <h1>Log In to Set a Festival Reminder</h1>

        <p class="auth-lede">
            Sign in to receive reminders for upcoming festivals
            and never miss an important cultural event.
        </p>

        <div class="auth-card">

            <div class="auth-card-body" style="padding-top:32px;">

                <p style="margin-bottom:26px; color:var(--muted);">
                    Continue with Google to set festival reminders
                    and keep track of the cultural experiences you want to attend.
                </p>

                <div style="display:flex; gap:12px; justify-content:center;">

                    <a href="{{ route('login') }}"
                       class="button button-primary">
                        Continue to Login
                    </a>

                    <a href="{{ route('festival.calendar') }}"
                       class="button"
                       style="background:transparent;
                              border:1px solid var(--border);
                              color:var(--ink);">
                        Not Now
                    </a>

                </div>

            </div>

        </div>

    </div>
</section>

@endsection