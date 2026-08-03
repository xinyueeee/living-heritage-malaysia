@extends('layouts.app')

@section('title', 'Log In - Living Heritage Malaysia')

@section('content')
    <section class="auth-page">
        <div class="auth-decor" aria-hidden="true">
            <svg class="auth-decor-buildings" viewBox="0 0 460 260" xmlns="http://www.w3.org/2000/svg">
                <style>
                    .bld-line { fill: none; stroke: var(--primary); stroke-width: 3; stroke-linecap: round; stroke-linejoin: round; }
                    .bld-accent { fill: none; stroke: var(--gold); stroke-width: 3; stroke-linecap: round; stroke-linejoin: round; }
                </style>
                <line class="bld-line" x1="0" y1="230" x2="460" y2="230" />
                <path class="bld-line" d="M30 230V150l30-20 30 20v80" />
                <path class="bld-line" d="M100 230V110h20V80l10-15 10 15v30h20v120" />
                <path class="bld-accent" d="M190 230V60l10-20 10 20v170" />
                <path class="bld-line" d="M230 230V95h60v135" />
                <path class="bld-accent" d="M240 95v-25l10-18 10 18v25" />
                <path class="bld-accent" d="M270 95v-25l10-18 10 18v25" />
                <path class="bld-line" d="M300 230V130l25-25 25 25v100" />
                <circle class="bld-accent" cx="70" cy="40" r="14" />
                <circle class="bld-accent" cx="380" cy="60" r="10" />
            </svg>
            <svg class="auth-decor-floral-tr" viewBox="0 0 240 240" xmlns="http://www.w3.org/2000/svg">
                <g fill="none" stroke="var(--gold)" stroke-width="2">
                    <circle cx="60" cy="60" r="18" />
                    <circle cx="95" cy="45" r="14" />
                    <circle cx="40" cy="95" r="12" />
                    <path d="M60 60C90 30 140 20 190 40" />
                </g>
            </svg>
            <svg class="auth-decor-floral-bl" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
                <g fill="none" stroke="var(--primary)" stroke-width="2">
                    <circle cx="40" cy="150" r="16" />
                    <circle cx="70" cy="170" r="10" />
                    <path d="M40 150C20 110 30 60 60 20" />
                </g>
            </svg>
        </div>

        <div class="container auth-wrap">
            <span class="auth-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
            </span>
            <h1>Welcome Back!</h1>
            <p class="auth-lede">Log in to continue your journey in exploring Malaysia's living heritage.</p>

            <div class="auth-card">
                <div class="auth-illustration">
                    <svg viewBox="0 0 400 200" xmlns="http://www.w3.org/2000/svg">
                        <style>
                            .ill-line { fill: none; stroke: var(--primary); stroke-width: 3; stroke-linecap: round; stroke-linejoin: round; }
                            .ill-accent { fill: none; stroke: var(--gold); stroke-width: 3; stroke-linecap: round; stroke-linejoin: round; }
                            .ill-cloud { fill: none; stroke: var(--border); stroke-width: 3; stroke-linecap: round; }
                        </style>
                        <line class="ill-line" x1="10" y1="170" x2="390" y2="170" />

                        <path class="ill-line" d="M40 170v-55h18v-15h18v15h18v55" />
                        <path class="ill-accent" d="M58 100V78l9-12 9 12v22" />

                        <path class="ill-line" d="M110 170V85l35-20 35 20v85" />
                        <path class="ill-line" d="M135 85h20" />
                        <path class="ill-line" d="M135 105h20" />
                        <path class="ill-line" d="M135 125h20" />

                        <path class="ill-accent" d="M210 170V60" />
                        <path class="ill-accent" d="M198 60a12 12 0 0 1 24 0z" />
                        <path class="ill-accent" d="M204 170V140h12v30" />

                        <path class="ill-line" d="M250 170V70l14-18 14 18v100" />
                        <path class="ill-line" d="M300 170V70l14-18 14 18v100" />
                        <path class="ill-line" d="M278 90h22" />

                        <path class="ill-line" d="M340 170V115l16-15 16 15v55" />

                        <g class="ill-cloud">
                            <path d="M40 40h30" />
                            <path d="M30 48h44" />
                        </g>
                        <g class="ill-cloud">
                            <path d="M300 30h30" />
                            <path d="M292 38h44" />
                        </g>

                        <g fill="none" stroke="var(--gold)" stroke-width="2">
                            <circle cx="90" cy="150" r="6" />
                            <circle cx="330" cy="155" r="6" />
                        </g>
                    </svg>
                </div>

                <div class="auth-card-body">
                    <h2>Log in with Google</h2>
                    <p>Use your Google account to access your profile and explore amazing cultural experiences.</p>

                    <button type="button" class="auth-google-btn" data-google-login>
                        <svg viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path fill="#FFC107" d="M43.611,20.083H42V20H24v8h11.303c-1.649,4.657-6.08,8-11.303,8c-6.627,0-12-5.373-12-12c0-6.627,5.373-12,12-12c3.059,0,5.842,1.154,7.961,3.039l5.657-5.657C34.046,6.053,29.268,4,24,4C12.955,4,4,12.955,4,24c0,11.045,8.955,20,20,20c11.045,0,20-8.955,20-20C44,22.659,43.862,21.35,43.611,20.083z"/>
                            <path fill="#FF3D00" d="M6.306,14.691l6.571,4.819C14.655,15.108,18.961,12,24,12c3.059,0,5.842,1.154,7.961,3.039l5.657-5.657C34.046,6.053,29.268,4,24,4C16.318,4,9.656,8.337,6.306,14.691z"/>
                            <path fill="#4CAF50" d="M24,44c5.166,0,9.86-1.977,13.409-5.192l-6.19-5.238C29.211,35.091,26.715,36,24,36c-5.202,0-9.619-3.317-11.283-7.946l-6.522,5.025C9.505,39.556,16.227,44,24,44z"/>
                            <path fill="#1976D2" d="M43.611,20.083H42V20H24v8h11.303c-0.792,2.237-2.231,4.166-4.087,5.571c0.001-0.001,0.002-0.001,0.003-0.002l6.19,5.238C36.971,39.205,44,34,44,24C44,22.659,43.862,21.35,43.611,20.083z"/>
                        </svg>
                        <span data-label>Continue with Google</span>
                    </button>

                    <p class="auth-error" data-auth-error hidden></p>

                    <p class="auth-secure">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
                        Secure, simple and fast<br>We never store your Google password.
                    </p>
                </div>
            </div>
        </div>
    </section>

    @push('scripts')
        @vite(['resources/js/pages/auth-login.js'])
    @endpush
@endsection
