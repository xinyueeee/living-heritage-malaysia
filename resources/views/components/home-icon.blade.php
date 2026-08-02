@props(['name'])

<svg {{ $attributes->class('home-icon') }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    @switch($name)
        @case('discover')
            <circle cx="12" cy="12" r="9"/>
            <path d="m15.8 8.2-2.1 5.5-5.5 2.1 2.1-5.5 5.5-2.1Z"/>
            @break
        @case('community')
            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
            <circle cx="9" cy="7" r="4"/>
            <path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
            @break
        @case('bell')
            <path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/>
            <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
            @break
        @case('gift')
            <path d="M20 12v9H4v-9M2 7h20v5H2zM12 22V7"/>
            <path d="M12 7H7.5a2.5 2.5 0 1 1 0-5C11 2 12 7 12 7ZM12 7h4.5a2.5 2.5 0 1 0 0-5C13 2 12 7 12 7Z"/>
            @break
        @case('heart')
            <path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8l1.1 1.1L12 21l7.8-7.5 1.1-1.1a5.5 5.5 0 0 0-.1-7.8Z"/>
            @break
        @case('globe')
            <circle cx="12" cy="12" r="9"/>
            <path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/>
            @break
        @case('food')
            <path d="M7 3v7a3 3 0 0 1-3 3V3M7 3H4M5.5 13v8M14 3v18M14 3c4 2 4 8 0 10"/>
            @break
        @case('arts')
            <path d="M4 5c4-2 7-2 10 0v7c-1 4-4 7-5 7s-4-3-5-7V5Z"/>
            <path d="M7 9h.01M11 9h.01M7 14c1.3 1 2.7 1 4 0M14 7c2-1 4-1 6 0v6c-.7 2.7-2.5 4.8-4 5.7"/>
            @break
        @case('craft')
            <circle cx="6" cy="17" r="3"/>
            <circle cx="18" cy="17" r="3"/>
            <path d="m8.5 15.5 9-11M15.5 15.5l-9-11"/>
            @break
    @endswitch
</svg>
