// Stroke icon set shared with the Blade `x-ui.icon` component.
const PATHS = {
    shield: ['M12 3 3 7v5c0 5 9 9 9 9s9-4 9-9V7l-9-4Z', 'm8 12 3 3 5-6'],
    users: ['M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2', 'M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8M16 4a4 4 0 0 1 0 7M22 21v-2a4 4 0 0 0-3-4'],
    media: ['M3 3h18v18H3z', 'm3 17 6-6 4 4 3-3 5 5M8 8h.01'],
    settings: ['M4 6h16M4 12h16M4 18h16', 'M8 3v6M16 9v6M10 15v6'],
    logout: ['M9 4H4v16h5M9 12h12m-4-4 4 4-4 4'],
    home: ['m3 11 9-8 9 8', 'M5 10v11h14V10M9 21v-6h6v6'],
    about: ['M4 20h16M6 20V9l6-5 6 5v11M9 20v-6h6v6'],
    services: ['M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2M4 9h16v10a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V9Z', 'M4 13h16M10 13v2h4v-2'],
    industries: ['M3 21h18M5 21V8h6v13M11 21V4h8v17M7.5 11h1M7.5 14h1M14 8h2M14 11h2M14 14h2M14 17h2'],
    experts: ['M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2M9.5 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM17 11l2 2 3-4'],
    'case-studies': ['M9 5h6M9 3h6v4H9zM7 5H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2', 'm8 14 2.5 2.5L16 11'],
    insights: ['M9 18h6M10 22h4M8.5 15.5a7 7 0 1 1 7 0c-.8.6-1.2 1.3-1.3 2.5h-4.4c-.1-1.2-.5-1.9-1.3-2.5Z'],
    events: ['M6 3v3M18 3v3M4 8h16M5 5h14a1 1 0 0 1 1 1v14H4V6a1 1 0 0 1 1-1Z', 'm9 14 2 2 4-4'],
    careers: ['M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M4 7h16v13H4zM4 12h16', 'M10 12v2h4v-2'],
    contact: ['M4 5h16v12H8l-4 4V5Z', 'M8 9h8M8 13h5'],
    consultation: ['M4 5h16v12H8l-4 4V5Z', 'm9 11 2 2 4-4'],
    rfp: ['M6 3h8l4 4v14H6zM14 3v5h5M9 12h6M9 16h6'],
    search: ['M16.95 16.95 20 20', 'M11 18a7 7 0 1 0 0-14 7 7 0 0 0 0 14Z'],
    'arrow-up-right': ['M7 17 17 7M8 7h9v9'],
    'arrow-right': ['M5 12h14M13 6l6 6-6 6'],
    'arrow-left': ['M19 12H5M11 6l-6 6 6 6'],
    'chevron-down': ['m6 9 6 6 6-6'],
    menu: ['M4 7h16M4 12h16M4 17h16'],
    close: ['m6 6 12 12M18 6 6 18'],
    pause: ['M9 5v14M15 5v14'],
    play: ['M7 4v16l13-8L7 4Z'],
    // Workspace
    plus: ['M12 5v14M5 12h14'],
    grid: ['M4 4h7v7H4zM13 4h7v7h-7zM4 13h7v7H4zM13 13h7v7h-7z'],
    layout: ['M4 4h16v16H4zM4 9h16M9 9v11'],
    activity: ['M4 6h16M4 12h9M4 18h6', 'M17 14v3l2 1M17 21a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z'],
    upload: ['M12 16V4m-5 5 5-5 5 5M4 20h16'],
    check: ['m5 12 5 5 9-10'],
    alert: ['M12 9v4M12 17h.01', 'M10.3 3.9 2 18a2 2 0 0 0 1.7 3h16.6a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z'],
    clock: ['M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Z', 'M12 7v5l3 2'],
    lock: ['M6 11h12v10H6zM8 11V7a4 4 0 0 1 8 0v4'],
    eye: ['M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z', 'M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z'],
    'eye-off': ['M3 3l18 18M10.6 10.6a3 3 0 0 0 4.2 4.2M9.9 5.1A10 10 0 0 1 12 5c6.5 0 10 7 10 7a17 17 0 0 1-3.2 4.2M6.6 6.6C3.8 8.4 2 12 2 12s3.5 7 10 7a9.6 9.6 0 0 0 5.4-1.6'],
    link: ['M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1'],
    file: ['M6 3h8l4 4v14H6zM14 3v5h5'],
    'chevron-up': ['m6 15 6-6 6 6'],
    info: ['M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Z', 'M12 8v4M12 16h.01'],
};

export default function Icon({ name, className = 'size-5', strokeWidth = 1.8 }) {
    const paths = PATHS[name] ?? ['M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Z'];

    return (
        <svg
            className={className}
            aria-hidden="true"
            focusable="false"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth={strokeWidth}
            strokeLinecap="round"
            strokeLinejoin="round"
        >
            {paths.map((d) => <path key={d} d={d} />)}
        </svg>
    );
}
