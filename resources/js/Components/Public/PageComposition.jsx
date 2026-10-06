import Reveal from '../Reveal';
import AppLink from '../AppLink';
import { EmptyState } from './Common';

// Renders editor-managed page sections (PageComposer) with the same section
// types, surfaces, widths and spacing the Blade renderer supported.
const SURFACES = { white: '', paper: '', muted: 'm-band', gold: 'm-band', brand: 'm-surface-brand' };
const WIDTHS = {
    reading: 'mx-auto max-w-3xl px-5 sm:px-8',
    standard: 'content-container',
    wide: 'content-container max-w-screen-2xl',
    full: 'w-full',
};
const SPACING = { none: 'py-0', compact: 'py-8 sm:py-10', standard: 'm-section', spacious: 'py-20 sm:py-28' };

function Actions({ actions, center = false }) {
    if (! actions?.length) {
        return null;
    }

    return (
        <div className={`mt-7 flex flex-wrap gap-3 ${center ? 'justify-center' : ''}`}>
            {actions.map((action) => (
                <AppLink
                    key={action.href + action.label}
                    className={action.primary ? 'button-primary' : 'button-secondary'}
                    href={action.href}
                    target={action.newContext ? '_blank' : undefined}
                    rel={action.newContext ? 'noopener noreferrer' : undefined}
                >
                    {action.label}
                    {action.description && <span className="sr-only">{action.description}</span>}
                </AppLink>
            ))}
        </div>
    );
}

function Intro({ content, as: Heading = 'h2' }) {
    return (
        <>
            {content.eyebrow && <p className="eyebrow">{content.eyebrow}</p>}
            {content.heading && <Heading className="heading-2 mt-4">{content.heading}</Heading>}
            {content.summary && <p className="reading-width mt-5 leading-8 text-muted">{content.summary}</p>}
        </>
    );
}

function Media({ media, className = '' }) {
    if (! media) {
        return null;
    }

    return (
        <figure className={className}>
            <img
                className="aspect-[3/2] w-full rounded-[var(--m-radius)] object-cover"
                src={media.src}
                srcSet={media.srcset ?? undefined}
                sizes={media.srcset ? media.sizes : undefined}
                width={media.width ?? undefined}
                height={media.height ?? undefined}
                alt={media.alt}
                aria-hidden={media.decorative || undefined}
                loading="lazy"
                decoding="async"
            />
            {media.caption && <figcaption className="mt-3 text-sm text-muted">{media.caption}</figcaption>}
        </figure>
    );
}

function SectionBody({ section, emptyTitle }) {
    const { content } = section;

    switch (section.type) {
        case 'page_header':
            return (
                <header className="m-page-header">
                    {content.eyebrow && <p className="eyebrow">{content.eyebrow}</p>}
                    <h1>{content.heading}</h1>
                    {content.summary && <p className="m-lead">{content.summary}</p>}
                </header>
            );
        case 'form_introduction':
            return (
                <header className="m-page-header">
                    {content.eyebrow && <p className="eyebrow">{content.eyebrow}</p>}
                    <h1>{content.heading}</h1>
                    {content.summary && <p className="m-lead">{content.summary}</p>}
                    {content.privacy_guidance && <p className="m-meta mx-auto max-w-xl">{content.privacy_guidance}</p>}
                </header>
            );
        case 'homepage_hero':
            return (
                <div className="m-center">
                    {content.eyebrow && <p className="m-eyebrow">{content.eyebrow}</p>}
                    <h1 className="home-signature-title">{content.heading}</h1>
                    {content.summary && <p className="m-lead">{content.summary}</p>}
                    <Actions actions={section.actions} center />
                    <Media media={section.media} className="mt-10 w-full" />
                </div>
            );
        case 'rich_text':
            return (
                <div className="reading-width">
                    <Intro content={{ eyebrow: content.eyebrow, heading: content.heading }} />
                    <div className="mt-6 whitespace-pre-line text-base leading-8">{content.body}</div>
                </div>
            );
        case 'image_text':
            return (
                <div className="grid items-center gap-10 lg:grid-cols-2">
                    <Media media={section.media} />
                    <div>
                        <Intro content={content} />
                        <Actions actions={section.actions} />
                    </div>
                </div>
            );
        case 'impact_metrics':
            return (
                <div>
                    <Intro content={content} />
                    <dl className="m-stats">
                        {(content.items ?? []).map((item) => (
                            <div key={item.label} className="m-stat">
                                <dt className="font-semibold">{item.label}</dt>
                                <dd className="m-stat-number order-first m-0">{item.value}</dd>
                                {item.context && <small>{item.context}</small>}
                            </div>
                        ))}
                    </dl>
                </div>
            );
        case 'featured_collection':
        case 'related_content':
            return (
                <div>
                    <Intro content={content} />
                    {section.relations.length > 0 ? (
                        <div className="m-grid m-grid-3 mt-8">
                            {section.relations.map((relation, index) => (
                                <article key={`${relation.title}-${index}`} className="m-card">
                                    <p className="m-index">{relation.type}</p>
                                    <h3>{relation.title}</h3>
                                </article>
                            ))}
                        </div>
                    ) : (
                        <EmptyState title={content.empty_title ?? emptyTitle} description={content.empty_summary ?? ''} />
                    )}
                </div>
            );
        case 'quote':
            return (
                <figure className="mx-auto max-w-4xl text-center">
                    <blockquote className="m-quote">“{content.quote}”</blockquote>
                    {content.attribution && (
                        <figcaption className="mt-5 font-semibold">
                            {content.attribution}
                            {content.role && <span className="font-normal text-muted"> — {content.role}</span>}
                        </figcaption>
                    )}
                </figure>
            );
        case 'faq':
            return (
                <div className="mx-auto max-w-4xl">
                    <Intro content={{ eyebrow: content.eyebrow, heading: content.heading }} />
                    <div className="mt-8 divide-y divide-edge border-y border-edge">
                        {(content.items ?? []).map((item, index) => (
                            <details key={`${item.question}-${index}`} className="group py-5">
                                <summary className="cursor-pointer font-bold">{item.question}</summary>
                                <p className="mt-4 leading-7 text-muted">{item.answer}</p>
                            </details>
                        ))}
                    </div>
                </div>
            );
        case 'cta_panel':
            return (
                <div className="grid items-end gap-7 lg:grid-cols-[1fr_auto]">
                    <div>
                        <Intro content={content} />
                    </div>
                    <Actions actions={section.actions} />
                </div>
            );
        case 'contact_panel':
        case 'newsletter_panel':
            return (
                <div>
                    <Intro content={content} />
                    <Actions actions={section.actions} />
                </div>
            );
        case 'divider':
            return section.variant === 'line' ? <hr className="border-edge" /> : <span className="block h-8" aria-hidden="true" />;
        default:
            return null;
    }
}

export default function PageComposition({ composition, emptyTitle }) {
    if (! composition?.sections?.length) {
        return null;
    }

    return composition.sections.map((section) => {
        const header = section.type === 'page_header' || section.type === 'form_introduction';
        const width = header ? 'content-container' : (WIDTHS[section.width] ?? WIDTHS.standard);
        const spacing = header ? 'py-0' : (SPACING[section.spacing] ?? SPACING.standard);

        return (
            <section
                key={section.id}
                id={section.key}
                className={SURFACES[section.surface] ?? ''}
                data-section-type={section.type}
                data-section-version={section.id}
            >
                <Reveal className={`${width} ${spacing}`}>
                    <SectionBody section={section} emptyTitle={emptyTitle} />
                </Reveal>
            </section>
        );
    });
}
