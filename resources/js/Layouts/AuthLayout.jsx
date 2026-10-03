import { Head, Link } from '@inertiajs/react';
import { motion, useReducedMotion } from 'framer-motion';
import { useWorkspace } from '../Components/Workspace/UI';
import Icon from '../Components/Icon';
export default function AuthLayout({title,children}) {
 const reducedMotion=useReducedMotion();
 const {t,identity,flash,loginNotice,securityEmail}=useWorkspace();
 const brand=<Link href="/" className="auth-identity"><span className="brand-mark">{identity?.logo ? <img src={identity.logo} alt="" className="h-10 w-10 object-contain" /> : (identity?.abbreviation ?? 'I')}</span><span>{identity?.short_name ?? 'Impact'}</span></Link>;
 return <>
  <Head><title>{`${t(title)} - ${identity?.short_name ?? 'Impact'}`}</title><meta head-key="robots" name="robots" content="noindex,nofollow,noarchive" /></Head><a href="#auth-main-content" className="skip-link">{t('Skip to content')}</a>
  <div className="impact-auth auth-shell">
   <aside className="auth-brand-panel">{brand}<div className="auth-brand-copy"><p className="auth-kicker"><Icon name="shield" className="size-4" />{t('Protected workspace')}</p><h2>{t('Manage trusted content and engagement safely.')}</h2><span className="auth-brand-rule" aria-hidden="true" /><p>{t('Staff access is invitation-only, permission-aware and protected by multifactor authentication where required.')}</p></div><p className="auth-notice">{loginNotice??t('Authorized users only. Activity may be audited.')}</p></aside>
   <main id="auth-main-content" className="auth-form-panel" tabIndex={-1}><div className="auth-form-content"><div className="auth-mobile-brand">{brand}</div><Link href="/" className="auth-back-link"><Icon name="arrow-left" className="size-4" />{t('View website')}</Link><motion.div className="auth-card" initial={reducedMotion?false:{opacity:0,y:8}} animate={{opacity:1,y:0}}><span className="auth-form-icon" aria-hidden="true"><Icon name="shield" className="size-6" /></span><h1 className="auth-title">{t(title)}</h1>{flash.status&&<p className="status-success mb-5" role="status">{t(flash.status)}</p>}{children}</motion.div>{securityEmail&&<a className="auth-support" href={`mailto:${securityEmail}`}>{securityEmail}</a>}</div></main>
  </div>
 </>;
}
