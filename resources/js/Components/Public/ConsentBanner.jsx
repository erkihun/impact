import { usePage } from '@inertiajs/react';
import { AnimatePresence, motion } from 'framer-motion';
import { useCallback, useEffect, useRef, useState } from 'react';
import Icon from '../Icon';

// Shared with the Blade/Alpine consent manager so a choice made on either
// kind of page is honoured on the other.
const STORAGE_KEY = 'impact.consent';
const OPTIONAL = ['preferences', 'analytics', 'marketing'];

function readStored(policyVersion) {
    try {
        const stored = JSON.parse(window.localStorage.getItem(STORAGE_KEY) ?? 'null');

        return stored && stored.policyVersion === policyVersion ? stored : null;
    } catch {
        return null;
    }
}

export default function ConsentBanner() {
    const { site, ui } = usePage().props;
    const policyVersion = site.privacy.policy_version;
    const [open, setOpen] = useState(false);
    const [detailsOpen, setDetailsOpen] = useState(false);
    const [saving, setSaving] = useState(false);
    const [failed, setFailed] = useState(false);
    const [decisions, setDecisions] = useState({ preferences: false, analytics: false, marketing: false });
    const bannerRef = useRef(null);
    const detailsHeadingRef = useRef(null);

    useEffect(() => {
        const stored = readStored(policyVersion);

        if (stored) {
            setDecisions((current) => ({ ...current, ...stored.decisions }));
        } else {
            setOpen(true);
        }
    }, [policyVersion]);

    useEffect(() => {
        const reopen = () => {
            setFailed(false);
            setOpen(true);
            setDetailsOpen(true);
        };

        window.addEventListener('open-consent-preferences', reopen);

        return () => window.removeEventListener('open-consent-preferences', reopen);
    }, []);

    useEffect(() => {
        if (detailsOpen) {
            detailsHeadingRef.current?.focus();
        }
    }, [detailsOpen]);

    const persist = useCallback(async (choice) => {
        setSaving(true);
        setFailed(false);

        // Necessary storage is always recorded as granted; it is not optional.
        const payload = { necessary: true, ...choice };

        try {
            const response = await fetch(site.routes.consent, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': site.csrfToken,
                },
                body: JSON.stringify({ decisions: payload, policy_version: policyVersion }),
            });

            if (! response.ok) {
                throw new Error('Consent was not recorded.');
            }

            window.localStorage.setItem(STORAGE_KEY, JSON.stringify({
                policyVersion,
                decisions: payload,
                recordedAt: new Date().toISOString(),
            }));

            setDecisions((current) => ({ ...current, ...choice }));
            setOpen(false);
            setDetailsOpen(false);
            window.dispatchEvent(new CustomEvent('consent-updated', { detail: payload }));
        } catch {
            setFailed(true);
        } finally {
            setSaving(false);
        }
    }, [policyVersion, site.csrfToken, site.routes.consent]);

    const all = (value) => Object.fromEntries(OPTIONAL.map((key) => [key, value]));

    if (! site.privacy.cookie_notice_enabled) {
        return null;
    }

    return (
        <>
            <AnimatePresence>
                {open && ! detailsOpen && (
                    <motion.section
                        ref={bannerRef}
                        tabIndex={-1}
                        className="consent-banner"
                        aria-labelledby="consent-banner-title"
                        initial={{ y: '100%' }}
                        animate={{ y: 0 }}
                        exit={{ y: '100%' }}
                        transition={{ duration: 0.45, ease: [0.2, 0.8, 0.2, 1] }}
                    >
                        <div className="content-container py-5">
                            <div className="grid gap-5 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-center">
                                <div>
                                    <h2 id="consent-banner-title" className="text-base font-bold text-brand-950">{ui.consentTitle}</h2>
                                    <p className="mt-2 max-w-3xl text-sm leading-6 text-muted">
                                        {ui.consentSummary}{' '}
                                        <a className="font-semibold text-action-700 underline underline-offset-4" href={site.routes.privacy}>{ui.consentPrivacy}</a>
                                    </p>
                                    {failed && <p className="mt-3 text-sm font-medium text-danger" role="alert">{ui.consentFailed}</p>}
                                </div>
                                {/* Equal visual weight: no option is styled to be more attractive than another. */}
                                <div className="flex flex-col gap-3 sm:flex-row lg:shrink-0">
                                    <button type="button" className="button-secondary" onClick={() => setDetailsOpen(true)} disabled={saving}>{ui.manageChoices}</button>
                                    <button type="button" className="button-secondary" onClick={() => persist(all(false))} disabled={saving}>{ui.rejectOptional}</button>
                                    <button type="button" className="button-secondary" onClick={() => persist(all(true))} disabled={saving}>{ui.acceptOptional}</button>
                                </div>
                            </div>
                        </div>
                    </motion.section>
                )}
            </AnimatePresence>

            <AnimatePresence>
                {detailsOpen && (
                    <motion.div
                        className="consent-overlay"
                        role="presentation"
                        initial={{ opacity: 0 }}
                        animate={{ opacity: 1 }}
                        exit={{ opacity: 0 }}
                        onKeyDown={(event) => event.key === 'Escape' && setDetailsOpen(false)}
                    >
                        <motion.div
                            className="consent-dialog w-full max-w-2xl bg-white p-6 sm:p-8"
                            role="dialog"
                            aria-modal="true"
                            aria-labelledby="consent-details-title"
                            initial={{ y: 24, opacity: 0 }}
                            animate={{ y: 0, opacity: 1 }}
                            exit={{ y: 24, opacity: 0 }}
                            transition={{ duration: 0.3, ease: [0.2, 0.8, 0.2, 1] }}
                        >
                            <div className="flex items-start justify-between gap-4">
                                <h2 id="consent-details-title" ref={detailsHeadingRef} tabIndex={-1} className="text-xl font-bold text-brand-950">{ui.manageTitle}</h2>
                                <button type="button" className="icon-button" onClick={() => setDetailsOpen(false)} aria-label={ui.close}>
                                    <Icon name="close" className="size-6" strokeWidth={2} />
                                </button>
                            </div>
                            <p className="mt-3 text-sm leading-6 text-muted">{ui.manageIntro}</p>
                            <ul className="mt-6 grid gap-3">
                                <li className="form-choice justify-between">
                                    <span><strong className="block">{ui.necessary}</strong><span className="text-sm text-muted">{ui.necessaryDescription}</span></span>
                                    <span className="shrink-0 text-sm font-semibold">{ui.alwaysActive}</span>
                                </li>
                                {OPTIONAL.map((key) => (
                                    <li key={key} className="form-choice justify-between">
                                        <span>
                                            <strong className="block">{ui[key]}</strong>
                                            <span className="text-sm text-muted">{ui[`${key}Description`]}</span>
                                        </span>
                                        <button
                                            type="button"
                                            className="button-secondary shrink-0"
                                            role="switch"
                                            aria-checked={decisions[key]}
                                            onClick={() => setDecisions((current) => ({ ...current, [key]: ! current[key] }))}
                                        >
                                            {decisions[key] ? ui.allowed : ui.notAllowed}
                                        </button>
                                    </li>
                                ))}
                            </ul>
                            {failed && <p className="mt-4 text-sm font-medium text-danger" role="alert">{ui.consentFailed}</p>}
                            <div className="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-end">
                                <button type="button" className="button-secondary" onClick={() => persist(all(false))} disabled={saving}>{ui.rejectOptional}</button>
                                <button type="button" className="button-secondary" onClick={() => persist(all(true))} disabled={saving}>{ui.acceptOptional}</button>
                                <button type="button" className="button-primary" onClick={() => persist({ ...decisions })} disabled={saving} aria-busy={saving || undefined}>{saving ? ui.saving : ui.saveChoices}</button>
                            </div>
                        </motion.div>
                    </motion.div>
                )}
            </AnimatePresence>
        </>
    );
}
