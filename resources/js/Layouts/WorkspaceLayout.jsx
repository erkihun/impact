import '@fontsource-variable/dm-sans';
import '@fontsource/dm-serif-display';
import { Head, Link, usePage } from '@inertiajs/react';
import { AnimatePresence, motion, useReducedMotion } from 'framer-motion';
import { Fragment, useEffect, useMemo, useRef, useState } from 'react';
import { Action, initials, useWorkspace } from '../Components/Workspace/UI';
import { createActions, isCurrent, navigationGroups } from '../Components/Workspace/Navigation';
import CommandPalette from '../Components/Workspace/CommandPalette';
import Icon from '../Components/Icon';

export { listingPermissions } from '../Components/Workspace/Navigation';

function CreateMenu({ actions }) {
    const { t } = useWorkspace();
    const [open, setOpen] = useState(false);
    const root = useRef(null);
    useEffect(() => {
        if (!open) return undefined;
        const outside = event => { if (!root.current?.contains(event.target)) setOpen(false); };
        const escape = event => { if (event.key === 'Escape') { setOpen(false); root.current?.querySelector('button')?.focus(); } };
        document.addEventListener('mousedown', outside);
        document.addEventListener('keydown', escape);
        return () => { document.removeEventListener('mousedown', outside); document.removeEventListener('keydown', escape); };
    }, [open]);

    return <div className="ws-create" ref={root}>
        <button type="button" className="button-primary" aria-expanded={open} aria-controls="ws-create-menu" onClick={() => setOpen(!open)}><Icon name="plus" className="size-4" /><span>{t('Create')}</span><Icon name="chevron-down" className="size-4" /></button>
        {open && <div className="ws-create-menu" id="ws-create-menu">
            <p>{t('Create a new…')}</p>
            {actions.map(action => <Link key={action.href} href={action.href} onClick={() => setOpen(false)}><Icon name={action.icon} className="size-4" />{t(action.label)}</Link>)}
        </div>}
    </div>;
}

export default function WorkspaceLayout({ title, eyebrow, description, actions, breadcrumbs, children }) {
    const { t, can, user, privileged, identity, flash, sidebarDefault, inbox } = useWorkspace();
    const { url } = usePage();
    const reducedMotion = useReducedMotion();
    const [open, setOpen] = useState(false);
    const [paletteOpen, setPaletteOpen] = useState(false);
    const [collapsed, setCollapsed] = useState(sidebarDefault === 'collapsed');
    const panel = useRef(null), opener = useRef(null);
    const currentPath = url.split('?')[0];

    const groups = navigationGroups({ can, privileged, inbox });
    const today = groups[0].items[0].href;
    const create = createActions(can);
    const current = groups.flatMap(group => group.items).find(item => !item.exact && isCurrent(item, currentPath));
    const currentGroup = groups.find(group => group.items.includes(current));
    const crumbs = breadcrumbs ?? (currentGroup ? [
        { label: currentGroup.title },
        ...(currentPath !== current.href ? [{ label: current.label, href: current.href }] : []),
    ] : []);
    const shortcuts = useMemo(() => [
        ...groups.flatMap(group => group.items.map(item => ({ label: item.label, href: item.href, icon: item.icon, group: group.title ?? 'Go to' }))),
        ...create.map(action => ({ ...action, group: 'Create' })),
        { label: 'Your profile', href: '/profile', icon: 'users', group: 'Account' },
    ], [can, privileged]);

    useEffect(() => { setOpen(false); setPaletteOpen(false); }, [url]);
    useEffect(() => {
        const shortcut = event => {
            if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') { event.preventDefault(); setPaletteOpen(value => !value); }
        };
        window.addEventListener('keydown', shortcut);
        return () => window.removeEventListener('keydown', shortcut);
    }, []);
    useEffect(() => {
        if (!open) return undefined;
        const before = document.activeElement, previous = document.body.style.overflow;
        panel.current?.querySelector('button,a')?.focus(); document.body.style.overflow = 'hidden';
        return () => { document.body.style.overflow = previous; before?.focus(); };
    }, [open]);

    const navigation = <>
        <Link href={today} className="ws-brand" aria-label={`${identity?.short_name ?? 'Impact'} — ${t('Today')}`}>
            <span className="ws-brand-mark">{identity?.logo ? <img src={identity.logo} alt="" /> : (identity?.abbreviation ?? 'I')}</span>
            <span className="ws-nav-label ws-brand-name"><strong>{identity?.short_name ?? 'Impact'}</strong><small>{t('Staff workspace')}</small></span>
        </Link>
        <nav aria-label={t('Administration')} className="ws-nav">
            {groups.map((group, index) => <div key={group.title ?? index} className="ws-nav-group">
                {group.title && <p className="ws-nav-heading">{t(group.title)}</p>}
                {group.items.map(item => <Link key={item.href} href={item.href} className="ws-nav-link" title={t(item.label)} aria-current={isCurrent(item, currentPath) ? 'page' : undefined}>
                    <Icon name={item.icon} />
                    <span className="ws-nav-label">{t(item.label)}</span>
                    {item.badge > 0 && <span className="ws-nav-badge"><span aria-hidden="true">{item.badge}</span><span className="sr-only">{t(':count new', { count: item.badge })}</span></span>}
                </Link>)}
            </div>)}
        </nav>
        <div className="ws-sidebar-foot">
            <p className="ws-protected ws-nav-label"><Icon name="shield" className="size-4" />{t('Protected workspace · activity is recorded')}</p>
            <div className="ws-account">
                <Link href="/profile" className="ws-avatar" aria-label={t('Your profile')}>{initials(user?.name)}</Link>
                <span className="ws-nav-label ws-account-name"><strong>{user?.name}</strong><small>{user?.email}</small></span>
                <Action href="/logout" className="ws-icon-button ws-signout"><Icon name="logout" /><span className="sr-only">{t('Sign out')}</span></Action>
            </div>
            <button type="button" className="ws-collapse" onClick={() => setCollapsed(!collapsed)} aria-expanded={!collapsed}><Icon name={collapsed ? 'arrow-right' : 'arrow-left'} className="size-4" /><span className="ws-nav-label">{t(collapsed ? 'Expand menu' : 'Collapse menu')}</span></button>
        </div>
    </>;
    const trapFocus = event => {
        if (event.key === 'Escape') { setOpen(false); opener.current?.focus(); }
        if (event.key === 'Tab') {
            const all = [...panel.current.querySelectorAll('a[href],button:not([disabled])')].filter(element => element.getClientRects().length), first = all[0], last = all.at(-1);
            if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
            else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
        }
    };

    return <>
        <Head><title>{`${t(title)} - ${identity?.short_name ?? 'Impact'}`}</title><meta head-key="robots" name="robots" content="noindex,nofollow,noarchive" /></Head>
        <a className="skip-link" href="#admin-main-content">{t('Skip to content')}</a>
        <div className={`impact-workspace ws-shell ${collapsed ? 'workspace-collapsed' : ''}`}>
            <aside className="ws-sidebar" aria-label={t('Workspace')}>{navigation}</aside>
            <div className="ws-main">
                <header className="ws-topbar">
                    <button ref={opener} type="button" className="ws-icon-button ws-menu-button" onClick={() => setOpen(true)} aria-label={t('Open menu')} aria-expanded={open}><Icon name="menu" /></button>
                    <button type="button" className="ws-search" onClick={() => setPaletteOpen(true)} aria-keyshortcuts="Control+K Meta+K"><Icon name="search" className="size-4" /><span>{t('Search or jump to…')}</span><kbd>Ctrl K</kbd></button>
                    <span className="ws-spacer" />
                    <a className="ws-topbar-link" href="/" target="_blank" rel="noreferrer">{t('View website')}<Icon name="arrow-up-right" className="size-4" /><span className="sr-only">{t('(opens in a new tab)')}</span></a>
                    {create.length > 0 && <CreateMenu actions={create} />}
                    <Link className="ws-avatar ws-topbar-avatar" href="/profile" aria-label={t('Your profile')}>{initials(user?.name)}</Link>
                </header>
                <main id="admin-main-content" tabIndex={-1} className="ws-page">
                    <div className="ws-page-header">
                        <div className="ws-page-heading">
                            {crumbs.length > 0 && <nav className="ws-breadcrumbs" aria-label={t('Location')}>{crumbs.map((crumb, index) => <Fragment key={`${crumb.label}-${index}`}>
                                {index > 0 && <span aria-hidden="true">/</span>}
                                {crumb.href ? <Link href={crumb.href}>{t(crumb.label)}</Link> : <span>{t(crumb.label)}</span>}
                            </Fragment>)}</nav>}
                            {eyebrow && <p className="ws-greeting-date">{t(eyebrow)}</p>}
                            <h1 className="ws-title">{t(title)}</h1>
                            {description && <p className="ws-lede">{t(description)}</p>}
                        </div>
                        {actions && <div className="ws-page-actions">{actions}</div>}
                    </div>
                    {flash.status && <div className="ws-flash" role="status"><Icon name="check" /><span>{t(flash.status)}</span></div>}
                    <div className="admin-workspace ws-content">{children}</div>
                </main>
            </div>
            <AnimatePresence>{open && <motion.div initial={{ opacity: 0 }} animate={{ opacity: 1 }} exit={{ opacity: 0 }} className="workspace-overlay" onClick={() => setOpen(false)}>
                <motion.div ref={panel} role="dialog" aria-modal="true" aria-label={t('Navigation')} onKeyDown={trapFocus} onClick={event => event.stopPropagation()} initial={{ x: reducedMotion ? 0 : -300 }} animate={{ x: 0 }} exit={{ x: reducedMotion ? 0 : -300 }} transition={reducedMotion ? { duration: 0 } : { type: 'spring', stiffness: 320, damping: 32 }} className="ws-drawer">
                    <button type="button" className="ws-drawer-close" onClick={() => setOpen(false)}><Icon name="close" /><span>{t('Close menu')}</span></button>
                    {navigation}
                </motion.div>
            </motion.div>}</AnimatePresence>
            <CommandPalette open={paletteOpen} onClose={() => setPaletteOpen(false)} shortcuts={shortcuts} />
        </div>
    </>;
}
