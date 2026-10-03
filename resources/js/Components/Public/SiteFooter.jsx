import { useForm, usePage } from '@inertiajs/react';
import AppLink from '../AppLink';
import Icon from '../Icon';

export default function SiteFooter() {
    const { site, navigation, ui } = usePage().props;
    const { identity } = site;
    const form = useForm({
        email: '',
        marketing_consent: false,
        policy_version: site.privacy.policy_version,
    });

    const subscribe = (event) => {
        event.preventDefault();
        form.post(site.routes.newsletter, {
            preserveScroll: true,
            onSuccess: () => form.reset('email', 'marketing_consent'),
        });
    };

    const openConsent = () => window.dispatchEvent(new CustomEvent('open-consent-preferences'));
    const year = new Date().getFullYear();

    return (
        <footer className="public-footer text-slate-200">
            <div className="content-container pt-10">
                <div className="glass-footer">
                    <div className="grid gap-12 py-12 lg:grid-cols-[1.2fr_.8fr_.8fr_1.2fr]">
                        <div>
                            <p className="glass-display text-2xl font-semibold text-white">{identity.legal_name}</p>
                            <p className="mt-4 max-w-sm text-sm leading-7 text-slate-300">{identity.tagline}</p>
                            <address className="mt-4 grid gap-1 text-sm not-italic text-slate-300">
                                {identity.address && <span>{identity.address}</span>}
                                {identity.postal_address && <span>{identity.postal_address}</span>}
                                {identity.phone && <a className="hover:text-white hover:underline" href={`tel:${identity.phone}`}>{identity.phone}</a>}
                                {identity.email && <a className="hover:text-white hover:underline" href={`mailto:${identity.email}`}>{identity.email}</a>}
                                {identity.working_hours && <span>{identity.working_hours}</span>}
                            </address>
                            {site.features.consultation && (
                                <AppLink className="mt-5 inline-flex min-h-11 items-center gap-2 text-sm font-bold text-action-100 hover:text-white" href={site.routes.consultation}>
                                    {ui.requestConsultation}
                                    <Icon name="arrow-right" className="size-4" strokeWidth={2} aria-hidden="true" />
                                </AppLink>
                            )}
                        </div>

                        <nav aria-labelledby="footer-explore-heading">
                            <h2 id="footer-explore-heading" className="footer-heading">{ui.explore}</h2>
                            <div className="mt-4 grid gap-1 text-sm">
                                {navigation.footer.footer_explore.map((item) => (
                                    <AppLink key={item.href} className="footer-menu-link" href={item.href}>{item.label}</AppLink>
                                ))}
                            </div>
                        </nav>

                        <nav aria-labelledby="footer-engage-heading">
                            <h2 id="footer-engage-heading" className="footer-heading">{ui.engage}</h2>
                            <div className="mt-4 grid gap-1 text-sm">
                                {navigation.footer.footer_engage.map((item) => (
                                    <AppLink key={item.href} className="footer-menu-link" href={item.href}>{item.label}</AppLink>
                                ))}
                            </div>
                        </nav>

                        <div>
                            <h2 className="footer-heading">{ui.newsletterTitle}</h2>
                            <form className="mt-4 grid gap-3" onSubmit={subscribe} noValidate={false}>
                                <label className="text-sm font-bold text-white" htmlFor="newsletter-email">{ui.email}</label>
                                <div className="flex flex-col gap-2 sm:flex-row">
                                    <input
                                        id="newsletter-email"
                                        type="email"
                                        required
                                        autoComplete="email"
                                        className="form-input min-w-0 flex-1 text-sm"
                                        value={form.data.email}
                                        onChange={(event) => form.setData('email', event.target.value)}
                                        aria-invalid={form.errors.email ? 'true' : undefined}
                                        aria-describedby={form.errors.email ? 'newsletter-email-error' : undefined}
                                    />
                                    <button className="button-primary" type="submit" disabled={form.processing} aria-busy={form.processing || undefined}>
                                        {ui.join}
                                    </button>
                                </div>
                                {form.errors.email && (
                                    <p id="newsletter-email-error" className="text-sm font-medium text-orange-200" role="alert">{form.errors.email}</p>
                                )}
                                <label className="flex items-start gap-3 text-xs leading-5 text-slate-300" htmlFor="newsletter-consent">
                                    <input
                                        id="newsletter-consent"
                                        type="checkbox"
                                        required
                                        className="mt-0.5 rounded"
                                        checked={form.data.marketing_consent}
                                        onChange={(event) => form.setData('marketing_consent', event.target.checked)}
                                    />
                                    <span>{ui.newsletterConsent}</span>
                                </label>
                                {form.errors.marketing_consent && (
                                    <p className="text-sm font-medium text-orange-200" role="alert">{form.errors.marketing_consent}</p>
                                )}
                            </form>
                        </div>
                    </div>

                    <div className="border-t border-white/10">
                        <div className="flex flex-col justify-between gap-4 py-6 text-xs text-slate-400 lg:flex-row lg:items-center">
                            <p>&copy; {identity.copyright_start_year}-{year} {identity.copyright_owner}</p>
                            <nav aria-label={ui.legal} className="flex flex-wrap items-center gap-x-5 gap-y-1">
                                {navigation.footer.footer_legal.map((item) => (
                                    <AppLink key={item.href} className="footer-legal-link" href={item.href}>{item.label}</AppLink>
                                ))}
                                {site.privacy.cookie_notice_enabled && (
                                    <button type="button" className="footer-legal-link text-start" onClick={openConsent}>{ui.privacyChoices}</button>
                                )}
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </footer>
    );
}
