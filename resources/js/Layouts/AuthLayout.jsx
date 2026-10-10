import '@fontsource-variable/dm-sans';
import '@fontsource/dm-serif-display';
import { Head, Link } from '@inertiajs/react';
import { motion, useReducedMotion } from 'framer-motion';
import { useWorkspace } from '../Components/Workspace/UI';
import Icon from '../Components/Icon';

/** Staff sign-in shell; `step` shows where the person is in password → verification. */
export default function AuthLayout({ title, lede, step, children }) {
    const reducedMotion = useReducedMotion();
    const { t, identity, flash, loginNotice, securityEmail } = useWorkspace();
    const name = identity?.short_name ?? 'Impact';
    const brand = <Link href="/" className="auth-identity">
        <span className="ws-brand-mark">{identity?.logo ? <img src={identity.logo} alt="" /> : (identity?.abbreviation ?? 'I')}</span>
        <span><strong>{name}</strong><small>{t('Staff workspace')}</small></span>
    </Link>;

    return <>
        <Head><title>{`${t(title)} - ${name}`}</title><meta head-key="robots" name="robots" content="noindex,nofollow,noarchive" /></Head>
        <a href="#auth-main-content" className="skip-link">{t('Skip to content')}</a>
        <div className="impact-auth auth-shell">
            <aside className="auth-brand-panel" aria-label={t('About this workspace')}>
                {brand}
                <div className="auth-brand-copy">
                    <h2>{t('Everything the website needs, in one calm place.')}</h2>
                    <p>{t('Publish pages and listings, answer requests and review applications, with every change recorded.')}</p>
                </div>
                <ul className="auth-promises">
                    <li><Icon name="check" />{t('Two-step verification protects staff accounts')}</li>
                    <li><Icon name="check" />{t('Sessions end automatically when you are away')}</li>
                    <li><Icon name="check" />{t('Uploaded files are scanned before anyone opens them')}</li>
                </ul>
            </aside>
            <main id="auth-main-content" className="auth-form-panel" tabIndex={-1}>
                <motion.div className="auth-form-content auth-card" initial={reducedMotion ? false : { opacity: 0, y: 8 }} animate={{ opacity: 1, y: 0 }}>
                    <div className="auth-mobile-brand">{brand}</div>
                    <Link href="/" className="auth-back-link"><Icon name="arrow-left" className="size-4" />{t('Back to the website')}</Link>
                    {step && <ol className="ws-steps" aria-label={t('Sign-in steps')}>
                        <li className={step > 1 ? 'is-done' : 'is-current'} aria-current={step === 1 ? 'step' : undefined}><span>{step > 1 ? <Icon name="check" className="size-3" /> : 1}</span>{t('Password')}</li>
                        <li className="ws-step-line" aria-hidden="true" />
                        <li className={step === 2 ? 'is-current' : ''} aria-current={step === 2 ? 'step' : undefined}><span>2</span>{t('Verification')}</li>
                    </ol>}
                    <h1 className="auth-title">{t(title)}</h1>
                    {lede && <p className="auth-lede">{t(lede)}</p>}
                    {flash.status && <p className="ws-flash" role="status">{t(flash.status)}</p>}
                    {children}
                    <div className="auth-notice"><Icon name="shield" /><p>{loginNotice ?? t('Authorized staff only. Sign-ins and changes are recorded.')}</p></div>
                    {securityEmail && <p className="auth-support">{t('Locked out or lost your phone? Email')} <a className="text-link" href={`mailto:${securityEmail}`}>{securityEmail}</a></p>}
                </motion.div>
            </main>
        </div>
    </>;
}
