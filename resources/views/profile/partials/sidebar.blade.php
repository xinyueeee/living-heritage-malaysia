@php($active = $active ?? 'overview')

<nav class="profile-sidebar" aria-label="Profile navigation">
    <a href="{{ route('profile') }}" @class(['active' => $active === 'overview']) title="Overview" aria-label="Overview">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V20a1 1 0 0 0 1 1h3v-6h6v6h3a1 1 0 0 0 1-1V9.5"/></svg>
        <span class="profile-sidebar-label">Overview</span>
    </a>
    <a href="{{ route('profile.personal-information') }}" @class(['active' => $active === 'personal-information']) title="Personal Information" aria-label="Personal Information">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 4-6 8-6s8 2 8 6"/></svg>
        <span class="profile-sidebar-label">Personal Information</span>
    </a>
    <a href="{{ route('profile.interests') }}" @class(['active' => $active === 'interests']) title="Interests" aria-label="Interests">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41 11 3.83A2 2 0 0 0 9.5 3H4a1 1 0 0 0-1 1v5.5a2 2 0 0 0 .59 1.41l9.58 9.58a2 2 0 0 0 2.83 0l4.59-4.59a2 2 0 0 0 0-2.83z"/><circle cx="7.5" cy="7.5" r="1.5"/></svg>
        <span class="profile-sidebar-label">Interests</span>
    </a>
    <a href="{{ route('profile.saved-experiences') }}" @class(['active' => $active === 'saved-experiences']) title="Saved Experiences" aria-label="Saved Experiences">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.8 4.6a5 5 0 0 0-7.1 0L12 6.3l-1.7-1.7a5 5 0 0 0-7.1 7.1L12 21l8.8-9.3a5 5 0 0 0 0-7.1z"/></svg>
        <span class="profile-sidebar-label">Saved Experiences</span>
    </a>
    <a href="{{ route('profile.recent-activity') }}" @class(['active' => $active === 'recent-activity']) title="Recent Activity" aria-label="Recent Activity">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/></svg>
        <span class="profile-sidebar-label">Recent Activity</span>
    </a>
    <a href="{{ route('profile.my-posts') }}" @class(['active' => $active === 'my-posts']) title="My Posts" aria-label="My Posts">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3h18v18H3z"/><path d="M7 7h10"/><path d="M7 11h10"/><path d="M7 15h6"/>
        </svg>
        <span class="profile-sidebar-label">My Posts</span>
    </a>
    <a href="{{ route('profile.saved-posts') }}" @class(['active' => $active === 'saved-posts']) title="Saved Posts" aria-label="Saved Posts">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
        <span class="profile-sidebar-label">Saved Posts</span>
    </a>
    
    <a href="{{ route('profile.albums.index') }}" @class(['active' => $active === 'albums']) title="My Albums" aria-label="My Albums">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="8.5" cy="9" r="1.5"/><path d="M21 15l-5-5L5 20"/></svg>
        <span class="profile-sidebar-label">My Albums</span>
    </a>

    <a href="{{ route('profile.achievements') }}" @class(['active' => $active === 'achievements']) title="Achievements & Stats" aria-label="Achievements & Stats">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 21h8"/><path d="M12 17v4"/><path d="M7 4h10v5a5 5 0 0 1-10 0z"/><path d="M7 6H4a1 1 0 0 0-1 1 4 4 0 0 0 4 4"/><path d="M17 6h3a1 1 0 0 1 1 1 4 4 0 0 1-4 4"/></svg>
        <span class="profile-sidebar-label">Achievements &amp; Stats</span>
    </a>

    <div class="profile-sidebar-divider"></div>

    <form method="POST" action="{{ route('logout') }}" class="profile-sidebar-logout-form">
        @csrf
        <button type="submit" class="profile-sidebar-logout" title="Logout" aria-label="Logout">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>
            <span class="profile-sidebar-label">Logout</span>
        </button>
    </form>
</nav>
