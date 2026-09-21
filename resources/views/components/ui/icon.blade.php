@props(['name'])

<svg
    {{ $attributes->class(['ui-icon']) }}
    aria-hidden="true"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="1.8"
    stroke-linecap="round"
    stroke-linejoin="round"
>
    @switch($name)
        @case('home')
            <path d="m3 11 9-8 9 8"/>
            <path d="M5 10v11h14V10M9 21v-6h6v6"/>
            @break
        @case('about')
            <path d="M4 20h16M6 20V9l6-5 6 5v11M9 20v-6h6v6"/>
            @break
        @case('services')
            <path d="M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2M4 9h16v10a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V9Z"/>
            <path d="M4 13h16M10 13v2h4v-2"/>
            @break
        @case('industries')
            <path d="M3 21h18M5 21V8h6v13M11 21V4h8v17M7.5 11h1M7.5 14h1M14 8h2M14 11h2M14 14h2M14 17h2"/>
            @break
        @case('experts')
            <path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2M9.5 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM17 11l2 2 3-4"/>
            @break
        @case('case-studies')
            <path d="M9 5h6M9 3h6v4H9zM7 5H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/>
            <path d="m8 14 2.5 2.5L16 11"/>
            @break
        @case('insights')
            <path d="M9 18h6M10 22h4M8.5 15.5a7 7 0 1 1 7 0c-.8.6-1.2 1.3-1.3 2.5h-4.4c-.1-1.2-.5-1.9-1.3-2.5Z"/>
            @break
        @case('events')
            <path d="M6 3v3M18 3v3M4 8h16M5 5h14a1 1 0 0 1 1 1v14H4V6a1 1 0 0 1 1-1Z"/>
            <path d="m9 14 2 2 4-4"/>
            @break
        @case('careers')
            <path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M4 7h16v13H4zM4 12h16"/>
            <path d="M10 12v2h4v-2"/>
            @break
        @case('contact')
            <path d="M4 5h16v12H8l-4 4V5Z"/>
            <path d="M8 9h8M8 13h5"/>
            @break
        @case('consultation')
            <path d="M4 5h16v12H8l-4 4V5Z"/>
            <path d="m9 11 2 2 4-4"/>
            @break
        @case('rfp')
            <path d="M6 3h8l4 4v14H6zM14 3v5h5M9 12h6M9 16h6"/>
            @break
        @case('search')
            <circle cx="11" cy="11" r="7"/>
            <path d="m20 20-4-4"/>
            @break
        @case('arrow-up-right')
            <path d="M7 17 17 7M8 7h9v9"/>
            @break
        @case('language')
            <circle cx="12" cy="12" r="9"/>
            <path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/>
            @break
        @case('privacy')
            <path d="M12 3 4 6v6c0 5 3.4 8 8 10 4.6-2 8-5 8-10V6l-8-3Z"/>
            <path d="M9 12h6M12 9v6"/>
            @break
        @case('cookies')
            <path d="M20 13.5A8.5 8.5 0 1 1 10.5 4a4 4 0 0 0 4.5 5 4 4 0 0 0 5 4.5Z"/>
            <circle cx="8" cy="12" r=".7" fill="currentColor" stroke="none"/>
            <circle cx="11" cy="17" r=".7" fill="currentColor" stroke="none"/>
            <circle cx="7" cy="7.5" r=".7" fill="currentColor" stroke="none"/>
            @break
        @case('terms')
            <path d="M6 3h12v18H6zM9 8h6M9 12h6M9 16h4"/>
            @break
        @case('accessibility')
            <circle cx="12" cy="4" r="2"/>
            <path d="M5 8h14M12 6v6M8 21l4-9 4 9"/>
            @break
        @default
            <circle cx="12" cy="12" r="9"/>
    @endswitch
</svg>
