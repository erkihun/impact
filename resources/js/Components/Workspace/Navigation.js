// One map of the workspace: the sidebar, the command palette, the Create
// menu and the dashboard all read from here, filtered by permission.

// Any one of these opens Website listings (services, insights, events, …).
export const listingPermissions = ['services.manage', 'industries.manage', 'case-studies.manage', 'insights.manage', 'events.manage', 'vacancies.manage', 'experts.manage'];

export const allowed = (can, permission) => [].concat(permission).some(can);

/** Sidebar groups. `match` lists other paths that light the item up. */
export function navigationGroups({ can, privileged, inbox = {} }) {
    const groups = [
        { title: null, items: [
            { label: 'Today', href: privileged ? '/admin' : '/dashboard', icon: 'home', exact: true },
        ] },
        { title: 'Website', items: [
            { permission: 'pages.view', label: 'Pages', href: '/admin/page-compositions', icon: 'layout' },
            { permission: listingPermissions, label: 'Listings', href: '/admin/website', icon: 'grid', match: ['/admin/experts'] },
            { permission: 'media.view', label: 'Media', href: '/admin/media', icon: 'media' },
            { permission: 'navigation.manage', label: 'Menus and footer', href: '/admin/navigation', icon: 'menu' },
            { permission: 'seo.manage', label: 'SEO', href: '/admin/seo', icon: 'search' },
            { permission: 'content.view', label: 'Content drafts', href: '/admin/content', icon: 'rfp' },
        ] },
        { title: 'Inbox', items: [
            { permission: 'engagement.view', label: 'Requests', href: '/admin/engagement', icon: 'contact', badge: inbox.requests },
            { permission: 'applications.view', label: 'Applications', href: '/admin/applications', icon: 'careers', badge: inbox.applications },
        ] },
        { title: 'Organization', items: [
            { permission: 'users.view', label: 'Team and access', href: '/admin/users', icon: 'users', match: ['/admin/roles'] },
            { permission: 'settings.manage', label: 'Settings', href: '/admin/settings', icon: 'settings' },
            { permission: 'audit.view', label: 'Activity log', href: '/admin/audit-events', icon: 'activity' },
        ] },
    ];

    return groups
        .map(group => ({ ...group, items: group.items.filter(item => !item.permission || allowed(can, item.permission)) }))
        .filter(group => group.items.length);
}

export const isCurrent = (item, path) => [item.href, ...(item.match ?? [])].some(href => item.exact && href === item.href
    ? path === href
    : path === href || path.startsWith(`${href}/`));

/** Everything that can be started from the Create menu or the dashboard. */
export function createActions(can) {
    return [
        { permission: 'services.manage', label: 'Service', href: '/admin/website/services/create', icon: 'services' },
        { permission: 'industries.manage', label: 'Industry', href: '/admin/website/industries/create', icon: 'industries' },
        { permission: 'case-studies.manage', label: 'Case study', href: '/admin/website/case-studies/create', icon: 'case-studies' },
        { permission: 'insights.manage', label: 'Insight', href: '/admin/website/insights/create', icon: 'insights' },
        { permission: 'events.manage', label: 'Event', href: '/admin/website/events/create', icon: 'events' },
        { permission: 'vacancies.manage', label: 'Vacancy', href: '/admin/website/vacancies/create', icon: 'careers' },
        { permission: 'experts.manage', label: 'Expert profile', href: '/admin/experts/create', icon: 'experts' },
        { permission: 'media.create', label: 'Upload media', href: '/admin/media', icon: 'upload' },
        { permission: 'content.create', label: 'Content draft', href: '/admin/content/create', icon: 'rfp' },
        { permission: 'users.manage', label: 'Invite a person', href: '/admin/users#invite', icon: 'users' },
    ].filter(action => allowed(can, action.permission));
}
