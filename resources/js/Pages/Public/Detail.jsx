import Reveal from '../../Components/Reveal';
import AppLink from '../../Components/AppLink';
import { RelatedContent, ResponsiveImage } from '../../Components/Public/Common';
import PublicLayout, { PageIntro } from '../../Layouts/PublicLayout';
import { pad } from '../../lib/format';

function Section({ section }) {
    switch (section.kind) {
        case 'portrait':
            return (
                <Reveal as="section">
                    <div className="m-portrait">
                        {section.photo
                            ? <ResponsiveImage src={section.photo} srcSet={section.photoSrcset} sizes={section.photoSizes} width={section.photoWidth} height={section.photoHeight} alt={section.photoAlt} eager />
                            : <span aria-hidden="true">{section.initial}</span>}
                    </div>
                    {section.body && <p className="m-quote">{section.body}</p>}
                </Reveal>
            );
        case 'list':
            return (
                <Reveal as="section">
                    {section.eyebrow && <p className="m-eyebrow">{section.eyebrow}</p>}
                    <h2>{section.heading}</h2>
                    <ol className="m-steps-list">
                        {section.items.map((item, index) => (
                            <li key={`${item}-${index}`}><span>{pad(index + 1)}</span><span>{item}</span></li>
                        ))}
                    </ol>
                </Reveal>
            );
        case 'prose':
            return (
                <Reveal as="section">
                    {section.eyebrow && <p className="m-eyebrow">{section.eyebrow}</p>}
                    <div className="prose mt-6 max-w-none whitespace-pre-line">
                        <p>{section.body}</p>
                    </div>
                </Reveal>
            );
        default:
            return (
                <Reveal as="section">
                    {section.eyebrow && <p className="m-eyebrow">{section.eyebrow}</p>}
                    <h2>{section.heading}</h2>
                    <p className="m-body">{section.body}</p>
                </Reveal>
            );
    }
}

export default function Detail({ breadcrumbs, composition, header, facts, sections, related = [], actions, copy }) {
    return (
        <PublicLayout breadcrumbs={breadcrumbs}>
            <PageIntro composition={composition} header={header}>
                <AppLink className="button-primary" href={actions.consultation.href}>{actions.consultation.label}</AppLink>
                {actions.index && <AppLink className="button-secondary" href={actions.index.href}>{actions.index.label}</AppLink>}
            </PageIntro>

            <div className="content-container">
                <dl className="m-facts" aria-label={copy.atAGlance}>
                    {facts.map((fact) => (
                        <div key={fact.label}>
                            <dt>{fact.label}</dt>
                            <dd>{fact.datetime ? <time dateTime={fact.datetime}>{fact.value}</time> : fact.value}</dd>
                        </div>
                    ))}
                </dl>
            </div>

            <article className="m-section content-container">
                <div className="m-reading">
                    {sections.map((section, index) => <Section key={`${section.kind}-${index}`} section={section} />)}
                </div>
            </article>

            <RelatedContent groups={related} title={copy.related} />

            <section className="m-section m-band" aria-labelledby="detail-next-title">
                <div className="content-container">
                    <Reveal className="m-center">
                        <h2 id="detail-next-title" className="m-title">{copy.nextTitle}</h2>
                        <p className="m-lead">{copy.nextLead}</p>
                        <div className="m-links">
                            <AppLink className="m-btn" href={actions.consultation.href}>{actions.consultation.label}</AppLink>
                            {actions.index && <AppLink className="m-link" href={actions.index.href}>{actions.index.label}</AppLink>}
                        </div>
                    </Reveal>
                </div>
            </section>
        </PublicLayout>
    );
}
