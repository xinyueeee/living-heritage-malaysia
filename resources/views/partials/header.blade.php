<header class="site-header">
    <nav class="navbar container" aria-label="Main navigation">
        <a class="site-logo" href="{{ route('home') }}">
            <img src="{{ asset('images/home/logo-transparent.png') }}" alt="Living Heritage Malaysia">
            <span class="site-logo-title" aria-hidden="true">
                <span class="site-logo-title-main">Living Heritage</span>
                <span class="site-logo-title-secondary">Malaysia</span>
            </span>
        </a>

        <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="main-menu" aria-label="Open navigation menu">
            <span></span><span></span><span></span>
        </button>

        <ul class="nav-menu" id="main-menu">
            <li><a @class(['active' => request()->routeIs('home')]) href="{{ route('home') }}">Home</a></li>
            <li><a @class(['active' => request()->routeIs('experiences.*')]) href="{{ route('experiences.index') }}">Discover</a></li>
            <li><a @class(['active' => request()->routeIs('community.*')])href="{{ route('community.index') }}">Community</a></li>
            <li> <a @class(['active' => request()->routeIs('festival.*')]) href="{{ route('festival.calendar') }}">Festival Alert</a></li>
            <li><a @class(['active' => request()->routeIs('engagement.*')])href="{{ route('engagement.index') }}">Engagement &amp; Rewards</a></li>
            <li><a @class(['active' => request()->routeIs('profile')]) href="{{ route('profile') }}">Profile</a></li>


            <li class="nav-auth">
                @auth
<<<<<<< Updated upstream
                    
=======
                    @php($unreadCount = \Illuminate\Support\Facades\DB::table('notification')->where('user_id', auth()->id())->where('is_read', false)->count())
                    <a class="nav-bell" href="#" aria-disabled="true" aria-label="Notifications">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                        @if ($unreadCount > 0)
                            <span class="nav-bell-badge">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
                        @endif
                    </a>
>>>>>>> Stashed changes

                @php
                    $unreadCount = \Illuminate\Support\Facades\DB::table('notification')
                        ->where('user_id', auth()->id())
                        ->where('is_read', false)
                        ->count();
                @endphp

                <a class="nav-bell"
                href="{{ route('notifications.index') }}"
                aria-label="Notifications">

                    <svg viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        aria-hidden="true">

                        <path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"/>

                    </svg>

                    @if ($unreadCount > 0)
                        <span class="nav-bell-badge">
                            {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                        </span>
                    @endif

                </a>

            @endauth
            </li>
        </ul>
    </nav>
</header>
