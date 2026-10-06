import { Link } from '@inertiajs/react';
import WorkspaceLayout from '../../../Layouts/WorkspaceLayout';
import { Action, Editor, Filters, Pagination, Panel, Status, Table, date, field, useWorkspace } from '../../../Components/Workspace/UI';

const severityOrder = { blocking: 0, warning: 1, information: 2 };

function Severity({ value }) {
    const tone = value === 'blocking' ? 'status-badge-danger' : value === 'warning' ? 'status-badge-warning' : 'status-badge-neutral';
    return <span className={`status-badge ${tone}`}>{value}</span>;
}

function Issues({ issues = [], empty = 'No findings in the latest audit.' }) {
    const sorted = [...issues].sort((a, b) => (severityOrder[a.severity] ?? 3) - (severityOrder[b.severity] ?? 3));
    return <Table data={sorted} empty={empty} columns={[
        { label: 'Severity', render: issue => <Severity value={issue.severity} /> },
        { label: 'Check', render: issue => <code>{issue.code}</code> },
        { label: 'Where', render: issue => issue.url ?? issue.subject ?? 'Site' },
        { label: 'Finding', render: issue => issue.message },
    ]} />;
}

function Metric({ label, value, href }) {
    const body = <><span className="block text-2xl font-semibold">{value ?? '—'}</span><span className="text-sm text-muted">{label}</span></>;
    return href ? <Link className="admin-panel block" href={href}>{body}</Link> : <div className="admin-panel">{body}</div>;
}

function NoAudit() {
    const { t } = useWorkspace();
    return <div className="empty-state"><p>{t('No audit has been run yet. Run an audit to populate this view.')}</p><Action href="/admin/seo/audit" className="button-primary">{t('Run SEO audit')}</Action></div>;
}

function Overview({ data, latestAudit }) {
    const m = data.metrics;
    if (!m) return <NoAudit />;
    const s = section => `/admin/seo?section=${section}`;
    return <>
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <Metric label="Indexable pages" value={m.indexable_pages} href={s('pages')} />
            <Metric label="Blocking issues" value={m.blocking} href={s('audit')} />
            <Metric label="SEO warnings" value={m.warnings} href={s('audit')} />
            <Metric label="Missing titles" value={m.missing_titles} href={s('metadata')} />
            <Metric label="Fallback descriptions" value={m.missing_descriptions} href={s('metadata')} />
            <Metric label="Missing social images" value={m.missing_social_images} href={s('social')} />
            <Metric label="Broken links (open)" value={data.openBrokenLinks} href={s('links')} />
            <Metric label="Redirect problems" value={m.redirect_problems} href={s('redirects')} />
            <Metric label="Structured-data issues" value={m.structured_data_issues} href={s('structured-data')} />
            <Metric label="Orphan pages" value={m.orphan_pages} href={s('quality')} />
            <Metric label="Oversized images" value={m.oversized_images} href={s('quality')} />
            <Metric label="Active redirects" value={data.redirects} href={s('redirects')} />
        </div>
        <Panel title="Sitemap and indexing">
            <dl className="grid gap-3 sm:grid-cols-3">
                <div><dt className="text-sm text-muted">Sitemap generated</dt><dd>{date(m.sitemap?.generated_at)}</dd></div>
                <div><dt className="text-sm text-muted">Indexing in this environment</dt><dd>{m.indexing_allowed ? 'Allowed' : 'Disabled (noindex everywhere)'}</dd></div>
                <div><dt className="text-sm text-muted">Canonical host</dt><dd>{m.canonical_base_url}</dd></div>
            </dl>
            <p className="form-help mt-4">Search rankings and impressions are not shown here: they come only from Google Search Console and Bing Webmaster Tools.</p>
        </Panel>
        <Panel title={`Blocking issues and warnings (${latestAudit?.status ?? ''})`}>
            <Issues issues={(data.blocking ?? []).filter(i => i.severity !== 'information').slice(0, 25)} />
        </Panel>
    </>;
}

function Pages({ data, social = false }) {
    return <Table data={data.pages} columns={[
        { label: 'Page', render: p => <><strong>{p.name}</strong><span className="block text-sm text-muted">{p.typeLabel}</span></> },
        { label: 'URL', render: p => <a className="text-link" href={p.path} target="_blank" rel="noreferrer">{p.path}</a> },
        { label: social ? 'Social card' : 'Indexing', render: p => social ? (p.override ? 'Custom' : 'Default') : p.robots },
        { label: 'Overrides', render: p => p.override ? 'Yes' : 'No' },
        { label: 'Actions', render: p => <Link className="button-secondary" href={p.editUrl}>{social ? 'Preview' : 'Edit SEO'}</Link> },
    ]} />;
}

function Metadata({ data }) {
    if (!data.pages?.length) return <NoAudit />;
    return <>
        <Table data={data.pages} columns={[
            { label: 'Page', render: p => p.path },
            { label: 'Title', render: p => <>{p.title}<span className="block text-xs text-muted">{(p.title ?? '').length} characters</span></> },
            { label: 'Description', render: p => <>{p.description}<span className="block text-xs text-muted">{(p.description ?? '').length} characters</span></> },
            { label: 'H1', render: p => (p.h1 ?? []).join(' / ') || '—' },
        ]} />
        <Panel title="Title and description findings"><Issues issues={data.issues} /></Panel>
    </>;
}

function Sitemap({ data }) {
    return <>
        <Panel title="XML sitemap">
            <p className="mb-4">Index: <a className="text-link" href={data.index} target="_blank" rel="noreferrer">{data.index}</a> · {data.enabled ? 'Enabled' : 'Disabled'} · Safety-net rebuild: {data.frequency}</p>
            <p className="mb-4 text-sm text-muted">Last generated: {date(data.status?.generated_at)} for {data.status?.host ?? '—'}. Publishing, unpublishing, archiving and slug changes rebuild it automatically.</p>
            <Action href="/admin/seo/sitemap" className="button-primary">Regenerate sitemap now</Action>
        </Panel>
        <Table data={data.segments} columns={[
            { label: 'Child sitemap', render: s => <a className="text-link" href={s.url} target="_blank" rel="noreferrer">{s.segment}.xml</a> },
            { label: 'URLs', render: s => s.count },
        ]} />
    </>;
}

function Redirects({ data }) {
    const { t } = useWorkspace();
    return <>
        <Panel title="Add a redirect">
            <Editor action="/admin/seo/redirects" initial={{ source_path: '', destination_url: '', status_code: '301', reason: '' }} onSuccess={form => form.reset()} submit="Save redirect" fields={[
                field('source_path', 'Old path', 'text', { required: true, placeholder: '/old-page', help: 'A path on this site that no longer serves a page.' }),
                field('destination_url', 'New path', 'text', { placeholder: '/services/strategy-transformation', help: 'Local paths only. Leave empty for 410 Gone.' }),
                field('status_code', 'Response', 'select', { options: { 301: '301 Moved permanently', 308: '308 Permanent (method preserved)', 302: '302 Temporary', 307: '307 Temporary (method preserved)', 410: '410 Gone' } }),
                field('reason', 'Reason', 'text', { required: true }),
            ]} />
        </Panel>
        <Panel title="Integrity">
            <p className="mb-4">{data.validation.label}: {data.validation.blocking} blocking, {data.validation.warnings} warnings.</p>
            <Issues issues={data.validation.issues} empty="All redirects are single-hop, local and loop-free." />
            <div className="mt-4"><Action href="/admin/seo/redirects/flatten">{t('Flatten redirect chains')}</Action></div>
        </Panel>
        <Filters fields={[field('q', 'Path contains'), field('origin', 'Origin', 'select', { options: { '': 'Any', ...data.origins } })]} />
        <Table data={data.redirects} columns={[
            { label: 'From', render: r => <code>{r.source_path}</code> },
            { label: 'To', render: r => r.destination_url ? <code>{r.destination_url}</code> : <em>Gone</em> },
            { label: 'Status', render: r => r.status_code },
            { label: 'Origin', render: r => data.origins[r.origin] ?? r.origin },
            { label: 'Hits', render: r => r.hit_count },
            { label: 'State', render: r => <Status value={r.enabled ? 'enabled' : 'disabled'} /> },
            { label: 'Actions', render: r => r.enabled && <Action href={`/admin/seo/redirects/${r.id}`} method="delete" confirm="Disable this redirect? The old URL will return 404.">Disable</Action> },
        ]} />
        <Pagination data={data.redirects} />
    </>;
}

function Links({ data }) {
    return <>
        <Panel title="Broken link checks">
            <p className="mb-4 text-sm text-muted">Last checked: {date(data.lastChecked)}. Internal links are checked here; external links are checked by <code>php artisan seo:links-check --external</code> with a timeout and rate limit.</p>
            <Action href="/admin/seo/links" className="button-primary">Check internal links now</Action>
        </Panel>
        <Table data={data.links} empty="No broken or redirecting links found." columns={[
            { label: 'Link', render: l => <><code className="break-all">{l.url}</code>{l.link_text && <span className="block text-sm text-muted">“{l.link_text}”</span>}</> },
            { label: 'Source', render: l => <>{l.source_label}<span className="block text-xs text-muted break-all">{l.source_url}</span></> },
            { label: 'Result', render: l => <>{l.result} ({l.status_code ?? 'no response'})</> },
            { label: 'Severity', render: l => <Severity value={l.severity} /> },
            { label: 'Detected', render: l => <>{date(l.first_detected_at)}<span className="block text-xs text-muted">checked {date(l.last_checked_at)}</span></> },
            { label: 'Resolution', render: l => <div className="flex flex-wrap gap-2"><Status value={l.resolution_status} />{l.resolution_status === 'open' && <Action href={`/admin/seo/links/${l.id}`} method="patch" data={{ resolution_status: 'ignored' }}>Ignore</Action>}</div> },
        ]} />
        <Pagination data={data.links} />
    </>;
}

function Indexing({ data }) {
    return <>
        <Panel title="Indexing policy">
            <dl className="grid gap-4 sm:grid-cols-2">
                <div><dt className="text-sm text-muted">Environment</dt><dd>{data.environment}</dd></div>
                <div><dt className="text-sm text-muted">Search engines may index</dt><dd>{data.indexingAllowed ? 'Yes (production)' : 'No: every response is noindex, nofollow and robots.txt disallows all'}</dd></div>
                <div><dt className="text-sm text-muted">Canonical site URL</dt><dd>{data.canonicalBaseUrl}</dd></div>
                <div><dt className="text-sm text-muted">robots.txt</dt><dd><a className="text-link" href={data.robotsUrl} target="_blank" rel="noreferrer">{data.robotsUrl}</a></dd></div>
                <div><dt className="text-sm text-muted">Internal search</dt><dd>{data.searchNoindex ? 'noindex, follow; not in sitemap' : 'Indexable'}</dd></div>
                <div><dt className="text-sm text-muted">Archived content</dt><dd>{data.archivedGone ? '410 Gone' : '404 Not Found'}</dd></div>
                <div><dt className="text-sm text-muted">Drafts, previews, admin, sign-in</dt><dd>Always noindex, nofollow; authorization still applies</dd></div>
                <div><dt className="text-sm text-muted">Verification tags</dt><dd>Google: {data.googleVerification ? 'set' : 'not set'} · Bing: {data.bingVerification ? 'set' : 'not set'}</dd></div>
            </dl>
        </Panel>
        <Panel title="URL and indexing findings"><Issues issues={data.issues} /></Panel>
    </>;
}

function Settings({ data }) {
    return <Panel title="Current SEO settings">
        <dl className="grid gap-3">{Object.entries(data.values).map(([key, value]) => <div key={key} className="grid gap-1 sm:grid-cols-[14rem_1fr]"><dt className="text-sm text-muted">{key}</dt><dd>{value}</dd></div>)}</dl>
        <Link className="button-primary mt-5 inline-flex" href={data.editUrl}>Edit in the Settings Center</Link>
    </Panel>;
}

function Audit({ data }) {
    return <>
        <Panel title="Run an audit">
            <p className="mb-4 text-sm text-muted">Renders every indexable page as a crawler would and checks titles, descriptions, canonicals, robots, sitemap membership, structured data, headings, images, redirects, links and English-only URL hygiene. The same checks run with <code>php artisan seo:audit --strict</code>.</p>
            <Action href="/admin/seo/audit" className="button-primary">Run SEO audit</Action>
        </Panel>
        <Table data={data.runs} empty="No audits yet." columns={[
            { label: 'When', render: r => date(r.created_at) },
            { label: 'Blocking', render: r => r.blocking_count },
            { label: 'Warnings', render: r => r.warning_count },
            { label: 'Information', render: r => r.information_count },
            { label: 'Trigger', render: r => r.trigger },
        ]} />
        <Panel title="Latest findings"><Issues issues={data.issues} /></Panel>
    </>;
}

export default function Index({ section, sections, latestAudit, data }) {
    const { t } = useWorkspace();
    const body = {
        overview: <Overview data={data} latestAudit={latestAudit} />,
        pages: <Pages data={data} />,
        metadata: <Metadata data={data} />,
        sitemap: <Sitemap data={data} />,
        redirects: <Redirects data={data} />,
        'structured-data': data.pages?.length ? <><Table data={data.pages} columns={[{ label: 'Page', render: p => p.path }, { label: 'Types', render: p => (p.structuredData ?? []).join(', ') }]} /><Panel title="Structured-data findings"><Issues issues={data.issues} empty="All structured data is valid." /></Panel></> : <NoAudit />,
        links: <Links data={data} />,
        indexing: <Indexing data={data} />,
        quality: <Panel title="Headings, orphan pages, images and links"><Issues issues={data.issues} /></Panel>,
        social: <Pages data={data} social />,
        settings: <Settings data={data} />,
        audit: <Audit data={data} />,
    }[section];

    return <WorkspaceLayout title="SEO centre" description={latestAudit ? `${latestAudit.status} · ${latestAudit.blocking} blocking · ${latestAudit.warnings} warnings · audited ${date(latestAudit.created_at)}` : 'English-only search visibility, indexing and URL governance.'}>
        <nav aria-label={t('SEO sections')} className="flex flex-wrap gap-2">
            {Object.entries(sections).map(([key, label]) => <Link key={key} href={`/admin/seo?section=${key}`} className={key === section ? 'button-primary' : 'button-secondary'} aria-current={key === section ? 'page' : undefined}>{t(label)}</Link>)}
        </nav>
        {body}
    </WorkspaceLayout>;
}
