import { Link } from '@inertiajs/react';
import WorkspaceLayout from '../../../Layouts/WorkspaceLayout';
import { Editor, Field, Panel, Table, field, useWorkspace } from '../../../Components/Workspace/UI';

const TITLE_MAX = 65;
const DESCRIPTION_MAX = 160;

function Count({ value, max }) {
    const length = (value ?? '').length;
    return <span className={length > max ? 'field-error text-xs' : 'text-xs text-muted'}>{length} / {max}</span>;
}

// Approximation of a search result: what the title and description look
// like after the brand suffix and truncation. Not a ranking prediction.
function SearchPreview({ form, preview, subject }) {
    const title = form.data.meta_title || preview.title || subject.name;
    const description = form.data.meta_description || preview.description || '';
    return <div className="rounded-lg border border-edge bg-white p-4">
        <p className="text-sm text-muted">{preview.host}{subject.path === '/' ? '' : subject.path.replaceAll('/', ' › ')}</p>
        <p className="text-lg text-link">{title.length > TITLE_MAX ? `${title.slice(0, TITLE_MAX - 1)}…` : title}</p>
        <p className="text-sm">{description.length > DESCRIPTION_MAX ? `${description.slice(0, DESCRIPTION_MAX - 1)}…` : description}</p>
    </div>;
}

function SocialPreview({ form, preview }) {
    const title = form.data.social_title || form.data.meta_title || preview.socialTitle || preview.title;
    const description = form.data.social_description || form.data.meta_description || preview.socialDescription || preview.description;
    return <div className="max-w-md overflow-hidden rounded-lg border border-edge bg-white">
        {preview.image?.url
            ? <img src={preview.image.url} alt={preview.image.alt ?? ''} width={preview.image.width ?? undefined} height={preview.image.height ?? undefined} className="aspect-[1.91/1] w-full object-cover" loading="lazy" />
            : <div className="aspect-[1.91/1] w-full bg-quiet" aria-hidden="true" />}
        <div className="p-3">
            <p className="text-xs uppercase text-muted">{preview.host}</p>
            <p className="font-semibold">{title}</p>
            <p className="text-sm text-muted">{description}</p>
        </div>
    </div>;
}

function Findings({ validation }) {
    const tone = { blocking: 'status-badge-danger', warning: 'status-badge-warning', information: 'status-badge-neutral' };
    return <>
        <p className="mb-3"><span className={`status-badge ${validation.status === 'blocking' ? 'status-badge-danger' : validation.status === 'warnings' ? 'status-badge-warning' : 'status-badge-success'}`}>{validation.label}</span></p>
        {validation.issues.length ? <ul className="grid gap-2">{validation.issues.map((issue, index) => <li key={`${issue.code}-${index}`} className="flex gap-3"><span className={`status-badge ${tone[issue.severity]}`}>{issue.severity}</span><span>{issue.message}</span></li>)}</ul> : <p className="text-sm text-muted">No findings.</p>}
    </>;
}

export default function Edit({ subject, values, preview, validation, relatedOptions, redirects, mediaOptions, intents, geographies, robotsOptions }) {
    const { t } = useWorkspace();
    const relatedGroups = relatedOptions.reduce((groups, option) => ({ ...groups, [option.group]: [...(groups[option.group] ?? []), option] }), {});

    return <WorkspaceLayout title={`SEO: ${subject.name}`} description={`${subject.typeLabel} · ${subject.canonicalUrl}`} actions={<Link className="button-secondary" href="/admin/seo?section=pages">{t('All pages')}</Link>}>
        <div className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_24rem]">
            <Panel title="Search and social metadata">
                <Editor action={`/admin/seo/pages/${subject.type}/${subject.key}`} method="put" initial={values} submit="Save SEO settings">
                    {form => <>
                        <section className="grid gap-4" aria-labelledby="seo-search">
                            <h3 id="seo-search" className="heading-4">Search result</h3>
                            <SearchPreview form={form} preview={preview} subject={subject} />
                            <Field form={form} {...field('meta_title', 'SEO title', 'text', { maxLength: 70, help: 'Leave empty to use the page title. The site suffix is added once automatically.' })} />
                            <Count value={form.data.meta_title || preview.title} max={TITLE_MAX} />
                            <Field form={form} {...field('meta_description', 'Meta description', 'textarea', { maxLength: 170, help: 'Leave empty to use the summary or excerpt. Aim for 50–160 characters.' })} />
                            <Count value={form.data.meta_description || preview.description} max={DESCRIPTION_MAX} />
                            {subject.slug !== null && <Field form={form} {...field('slug', 'URL slug', 'text', { required: true, help: `Public URL: ${subject.path}. Changing a published slug creates a permanent redirect from the old URL.` })} />}
                        </section>
                        <section className="grid gap-4" aria-labelledby="seo-indexing">
                            <h3 id="seo-indexing" className="heading-4">Indexing</h3>
                            <Field form={form} {...field('robots', 'Robots directive', 'select', { options: robotsOptions, help: 'Drafts, previews and private pages are always noindex regardless of this setting.' })} />
                            <Field form={form} {...field('include_in_sitemap', 'Include in the XML sitemap', 'checkbox')} />
                            <Field form={form} {...field('canonical_path', 'Canonical override', 'text', { placeholder: subject.path, help: 'Only for duplicates of another page on this site. Leave empty in almost every case.' })} />
                        </section>
                        <section className="grid gap-4" aria-labelledby="seo-social">
                            <h3 id="seo-social" className="heading-4">Social sharing</h3>
                            <SocialPreview form={form} preview={preview} />
                            <Field form={form} {...field('social_title', 'Social title', 'text', { maxLength: 95 })} />
                            <Field form={form} {...field('social_description', 'Social description', 'textarea', { maxLength: 200 })} />
                            <Field form={form} {...field('social_image_media_id', 'Social image', 'select', { options: mediaOptions, help: 'Approved public images only. Falls back to the page image, then the default social image.' })} />
                        </section>
                        <details className="workspace-advanced">
                            <summary>{t('Editorial intent (not published as keywords)')}</summary>
                            <div className="mt-4 grid gap-4">
                                <Field form={form} {...field('primary_topic', 'Primary topic')} />
                                <Field form={form} {...field('secondary_topics', 'Secondary topics', 'text', { help: 'Comma separated.' })} />
                                <Field form={form} {...field('target_audience', 'Target audience')} />
                                <Field form={form} {...field('search_intent', 'Search intent', 'select', { options: intents })} />
                                <Field form={form} {...field('geographic_relevance', 'Geographic relevance', 'checks', { options: geographies.map(value => ({ value, label: value })), help: 'Select only markets this content genuinely addresses. Services use it for areaServed.' })} />
                            </div>
                        </details>
                        {relatedOptions.length > 0 && <details className="workspace-advanced" open={values.related.length > 0}>
                            <summary>{t('Related content (internal links)')}</summary>
                            <p className="form-help mt-3">Shown on this page as contextual links. Only published pages can be linked.</p>
                            <div className="mt-4 grid gap-4">{Object.entries(relatedGroups).map(([group, options]) => <Field key={group} form={form} name="related" label={group} type="checks" options={options} />)}</div>
                        </details>}
                    </>}
                </Editor>
            </Panel>
            <div className="grid content-start gap-6">
                <Panel title="SEO checks"><Findings validation={validation} /></Panel>
                <Panel title="Live page">
                    <dl className="grid gap-3 text-sm">
                        <div><dt className="text-muted">Canonical</dt><dd className="break-all">{preview.canonical ?? subject.canonicalUrl}</dd></div>
                        <div><dt className="text-muted">Robots (production)</dt><dd>{preview.robots ?? '—'}</dd></div>
                        <div><dt className="text-muted">Robots sent here</dt><dd>{preview.effectiveRobots ?? '—'}</dd></div>
                        <div><dt className="text-muted">Main heading</dt><dd>{preview.h1.join(' / ') || '—'}</dd></div>
                        <div><dt className="text-muted">Structured data</dt><dd>{preview.structuredData.join(', ') || '—'}</dd></div>
                    </dl>
                    <a className="text-link mt-4 inline-block" href={subject.path} target="_blank" rel="noreferrer">{t('Open page')}</a>
                </Panel>
                <Panel title="Redirects to this page">
                    <Table data={redirects} empty="No redirects point here." columns={[
                        { label: 'From', render: r => <code className="break-all">{r.source_path}</code> },
                        { label: 'Status', render: r => r.status_code },
                        { label: 'Hits', render: r => r.hit_count },
                    ]} />
                </Panel>
            </div>
        </div>
    </WorkspaceLayout>;
}
