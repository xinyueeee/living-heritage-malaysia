<header class="site-header">
    <nav class="navbar container" aria-label="Main navigation">
        <a class="brand" href="{{ route('home') }}" aria-label="Living Heritage Malaysia home">
            <span class="brand-mark" aria-hidden="true">LH</span>
            <span>Living Heritage<br><strong>Malaysia</strong></span>
        </a>

        <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="main-menu" aria-label="Open navigation menu">
            <span></span><span></span><span></span>
        </button>

        <ul class="nav-menu" id="main-menu">
            <li><a @class(['active' => request()->routeIs('home')]) href="{{ route('home') }}">Home</a></li>
            <li><a @class(['active' => request()->routeIs('experiences.*')]) href="{{ route('experiences.index') }}">Discover</a></li>
            <li><a href="#" aria-disabled="true">Community</a></li>
            <li><a href="#" aria-disabled="true">Festival Alert</a></li>
            <li><a @class(['active' => request()->routeIs('engagement.*')])href="{{ route('engagement.index') }}">Engagement &amp; Rewards</a></li>
            <li><a @class(['active' => request()->routeIs('profile')]) href="{{ route('profile') }}">Profile</a></li>

            <li class="nav-auth">
                @auth
                    @php($unreadCount = \Illuminate\Support\Facades\DB::table('notification')->where('user_id', auth()->id())->where('is_read', 'Unseen')->count())
                    <a class="nav-bell" href="#" aria-disabled="true" aria-label="Notifications">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                        @if ($unreadCount > 0)
                            <span class="nav-bell-badge">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
                        @endif
                    </a>

                    <details class="nav-account">
                        <summary class="nav-avatar-btn" aria-label="Account menu">
                            @if (auth()->user()->profile_photo)
                                <img class="nav-avatar" src="{{ auth()->user()->profile_photo }}" alt="">
                            @else
                                <span class="nav-avatar nav-avatar-fallback">{{ \Illuminate\Support\Str::substr(auth()->user()->user_name ?? '?', 0, 1) }}</span>
                            @endif
                            <svg class="nav-account-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
                        </summary>
                        <div class="nav-account-menu">
                            <a href="{{ route('profile') }}">View Profile</a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit">Log Out</button>
                            </form>
                        </div>
                    </details>
                @else
                    <a @class(['nav-login-btn', 'active' => request()->routeIs('login')]) href="{{ route('login') }}">Log In</a>
                @endauth
            </li>
        </ul>
    </nav>
</header>
