import { Head, Link, usePage } from '@inertiajs/react';
import { AnimatePresence, motion, useReducedMotion } from 'framer-motion';
import { useEffect, useRef, useState } from 'react';
import { Action, useWorkspace } from '../Components/Workspace/UI';
import Icon from '../Components/Icon';

const links = [
 ['content.view','Content','/admin/content','rfp'], ['experts.manage','Experts','/admin/experts','experts'],
 ['pages.view','Page compositions','/admin/page-compositions','about'], ['navigation.manage','Navigation and footer','/admin/navigation','menu'],
 ['engagement.view','Engagement','/admin/engagement','contact'], ['applications.view','Applications','/admin/applications','careers'],
 ['media.view','Media library','/admin/media','media'], ['users.view','Users','/admin/users','users'],
 ['roles.manage','Roles and permissions','/admin/roles','shield'], ['settings.manage','Settings Center','/admin/settings','settings'],
 ['audit.view','Audit log','/admin/audit-events','case-studies'],
];
const groups = [
 { title: 'Website content', paths: ['/admin/page-compositions', '/admin/content', '/admin/experts', '/admin/media', '/admin/navigation'] },
 { title: 'Requests and applications', paths: ['/admin/engagement', '/admin/applications'] },
 { title: 'System administration', paths: ['/admin/users', '/admin/roles', '/admin/settings', '/admin/audit-events'], secondary: true },
];
const navigationLabels = { 'Page compositions': 'Website pages', 'Navigation and footer': 'Menus and footer' };
export default function WorkspaceLayout({ title, description, actions, children }) {
 const { t, can, user, privileged, identity, flash, sidebarDefault } = useWorkspace();
 const { url } = usePage();
 const reducedMotion = useReducedMotion();
 const [open,setOpen] = useState(false);
 const [collapsed,setCollapsed] = useState(sidebarDefault === 'collapsed');
 const panel = useRef(null), opener = useRef(null);
 const currentPath = url.split('?')[0], dashboard = privileged ? '/admin' : '/dashboard';
 const parentModule=links.find(([permission, ,href])=>can(permission)&&currentPath.startsWith(`${href}/`));
 useEffect(() => { setOpen(false); }, [url]);
 useEffect(() => {
  if (!open) return;
  const before = document.activeElement, previous = document.body.style.overflow;
  panel.current?.querySelector('button,a')?.focus(); document.body.style.overflow = 'hidden';
  return () => { document.body.style.overflow = previous; before?.focus(); };
 }, [open]);
 const navItem = (text, href, icon, exact = false) => <Link key={href} className="admin-nav-link" href={href} title={t(text)} aria-label={t(text)} aria-current={(exact ? currentPath === href : currentPath === href || currentPath.startsWith(`${href}/`)) ? 'page' : undefined}><Icon name={icon} /><span className="workspace-nav-label">{t(text)}</span></Link>;
 const navigation = <>
  <Link href="/" className="workspace-identity" aria-label={identity?.short_name ?? 'Impact'}><span className="brand-mark">{identity?.logo ? <img src={identity.logo} alt="" className="h-10 w-10 object-contain" /> : (identity?.abbreviation ?? 'I')}</span><span className="workspace-nav-label">{identity?.short_name ?? 'Impact'}<small>{t('Administration')}</small></span></Link>
  <nav aria-label={t('Administration')} className="workspace-navigation">
   {navItem('Dashboard',dashboard,'home',true)}
   {groups.map(group=>{
    const items=group.paths.map(path=>links.find(([, ,href])=>href===path)).filter(([permission])=>can(permission));
    if(!items.length)return null;
    const children=items.map(([,text,href,icon])=>navItem(navigationLabels[text]??text,href,icon));
    return group.secondary?<details key={group.title} className="workspace-nav-group" open={collapsed||items.some(([, ,href])=>currentPath.startsWith(href))}><summary className="workspace-group-heading">{t(group.title)}</summary><div className="grid gap-1">{children}</div></details>:<div key={group.title} className="workspace-nav-group"><p className="workspace-group-heading">{t(group.title)}</p><div className="grid gap-1">{children}</div></div>;
   })}
   {navItem('Profile','/profile','users')}
  </nav>
  <div className="workspace-account"><span className="workspace-avatar" aria-hidden="true">{user?.name?.slice(0,1).toUpperCase() ?? 'I'}</span><div className="workspace-nav-label"><p className="font-semibold text-sm">{user?.name}</p><Action href="/logout" className="workspace-logout">{t('Log out')}</Action></div><div className="workspace-collapsed-logout"><Action href="/logout" className="workspace-logout"><span className="sr-only">{t('Log out')}</span><Icon name="logout" /></Action></div></div>
 </>;
 const keydown = event => {
  if(event.key==='Escape'){setOpen(false);opener.current?.focus();}
  if(event.key==='Tab') {const all=[...panel.current.querySelectorAll('a[href],button:not([disabled])')].filter(element=>element.getClientRects().length), first=all[0], last=all.at(-1);if(event.shiftKey&&document.activeElement===first){event.preventDefault();last.focus();}else if(!event.shiftKey&&document.activeElement===last){event.preventDefault();first.focus();}}
 };
 return <>
  <Head><title>{`${t(title)} - ${identity?.short_name ?? 'Impact'}`}</title><meta head-key="robots" name="robots" content="noindex,nofollow,noarchive" /></Head>
  <a className="skip-link" href="#admin-main-content">{t('Skip to content')}</a>
  <div className={`impact-workspace ${collapsed ? 'workspace-collapsed' : ''}`}>
   <aside className="workspace-sidebar"><button className="workspace-collapse" onClick={()=>setCollapsed(!collapsed)} aria-label={t('Toggle navigation')} aria-expanded={!collapsed}><Icon name="menu" /></button>{navigation}</aside>
   <div className="workspace-main">
    <header className="workspace-topbar"><button ref={opener} className="icon-button lg:hidden" onClick={()=>setOpen(true)} aria-label={t('Open menu')} aria-expanded={open}><Icon name="menu" /></button><span className="workspace-security"><Icon name="shield" className="size-4" />{t('Protected workspace')}</span><Link className="workspace-website" href="/">{t('View website')}<Icon name="arrow-up-right" className="size-4" /></Link><Link className="workspace-avatar" href="/profile" aria-label={t('Profile')}>{user?.name?.slice(0,1).toUpperCase() ?? 'I'}</Link></header>
    <div className="admin-page-header"><div>{currentPath!==dashboard&&<nav className="workspace-breadcrumbs" aria-label={t('Location')}><Link href={dashboard}>{t('Dashboard')}</Link>{parentModule&&<><span aria-hidden="true">/</span><Link href={parentModule[2]}>{t(navigationLabels[parentModule[1]]??parentModule[1])}</Link></>}</nav>}<p className="eyebrow">{t('Administration')}</p><h1 className="heading-2 mt-2">{t(title)}</h1>{description&&<p className="mt-3 text-muted">{t(description)}</p>}</div>{actions&&<div className="workspace-page-actions">{actions}</div>}</div>
    <main id="admin-main-content" tabIndex={-1} className="admin-workspace grid gap-6">{flash.status&&<div className="status-success" role="status">{t(flash.status)}</div>}{children}</main>
   </div>
   <AnimatePresence>{open&&<motion.div initial={{opacity:0}} animate={{opacity:1}} exit={{opacity:0}} className="workspace-overlay" onClick={()=>setOpen(false)}><motion.div ref={panel} role="dialog" aria-modal="true" aria-label={t('Navigation')} onKeyDown={keydown} onClick={event=>event.stopPropagation()} initial={{x:reducedMotion?0:-300}} animate={{x:0}} exit={{x:reducedMotion?0:-300}} transition={reducedMotion?{duration:0}:{type:'spring',stiffness:320,damping:32}} className="workspace-mobile-sidebar"><button className="workspace-close" onClick={()=>setOpen(false)}><Icon name="close" /><span>{t('Close menu')}</span></button>{navigation}</motion.div></motion.div>}</AnimatePresence>
  </div>
 </>;
}
