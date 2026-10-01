import { usePage } from '@inertiajs/react';
import { AnimatePresence, motion } from 'framer-motion';
import { useCallback, useEffect, useRef, useState } from 'react';
import AppLink from '../AppLink';
import Icon from '../Icon';

const EASE = [0.2, 0.8, 0.2, 1];
const MotionLink = motion.create(AppLink);
const FOCUSABLE = 'a[href], button:not([disabled]), input:not([disabled]), [tabindex]:not([tabindex="-1"])';

function MegaPanel({ menu, onNavigate }) {
    return (
        <motion.div
            id={`mega-${menu.id}`}
            className="mega-panel"
            initial={{ opacity: 0, y: -8 }}
            animate={{ opacity: 1, y: 0 }}
            exit={{ opacity: 0, y: -8 }}
            transition={{ duration: 0.25, ease: EASE }}
        >
            <div className={`content-container grid gap-8 py-8 ${menu.promo ? 'lg:grid-cols-[1fr_2fr_1fr]' : 'lg:grid-cols-[1fr_2fr]'}`}>
                <div>
                    <p className="eyebrow">{menu.eyebrow}</p>
                    <p className="mt-3 text-sm leading-6 text-muted">{menu.intro}</p>
                </div>
                <div className="grid gap-2 sm:grid-cols-2">
                    {menu.links.map((link) => (
                        <AppLink key={link.href + link.title} className="mega-link" href={link.href} onClick={onNavigate}>
                            <span className="mega-link-icon"><Icon name={link.icon} /></span>
                            <span>
                                <strong className="block text-white">{link.title}</strong>
                                <span className="mt-1 block text-sm text-muted">{link.description}</span>
                            </span>
                        </AppLink>
                    ))}
                </div>
                {menu.promo && (
                    <div className="glass-promo">
                        <p className="text-sm font-bold text-white">{menu.promo.title}</p>
                        <p className="mt-2 text-sm leading-6 text-slate-300">{menu.promo.text}</p>
                        <AppLink className="text-link mt-3 gap-2" href={menu.promo.href} onClick={onNavigate}>
                            {menu.promo.label} <span aria-hidden="true">→</span>
                        </AppLink>
                    </div>
                )}
            </div>
        </motion.div>
    );
}

function MobileSheet({ open, onClose, navigation, site, ui }) {
    const sheetRef = useRef(null);
    const closeRef = useRef(null);

    useEffect(() => {
        if (! open) {
            return undefined;
        }

        document.body.style.overflow = 'hidden';
        closeRef.current?.focus();

        return () => {
            document.body.style.overflow = '';
        };
    }, [open]);

    // Keep keyboard focus inside the dialog while it is open.
    const trapFocus = (event) => {
        if (event.key !== 'Tab' || ! sheetRef.current) {
            return;
        }

        const controls = [...sheetRef.current.querySelectorAll(FOCUSABLE)];
        const first = controls[0];
        const last = controls[controls.length - 1];

        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last?.focus();
        } else if (! event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first?.focus();
        }
    };

    return (
        <AnimatePresence>
            {open && (
                <motion.div
                    id="mobile-navigation"
                    ref={sheetRef}
                    className="mobile-sheet xl:hidden"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="mobile-navigation-title"
                    onKeyDown={trapFocus}
                    initial={{ opacity: 0 }}
                    animate={{ opacity: 1 }}
                    exit={{ opacity: 0 }}
                    transition={{ duration: 0.2 }}
                >
                    <div className="content-container flex min-h-20 items-center justify-between border-b border-slate-200">
                        <p id="mobile-navigation-title" className="glass-kicker">{ui.navigation}</p>
                        <button ref={closeRef} className="icon-button" type="button" onClick={onClose} aria-label={ui.closeMenu}>
                            <Icon name="close" className="size-6" strokeWidth={2} />
                        </button>
                    </div>
                    <div className="content-container grid gap-6 py-6">
                        <div className="grid grid-cols-2 gap-3">
                            {site.features.search && (
                                <AppLink className="button-secondary" href={site.routes.search}><Icon name="search" className="size-4" />{ui.search}</AppLink>
                            )}
                            {site.locale.alternateUrl && (
                                <a className="button-secondary" href={site.locale.alternateUrl} hrefLang={site.locale.alternate} lang={site.locale.alternate}>
                                    {site.locale.alternateLabel}
                                </a>
                            )}
                        </div>
                        {site.features.consultation && (
                            <AppLink className="button-primary w-full" href={site.routes.consultation}>{ui.requestConsultation}</AppLink>
                        )}
                        <nav aria-label={ui.mobileNavigation} className="grid gap-2">
                            {navigation.mobile.map((item, index) => (
                                <MotionLink
                                    key={item.href}
                                    className="mobile-link"
                                    href={item.href}
                                    initial={{ opacity: 0, x: 16 }}
                                    animate={{ opacity: 1, x: 0 }}
                                    transition={{ duration: 0.35, ease: EASE, delay: 0.03 * index }}
                                >
                                    {item.label}
                                </MotionLink>
                            ))}
                        </nav>
                    </div>
                </motion.div>
            )}
        </AnimatePresence>
    );
}

export default function SiteHeader() {
    const { site, navigation, ui } = usePage().props;
    const [activeMenu, setActiveMenu] = useState(null);
    const [mobileOpen, setMobileOpen] = useState(false);
    const navRef = useRef(null);
    const triggerRef = useRef(null);
    const mobileTriggerRef = useRef(null);

    const closeMenu = useCallback((restoreFocus = false) => {
        setActiveMenu(null);

        if (restoreFocus) {
            triggerRef.current?.focus();
        }
    }, []);

    const closeMobile = useCallback(() => {
        setMobileOpen(false);
        mobileTriggerRef.current?.focus();
    }, []);

    useEffect(() => {
        const onKeyDown = (event) => {
            if (event.key !== 'Escape') {
                return;
            }

            if (mobileOpen) {
                closeMobile();
            } else if (activeMenu) {
                closeMenu(true);
            }
        };
        const onPointerDown = (event) => {
            if (activeMenu && navRef.current && ! navRef.current.contains(event.target)) {
                closeMenu();
            }
        };

        window.addEventListener('keydown', onKeyDown);
        document.addEventListener('pointerdown', onPointerDown);

        return () => {
            window.removeEventListener('keydown', onKeyDown);
            document.removeEventListener('pointerdown', onPointerDown);
        };
    }, [activeMenu, mobileOpen, closeMenu, closeMobile]);

    const toggleMenu = (id, event) => {
        triggerRef.current = event.currentTarget;
        setActiveMenu((current) => (current === id ? null : id));
    };

    const { identity } = site;

    return (
        <header className="public-header">
            <div className="content-container">
                <div className="glass-nav-bar">
                    <AppLink href={site.routes.home} className="glass-brand" aria-label={ui.homeLabel}>
                        {identity.logo ? (
                            <img className="h-10 w-auto max-w-44 object-contain" src={identity.logo} alt={identity.logo_alt ?? ''} />
                        ) : (
                            <span className="glass-brand-mark" aria-hidden="true">{(identity.abbreviation ?? 'I').slice(0, 1)}</span>
                        )}
                        <span>
                            <span className="glass-display block text-lg font-bold leading-none text-white">{identity.short_name}</span>
                            <span className="mt-1 block text-[0.6rem] font-bold uppercase tracking-[0.2em] text-slate-400">{ui.consulting}</span>
                        </span>
                    </AppLink>

                    <nav ref={navRef} aria-label={ui.primaryNavigation} className="primary-navigation">
                        {navigation.primary.map((item) => (
                            item.type === 'link' ? (
                                <AppLink
                                    key={item.id}
                                    className="nav-link"
                                    href={item.href}
                                    data-nav-item={item.id}
                                    aria-current={item.active ? 'page' : undefined}
                                >
                                    {item.label}
                                </AppLink>
                            ) : (
                                <div key={item.id} className="flex">
                                    <button
                                        className="mega-trigger"
                                        type="button"
                                        aria-controls={`mega-${item.id}`}
                                        aria-expanded={activeMenu === item.id}
                                        aria-current={item.active ? 'page' : undefined}
                                        onClick={(event) => toggleMenu(item.id, event)}
                                    >
                                        {item.label}
                                        <motion.span
                                            className="nav-chevron"
                                            aria-hidden="true"
                                            animate={{ rotate: activeMenu === item.id ? 180 : 0 }}
                                            transition={{ duration: 0.2 }}
                                        >
                                            <Icon name="chevron-down" className="size-3" strokeWidth={2} />
                                        </motion.span>
                                    </button>
                                    <AnimatePresence>
                                        {activeMenu === item.id && (
                                            <MegaPanel menu={item} onNavigate={() => setActiveMenu(null)} />
                                        )}
                                    </AnimatePresence>
                                </div>
                            )
                        ))}
                    </nav>

                    <div className="flex items-center gap-1">
                        {site.features.search && (
                            <AppLink href={site.routes.search} className="icon-link" aria-label={ui.search}>
                                <Icon name="search" strokeWidth={2} />
                            </AppLink>
                        )}
                        {site.locale.alternateUrl && (
                            <a
                                href={site.locale.alternateUrl}
                                className="glass-lang"
                                hrefLang={site.locale.alternate}
                                lang={site.locale.alternate}
                                aria-label={`${site.locale.alternate.toUpperCase()} – ${site.locale.alternateLabel}`}
                            >
                                {site.locale.alternate}
                            </a>
                        )}
                        {site.features.consultation && (
                            <AppLink className="button-primary ms-1 hidden xl:inline-flex" href={site.routes.consultation}>
                                {ui.consultation}
                            </AppLink>
                        )}
                        <button
                            ref={mobileTriggerRef}
                            className="icon-button xl:hidden"
                            type="button"
                            onClick={() => setMobileOpen(true)}
                            aria-controls="mobile-navigation"
                            aria-expanded={mobileOpen}
                            aria-label={ui.openMenu}
                        >
                            <Icon name="menu" className="size-6" strokeWidth={2} />
                        </button>
                    </div>
                </div>
            </div>

            <MobileSheet open={mobileOpen} onClose={closeMobile} navigation={navigation} site={site} ui={ui} />
        </header>
    );
}
