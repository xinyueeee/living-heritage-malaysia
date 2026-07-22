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
            <li><a @class(['active' => request()->routeIs('experiences.*')]) href="{{ route('experiences.index') }}">Cultural Experiences</a></li>
            <li><a href="#" aria-disabled="true">For You</a></li>
            <li><a href="#footer">Community</a></li>
            <li><a href="#" aria-disabled="true">Passport &amp; Rewards</a></li>
            <li><a href="#" aria-disabled="true">Profile</a></li>
        </ul>
    </nav>
</header>
