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
            <li><a @class(['active' => request()->routeIs('festival.*')]) href="{{ route('festival.calendar') }}">Festival Alert</a></li>
            <li><a @class(['active' => request()->routeIs('trip.planner.*')]) href="{{ route('trip.planner.index') }}">Trip Planner</a></li>
            <li><a @class(['active' => request()->routeIs('engagement.*')])href="{{ route('engagement.index') }}">Engagement &amp; Rewards</a></li>
            <li><a @class(['active' => request()->routeIs('profile')]) href="{{ route('profile') }}">Profile</a></li>


            <li class="nav-auth">
                @auth
                 
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

                    <span
                        id="notificationBadge"
                        class="nav-bell-badge"
                        style="display: none;"
                    >
                        0
                    </span>

                </a>

            @endauth
            </li>
        </ul>
    </nav>
</header>

@push('scripts')

<script>
async function updateNotificationBadge()
{
    try
    {
        const response = await fetch(
            "{{ route('notifications.count') }}"
        );

        const data = await response.json();

        const badge =
            document.getElementById('notificationBadge');

        if (!badge)
        {
            return;
        }

        if (data.count > 0)
        {
            badge.style.display = 'flex';

            badge.textContent =
                data.count > 9
                    ? '9+'
                    : data.count;
        }
        else
        {
            badge.style.display = 'none';
        }
    }
    catch (error)
    {
        console.error(
            'Notification count error:',
            error
        );
    }
}


// Check immediately
updateNotificationBadge();


// Check every 10 seconds
setInterval(
    updateNotificationBadge,
    10000
);
</script>

@endpush
