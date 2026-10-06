import AppLink from '../../Components/AppLink';
import PublicLayout, { PageIntro } from '../../Layouts/PublicLayout';

function Section({ section }) {
    return (
        <section id={section.id} className="scroll-mt-28 border-t border-slate-300 pt-8 first:border-t-0 first:pt-0 [&+section]:mt-12">
            <h2 className="heading-3">{section.heading}</h2>
            {[].concat(section.body ?? []).map((paragraph) => (
                <p key={paragraph} className="mt-4 text-base leading-8 text-slate-700">{paragraph}</p>
            ))}
            {section.list?.length > 0 && (
                <ul className="mt-4 grid gap-3">
                    {section.list.map((item) => (
                        <li key={item} className="flex gap-3 text-base leading-8 text-slate-700">
                            <span className="mt-3 size-1.5 shrink-0 bg-action-500" aria-hidden="true" />
                            <span>{item}</span>
                        </li>
                    ))}
                </ul>
            )}
        </section>
    );
}

export default function Legal({ breadcrumbs, composition, header, sections, extra, copy }) {
    const openConsent = () => window.dispatchEvent(new CustomEvent('open-consent-preferences'));
    const contents = extra ? [...sections, extra] : sections;

    return (
        <PublicLayout breadcrumbs={breadcrumbs}>
            <PageIntro composition={composition} header={header} />

            <div className="public-section impact-editorial-surface">
                <div className="legal-layout content-container">
                    {contents.length > 1 && (
                        <nav className="legal-toc" aria-labelledby="legal-contents-heading">
                            <h2 id="legal-contents-heading" className="eyebrow">{copy.onThisPage}</h2>
                            <ul className="mt-5 grid gap-1 border-s border-slate-300 ps-4">
                                {contents.map((section) => (
                                    <li key={section.id}>
                                        <a className="flex min-h-11 items-center text-sm font-semibold text-action-700 underline-offset-4 hover:underline" href={`#${section.id}`}>
                                            {section.heading}
                                        </a>
                                    </li>
                                ))}
                            </ul>
                        </nav>
                    )}

                    <article className="public-content-canvas reading-width">
                        {sections.map((section) => <Section key={section.id} section={section} />)}

                        {extra && (
                            <section id={extra.id} className="mt-12 scroll-mt-28 border-t border-slate-300 pt-8">
                                <h2 className="heading-3">{extra.heading}</h2>
                                <p className="mt-4 text-base leading-8 text-slate-700">{extra.body}</p>
                                {extra.action.type === 'consent' ? (
                                    <button type="button" className="button-primary mt-5" onClick={openConsent}>{extra.action.label}</button>
                                ) : (
                                    <AppLink className="button-primary mt-5" href={extra.action.href}>{extra.action.label}</AppLink>
                                )}
                                {extra.note && <p className="mt-4 text-sm leading-6 text-muted">{extra.note}</p>}
                            </section>
                        )}

                        <aside className="mt-12 rounded-[var(--m-radius)] bg-quiet p-6">
                            <h2 className="heading-3">{copy.questionsTitle}</h2>
                            <p className="mt-3 text-base leading-8 text-slate-700">{copy.questionsBody}</p>
                            <AppLink className="button-primary mt-5" href={copy.contactHref}>{copy.contact}</AppLink>
                        </aside>
                    </article>
                </div>
            </div>
        </PublicLayout>
    );
}
