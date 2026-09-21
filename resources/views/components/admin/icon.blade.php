@props(['name', 'class' => 'size-5'])

@php
    // One coherent outline set: same 24px box, same 1.75 stroke, same joins.
    $paths = [
        'dashboard' => '<rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/>',
        'content' => '<path d="M5 3h9l5 5v13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Z"/><path d="M14 3v5h5"/><path d="M8 13h8M8 17h5"/>',
        'pages' => '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 9v12"/><path d="M12 13h6M12 17h4"/>',
        'navigation' => '<path d="M4 6h16M4 12h10M4 18h7"/><circle cx="18" cy="12" r="2"/><path d="m17 17 3 3m0-3-3 3"/>',
        'engagement' => '<path d="M4 5h16v11H8l-4 4V5Z"/><path d="M8 9h8M8 12h5"/>',
        'applications' => '<path d="M9 4h6v3H9z"/><path d="M6 7h12a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V8a1 1 0 0 1 1-1Z"/><path d="M9 13l2 2 4-4"/>',
        'media' => '<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="2"/><path d="m4 18 5-5 4 4 3-3 4 4"/>',
        'users' => '<circle cx="9" cy="8" r="3"/><path d="M3 20a6 6 0 0 1 12 0"/><path d="M16 6a3 3 0 0 1 0 6M18 20a5 5 0 0 0-2-4"/>',
        'roles' => '<path d="M12 3l7 3v6c0 4-3 7-7 9-4-2-7-5-7-9V6l7-3Z"/><path d="m9 12 2 2 4-4"/>',
        'audit' => '<path d="M12 8v5l3 2"/><circle cx="12" cy="12" r="9"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.6 1.6 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.6 1.6 0 0 0-1.8-.3 1.6 1.6 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1A1.6 1.6 0 0 0 9 19.4a1.6 1.6 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.6 1.6 0 0 0 .3-1.8 1.6 1.6 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1A1.6 1.6 0 0 0 4.6 9a1.6 1.6 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.6 1.6 0 0 0 1.8.3H9a1.6 1.6 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.6 1.6 0 0 0 1 1.5 1.6 1.6 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.6 1.6 0 0 0-.3 1.8V9a1.6 1.6 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.6 1.6 0 0 0-1.5 1Z"/>',
        'organization' => '<path d="M3 21h18M5 21V8l7-5 7 5v13M9 21v-6h6v6"/><path d="M8 10h2M14 10h2"/>',
        'branding' => '<path d="M12 3a9 9 0 1 0 0 18h1.5a1.5 1.5 0 0 0 0-3H12a2 2 0 0 1 0-4h3a6 6 0 0 0 0-12h-3Z"/><circle cx="7.5" cy="10" r=".8" fill="currentColor" stroke="none"/><circle cx="9" cy="6.5" r=".8" fill="currentColor" stroke="none"/><circle cx="14" cy="6.5" r=".8" fill="currentColor" stroke="none"/>',
        'appearance' => '<rect x="3" y="4" width="18" height="14" rx="2"/><path d="M8 22h8M12 18v4M8 9h8M8 13h5"/>',
        'homepage' => '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 9h18M7 13h5M7 17h8"/><path d="m16 12 3 3-3 3"/>',
        'localization' => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/>',
        'security' => '<path d="M12 3l7 3v6c0 4-3 7-7 9-4-2-7-5-7-9V6l7-3Z"/><path d="m9 12 2 2 4-4"/>',
        'authentication' => '<circle cx="8" cy="12" r="4"/><path d="M12 12h9M18 12v3M15 12v2"/>',
        'notifications' => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9ZM10 21h4"/>',
        'email' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m4 7 8 6 8-6"/>',
        'integrations' => '<path d="M8 12h8M9 8V5a3 3 0 0 1 6 0v3M9 16v3a3 3 0 0 0 6 0v-3"/><rect x="6" y="8" width="12" height="8" rx="2"/>',
        'privacy' => '<rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3M12 14v3"/>',
        'publishing' => '<path d="M5 3h9l5 5v13H5zM14 3v5h5"/><path d="M8 13h8M8 17h5"/><path d="m15 20 4-4 2 2-4 4h-2v-2Z"/>',
        'seo' => '<circle cx="10" cy="10" r="6"/><path d="m15 15 5 5M7 11l2-2 2 2 3-4"/>',
        'forms' => '<path d="M7 3h10v4H7z"/><path d="M5 5H4v16h16V5h-1M8 12h8M8 16h5"/><path d="m8 9 .8.8L10.5 8"/>',
        'performance' => '<path d="M4 17a8 8 0 1 1 16 0"/><path d="m12 13 4-4M7 17h10"/><circle cx="12" cy="17" r="1"/>',
        'maintenance' => '<path d="m14 6 4-4 4 4-4 4M4 20l9-9"/><path d="M6 3a4 4 0 0 0 5 5L8 11l5 5-3 3-5-5-3 3v-6l4-4a4 4 0 0 0 0-4Z"/>',
        'features' => '<rect x="3" y="5" width="18" height="6" rx="3"/><circle cx="8" cy="8" r="2"/><rect x="3" y="14" width="18" height="6" rx="3"/><circle cx="16" cy="17" r="2"/>',
        'environment' => '<rect x="4" y="3" width="16" height="6" rx="1"/><rect x="4" y="15" width="16" height="6" rx="1"/><path d="M7 6h.01M7 18h.01M12 9v6"/>',
        'history' => '<path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5M12 7v5l3 2"/>',
        'diagnostics' => '<path d="M3 12h4l2-5 4 10 2-5h6"/><path d="M5 4h14v16H5z"/>',
        'profile' => '<circle cx="12" cy="8" r="4"/><path d="M5 21a7 7 0 0 1 14 0"/>',
        'external' => '<path d="M14 4h6v6"/><path d="M20 4 10 14"/><path d="M18 13v6a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h6"/>',
        'collapse' => '<path d="M15 6l-6 6 6 6"/>',
        'expand' => '<path d="M9 6l6 6-6 6"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>',
        'menu' => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'close' => '<path d="m6 6 12 12M18 6 6 18"/>',
        'alert' => '<path d="M12 9v4M12 17h.01"/><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
    ];
@endphp

<svg
    {{ $attributes->merge(['class' => $class]) }}
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="1.75"
    stroke-linecap="round"
    stroke-linejoin="round"
    aria-hidden="true"
    focusable="false"
>{!! $paths[$name] ?? $paths['dashboard'] !!}</svg>
