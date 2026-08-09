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
        @case('sparkles')
            <path d="m12 3 1.2 3.8L17 8l-3.8 1.2L12 13l-1.2-3.8L7 8l3.8-1.2L12 3Z"/>
            <path d="m5 13 .8 2.2L8 16l-2.2.8L5 19l-.8-2.2L2 16l2.2-.8L5 13ZM19 12l.7 1.8 1.8.7-1.8.7L19 17l-.7-1.8-1.8-.7 1.8-.7L19 12Z"/>
            @break
        @case('refresh')
            <path d="M20 7v5h-5"/>
            <path d="M4 17v-5h5"/>
            <path d="M6.1 9a7 7 0 0 1 11.4-2.2L20 9M4 15l2.5 2.2A7 7 0 0 0 17.9 15"/>
            @break
        @case('target')
            <circle cx="12" cy="12" r="9"/>
            <circle cx="12" cy="12" r="5"/>
            <circle cx="12" cy="12" r="1.5"/>
            <path d="m15 9 6-6M17 3h4v4"/>
            @break
        @case('museum')
            <path d="m3 9 9-5 9 5H3Z"/>
            <path d="M5 10v7M9.5 10v7M14.5 10v7M19 10v7M3 20h18M2 17h20"/>
            @break
        @case('culinary')
            <path d="M4 10h16c0 5-3.5 9-8 9s-8-4-8-9Z"/>
            <path d="M7 7c0-1 1-1.5 1-2.5M12 7c0-1 1-1.5 1-2.5M17 7c0-1 1-1.5 1-2.5M7 21h10"/>
            @break
        @case('performance')
            <path d="M3 5c3-1.5 6-1.5 9 0v7c-.8 3-3.2 5.5-4.5 5.5S4 15 3 12V5Z"/>
            <path d="M12 7c3-1.5 6-1.5 9 0v7c-.8 3-3.2 5.5-4.5 5.5-1 0-2.5-1.5-3.5-3.5M6 9h.01M9 9h.01M6.5 13c.8.7 1.7.7 2.5 0M15 11h.01M18 11h.01"/>
            @break
        @case('clock')
            <circle cx="12" cy="12" r="9"/>
            <path d="M12 7v5l3 2"/>
            @break
        @case('map-pin')
            <path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/>
            <circle cx="12" cy="10" r="2.5"/>
            @break
        @case('star')
            <path d="m12 3 2.7 5.5 6.1.9-4.4 4.3 1 6.1-5.4-2.9-5.4 2.9 1-6.1-4.4-4.3 6.1-.9L12 3Z"/>
            @break
    @endswitch
</svg>
